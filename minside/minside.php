<?php
if(!defined('DOKU_INC')) die(); // Dette scriptet kan kun kjøres via dokuwiki
define('MS_INC', true); // Alle underscript sjekker om denne er definert
define('MS_LINK', "?do=minside");
define('MSAUTH_NONE',0); // Matcher dokuwiki sine auth verdier
define('MSAUTH_1',1); // Lese
define('MSAUTH_2',2); // Redigere
define('MSAUTH_3',4); // Lage
define('MSAUTH_4',8); // Laste opp
define('MSAUTH_5',16); // Slette
define('MSAUTH_ADMIN',255); // Wiki-admin
define('MS_IMG_PATH', DOKU_REL . 'lib/plugins/minside/minside/bilder/');

require_once('msconfig.php');
require_once('class.database.php');
require_once('interface.msmodul.php');
require_once('class.msdispatcher.php');
require_once('class.collectioniterator.php');
require_once('class.collection.php');
require_once('class.erstatter.php');
require_once('class.menyitem.php');
require_once('class.menyitemcollection.php');


class minside { // denne classen instansieres og gen_minside() kjøres for å generere minside

private $_msmod = array(); // array som holder alle lastede moduler som objekter
private $UserID; // settes til brukerens interne minside-id når og hvis den sjekkes
private $username; // brukernavn som oppgis når script kalles, alltid tilgjengelig

	public function __construct($username) { // kalles når class instansieres
	
		try {
			$GLOBALS['msdb'] = new Database(); // $msdb blir en globalt tilgjengelig db-class, se class.database.php
		} catch(Exception $e) {
			die($e->getMessage());
		}
		$this->username = $username; 
		
	}
	
	public function gen_minside() { // returnerer all nødvendig xhtml for å vise minside som en streng
		if (!($this->sjekkAdgang('vismeny') > MSAUTH_NONE)) {
			return '<h1>Ingen adgang</h1><p>Brukeren ' . $this->username . ' har ikke tilgang til å vise Min Side. ' . 
				'Kontakt en teamleder dersom du har spørsmål til dette.</p>';
		}
		$this->updateUserInfo();
		
		$this->_lastmoduler(); // alle moduler definert i msconfig.php instansieres, se funksjonen
		
		// Kode under er midlertidig hack for å vise "et eller annet" på forsiden
		
		$mspremenu .= '<div class="minside">'; 
		
		if(array_key_exists('page', $_REQUEST)) {
			$page = $_REQUEST['page'];
		} else {
			$page = 'nyheter';
		}
		
		if(array_key_exists('act', $_REQUEST)) {
			$act = $_REQUEST['act'];
		} else {
			$act = 'show';
		}
		
		$msdisp = new msdispatcher($page, $this->_msmod, $this, $act, NULL);
		$msoutput .= '<h1>' . ucfirst($page) . '</h1>';
		$msoutput .= $msdisp->dispatch();

		$msoutput .= '<div class="msclearer"></div></div>';
		
		$msoutput = $mspremenu . $this->_genMeny() . $msoutput; // meny genereres til slutt for å gi moduler mest mulig
																// valgfrihet i hvilke menyitems som skal vises, men
		return $msoutput;										// legges i starten av output.
		
		
	}
	
	private function _lastmoduler() {
		
		foreach (mscfg::$moduler as $modulnavn) { 											// se msconfig.php
			require_once 'moduler/' . $modulnavn . '/' . $modulnavn . '.msmodul.php';		// f.eks. moduler/testmodul/testmodul.msmodul.php
			$msclassnavn = 'msmodul_' . $modulnavn;											// modulens hoved class skal være f.eks. msmodul_testmodul
			$this->_msmod[$modulnavn] = new $msclassnavn($this->getUserID(), $this->sjekkAdgang($modulnavn)); // alle moduler får userid og accessnivå i forhold til modul @ instansiering
			// $this->_msmod holder alle lastede moduler
		}
	
	}
	
	public function getUserID($recursion = false) { // returnerer nåværende brukers interne userid, forsøker å opprette ny bruker om den ikke finnes
		
		if ($this->username == '') { die('Username not set on minside create'); } // skal i utgangspunktet aldri skje
			
		if (isset($this->UserID)) {
			return $this->UserID;			// dersom denne funksjonen allerede er kjørt er det bare å svare samme som sist gang
		} else {
			$lookupid = $this->_lookupUserID($this->username); // _lookupUserID sjekker db for username
			if ($lookupid === false) { // finner ikke username i db
				if ($recursion === true) {die('Klarer ikke å opprette brukerid');} // dersom denne funksjonen er kallt av seg selv gir vi opp nå. user er i så fall forsøkt opprettet, men finnes enda ikke i db.
				$this->_createUser($this->username); // forsøker å opprette bruker
				return $this->getUserID(true); // kaller seg selv for å sjekke om bruker nå finnes i db
			} else { 					// fant username i db
				$this->UserID = $lookupid; // lagre resultat for neste gang funksjonen kalles
				return $lookupid;		
			}
		}

	}
	
	private function _lookupUserID($username) {  // sjekker db for gitt username, returnerer userid eller false
		global $msdb;
		if ($username == '') { die('Kan ikke sjekke id til tomt brukernavn'); }
		
		$result = $msdb->assoc('SELECT id FROM internusers WHERE wikiname = ' . $msdb->quote($this->username) . ' LIMIT 1;');
		
		if ($result[0]['id'] != 0) {
			return $result[0]['id']; // fant userid
		} else {
			return false;			// fant ikke userid
		}		
	}
	
	private function _createUser($username){ // forsøker å opprette ny userid for gitt username
		global $msdb;
		if ($username == '') { die('Kan ikke opprette bruker med tomt brukernavn'); }
	
		$result = $msdb->exec("INSERT INTO internusers (wikiname, createtime, lastlogin, isactive) VALUES (" . $msdb->quote($username) . ", now(), now(), '1');");
		if ($result = 1) {
			msg("Opprettet ny brukerid for bruker: $username",1); //debug
			return true;
		} else {
			return false; // klarte ikke å opprette bruker - db classen klikker sikkert før vi får returnert false her...
		}
	
	}
	
	private function _genMeny() { // returnerer streng med nødvendig xhtml for å vise menyen
	
		$meny = new MenyitemCollection(); // collection-variabel som sendes til alle lastede moduler
		
		foreach ($this->_msmod as $msmod) {
			$msmod->registrer_meny($meny); // hver modul får collection by reference, slik at menyitems kan legges til
		}
	
		$output .= '<div class="mstoc">'."\n";
		$output .= '<div class="mstocheader">Min Side - Meny</div>'."\n";
		$output .= '<div class="meny">'."\n";
		$output .= $this->_genMenyitem($meny, 1); // recursive funksjon som kaller seg selv dersom et item har underitems
		$output .= '</div>'."\n";
		$output .= '</div>'."\n";
		
		$output .= '';
		
		return $output;
	}
	
	private function _genMenyitem(MenyitemCollection &$col, $lvl) { // recursive funksjon som kaller seg selv dersom et item har underitems
		if ($lvl > 5) { return; }  // endres for å bestemme maks nivåer i meny
		$output .= '<ul class="mstoc">';
		foreach ($col as $menyitem) {
			$output .= "<li class=\"level$lvl\">";
			$output .= '<div class="li"><span class="li"><a href="' . MS_LINK . $menyitem->getHref() . '" class="toc">' . $menyitem->getTekst() . '</a></span></div>';
			$output .= '</li>';
			if ($menyitem->hasChildren()) {
				$output .= $this->_genMenyitem($menyitem->getChildren(), $lvl+1);
			}
		}
		$output .= '</ul>';
		
		return $output;
	}
	
	public function sjekkAdgang($modul = '') { // returnerer en int (se definisjon på toppen av denne filen, samt dokuwikis auth.php
	
		$id = 'msauth:' . $modul . ':info'; // siden msauth:modulnavn:info må opprettes i dokuwiki for hver modul. ACL må settes opp mot denne siden.
		// echo 'Sjekker adgang til: ', $id, '. Adgangsnivå er: ', auth_quickaclcheck($id), '<br />'; // Debug.
		return auth_quickaclcheck($id);	
	
	}
	
	public function getDbGentime(){
		global $msdb;
		
		return $msdb->querytime;
	}
	
	public function getNumDbQueries(){
		global $msdb;
		
		return $msdb->num_queries;
	}
	
	public function updateUserInfo() {
		global $INFO;
		global $msdb;
		
		$fullname = $INFO['userinfo']['name'];
		$epost = $INFO['userinfo']['mail'];
		$groups = implode(',', $INFO['userinfo']['grps']);

		$safeuserid = $msdb->quote($this->getUserID());
		
		$sql = "SELECT wikifullname, wikiepost, wikigroups FROM internusers WHERE id=$safeuserid LIMIT 1;";
		$data = $msdb->assoc($sql);
		
		if(is_array($data) && sizeof($data)) {
			$datum = $data[0];
			$sqlfullname = $datum['wikifullname'];
			$sqlepost = $datum['wikiepost'];
			$sqlgroups = $datum['wikigroups'];
		}
		
		$arSql = array();
		
		if ($fullname != $sqlfullname) {
			$safefullname = $msdb->quote($fullname);
			$arSql[] = "UPDATE internusers SET wikifullname=$safefullname, modifytime=now() WHERE id=$safeuserid LIMIT 1;";
		}
		
		if ($epost != $sqlepost) {
			$safeepost = $msdb->quote($epost);
			$arSql[] = "UPDATE internusers SET wikiepost=$safeepost, modifytime=now() WHERE id=$safeuserid LIMIT 1;";
		}
		
		if ($groups != $sqlgroups) {
			$safegroups = $msdb->quote($groups);
			$arSql[] = "UPDATE internusers SET wikigroups=$safegroups, modifytime=now() WHERE id=$safeuserid LIMIT 1;";
		}
		
		foreach ($arSql as $sql) {
			$msdb->exec($sql);
		}
		
	}

}
