<?php
if(!defined('DOKU_INC')) die(); // Dette scriptet kan kun kjøres via dokuwiki
define('MW_INC', true); // Alle underscript sjekker om denne er definert
define(MW_LINK, "?do=minwiki");
define('MWAUTH_NONE',0); // Matcher dokuwiki sine auth verdier
define('MWAUTH_1',1);
define('MWAUTH_2',2);
define('MWAUTH_3',4);
define('MWAUTH_4',8);
define('MWAUTH_5',16);
define('MWAUTH_ADMIN',255);

require_once('mwconfig.php');
require_once('class.database.php');
require_once('interface.mwmodul.php');
require_once('class.mwdispatcher.php');
require_once('class.collectioniterator.php');
require_once('class.collection.php');
require_once('class.menyitem.php');
require_once('class.menyitemcollection.php');


class minwiki { // denne classen instansieres og gen_minwiki() kjøres for å generere minwiki

private $mwmod = array(); // array som holder alle lastede moduler som objekter
private $UserID; // settes til brukerens interne minwiki-id når og hvis den sjekkes
private $username; // brukernavn som oppgis når script kalles, alltid tilgjengelig

	public function __construct($username) { // kalles når class instansieres
	
		try {
			$GLOBALS['mwdb'] = new Database(); // $mwdb blir en globalt tilgjengelig db-class, se class.database.php
		} catch(Exception $e) {
			die($e->getMessage());
		}
		$this->username = $username; 
		
	}
	
	public function gen_minwiki() { // returnerer all nødvendig xhtml for å vise minwiki som en streng
		$this->_lastmoduler(); // alle moduler definert i mwconfig.php instansieres, se funksjonen
		
		// Kode under er midlertidig hack for å vise "et eller annet" på forsiden
		
		$mwpremenu .= '<div class="minwiki">'; 
		
		$mwoutput .= '<h1>MinWiki</h1>';
		$mwoutput .= 'Output fra minwiki! Navn: ' . $this->username . ' ID: ' . $this->getUserID() . '<br />';
		
		if(array_key_exists('page', $_REQUEST)) {
			$page = $_REQUEST['page'];
		} else {
			$page = 'feilmrapport';
		}
		
		if(array_key_exists('act', $_REQUEST)) {
			$act = $_REQUEST['act'];
		} else {
			$act = 'show';
		}
		
		$mwdisp = new mwdispatcher($page, $this->mwmod, $this, $act, NULL);
		$mwoutput .= '<h2>' . ucfirst($page) . '</h2>';
		$mwoutput .= '<div class="level2">';
		$mwoutput .= $mwdisp->dispatch();
		$mwoutput .= '</div>';
		
		
		$mwdisp = new mwdispatcher('testmodul', $this->mwmod, $this, 'show', NULL);
		$mwoutput .= '<h2>Testmodul</h2>';
		$mwoutput .= '<div class="level2">';
		$mwoutput .= $mwdisp->dispatch();
		$mwoutput .= '</div>';
		
		
		
		$mwoutput .= '</div>';
		
		$mwoutput = $mwpremenu . $this->_genMeny() . $mwoutput; // meny genereres til slutt for å gi moduler mest mulig
																// valgfrihet i hvilke menyitems som skal vises, men
		return $mwoutput;										// legges i starten av output.
		
		
	}
	
	private function _lastmoduler() {
		
		foreach (mwcfg::$moduler as $modulnavn) { 											// se mwconfig.php
			require_once 'moduler/' . $modulnavn . '/' . $modulnavn . '.mwmodul.php';		// f.eks. moduler/testmodul/testmodul.mwmodul.php
			$mwclassnavn = 'mwmodul_' . $modulnavn;											// modulens hoved class skal være f.eks. mwmodul_testmodul
			$this->mwmod[$modulnavn] = new $mwclassnavn($this->getUserID(), $this->sjekkAdgang($modulnavn)); // alle moduler får userid og accessnivå i forhold til modul @ instansiering
			// $this->mwmod holder alle lastede moduler
		}
	
	}
	
	public function getUserID($recursion = false) { // returnerer nåværende brukers interne userid, forsøker å opprette ny bruker om den ikke finnes
		
		if ($this->username == '') { die('Username not set on minwiki create'); } // skal i utgangspunktet aldri skje
			
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
		global $mwdb;
		if ($username == '') { die('Kan ikke sjekke id til tomt brukernavn'); }
		
		$result = $mwdb->assoc('SELECT id FROM internusers WHERE wikiname = ' . $mwdb->quote($this->username) . ' LIMIT 1;');
		
		if ($result[0]['id'] != 0) {
			return $result[0]['id']; // fant userid
		} else {
			return false;			// fant ikke userid
		}		
	}
	
	private function _createUser($username){ // forsøker å opprette ny userid for gitt username
		global $mwdb;
		if ($username == '') { die('Kan ikke opprette bruker med tomt brukernavn'); }
	
		$result = $mwdb->exec("INSERT INTO internusers (wikiname, createtime, lastlogin, isactive) VALUES (" . $mwdb->quote($username) . ", now(), now(), '1');");
		if ($result = 1) {
			msg("Opprettet ny brukerid for bruker: $username",1); //debug
			return true;
		} else {
			return false; // klarte ikke å opprette bruker - db classen klikker sikkert før vi får returnert false her...
		}
	
	}
	
	private function _genMeny() { // returnerer streng med nødvendig xhtml for å vise menyen
	
		$meny = new MenyitemCollection(); // collection-variabel som sendes til alle lastede moduler
		
		foreach ($this->mwmod as $mwmod) {
			$mwmod->registrer_meny($meny); // hver modul får collection by reference, slik at menyitems kan legges til
		}
	
		$output .= '<div class="toc">';
		$output .= '<div class="tocheader toctoggle" id="toc__header">Min Wiki Meny</div>';
		$output .= '<div id="toc__inside">';
		$output .= $this->_genMenyitem($meny, 1); // recursive funksjon som kaller seg selv dersom et item har underitems
		$output .= '</div>';
		$output .= '</div>';
		
		$output .= '';
		
		return $output;
	}
	
	private function _genMenyitem(MenyitemCollection &$col, $lvl) { // recursive funksjon som kaller seg selv dersom et item har underitems
		if ($lvl > 5) { return; }  // endres for å bestemme maks nivåer i meny
		$output .= '<ul class="toc">';
		foreach ($col as $menyitem) {
			$output .= '<li class="level$lvl">';
			$output .= '<div class="li"><span class="li"><a href="' . MW_LINK . $menyitem->getHref() . '" class="toc">' . $menyitem->getTekst() . '</a></span></div>';
			$output .= '</li>';
			if ($menyitem->hasChildren()) {
				$output .= $this->_genMenyitem($menyitem->getChildren(), $lvl+1);
			}
		}
		$output .= '</ul>';
		
		return $output;
	}
	
	public function sjekkAdgang($modul = '') { // returnerer en int (se definisjon på toppen av denne filen, samt dokuwikis auth.php
	
		$id = 'mwauth:' . $modul . ':info'; // siden mwauth:modulnavn:info må opprettes i dokuwiki for hver modul. ACL må settes opp mot denne siden.
		// echo 'Sjekker adgang til: ', $id, '. Adgangsnivå er: ', auth_quickaclcheck($id), '<br />'; // Debug.
		return auth_quickaclcheck($id);	
	
	}
	
	public function getDbGentime(){
		global $mwdb;
		
		return $mwdb->querytime;
	}
	
	public function getNumDbQueries(){
		global $mwdb;
		
		return $mwdb->num_queries;
	}
	

}
