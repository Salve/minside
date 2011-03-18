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

require_once('interface.msmodul.php');
require_once('class.collectioniterator.php');
require_once('class.collection.php');
require_once('msconfig.php');
require_once('class.database.php');
require_once('class.msdispatcher.php');
require_once('class.actdispatcher.php');
require_once('class.erstatter.php');
require_once('class.menyitem.php');
require_once('class.menyitemcollection.php');
require_once('class.bruker.php');
require_once('class.brukercollection.php');
require_once('class.calendar.php');

class AdgangsException extends Exception { }

class MinSide { // denne classen instansieres og gen_minside() kjøres for å generere minside

const DEBUG = false;

private static $_objMinside;

private static $UserID; // settes til brukerens interne minside-id når og hvis den sjekkes
public static $username; // brukernavn som oppgis når script kalles, tilgjengelig når instansiert

private $_msmod; // array som holder alle lastede moduler som objekter
private $toc; // inneholder xhtml for ms-toc når den er generert

    public static function getInstance() {
        if(!isset(self::$_objMinside)) {
            self::$_objMinside = new self($_SERVER['REMOTE_USER']);
        }
        return self::$_objMinside;
    }
    
    private function __clone() { }
    
	private function __construct($username) { // kalles når class instansieres
	
		try {
			$GLOBALS['msdb'] = new Database(); // $msdb blir en globalt tilgjengelig db-class, se class.database.php
		} catch(Exception $e) {
			die($e->getMessage());
		}
		self::$username = $username; 
		
	}
	
	public function gen_minside() { // returnerer all nødvendig xhtml for å vise minside som en streng
		if (!($this->sjekkAdgang('vismeny') > MSAUTH_NONE)) {
			return '<h1>Ingen adgang</h1><p>Brukeren ' . self::$username . ' har ikke tilgang til å vise Min Side. ' . 
				'Kontakt en teamleder dersom du har spørsmål til dette.</p>';
		}
        
		$this->updateUserInfo();
		$this->_lastmoduler();
		
        $page = ($_REQUEST['page']) ?: 'nyheter';
        $act = ($_REQUEST['act']) ?: 'show';
		
        global $INFO;
        if($page == 'admin' && $INFO['isadmin']) {
            return $this->_genAdmin();
        }
        
        try {
            $msdisp = new msdispatcher($page, $this->_msmod, $this, $act, NULL);
            $msoutput = $msdisp->dispatch();
        } catch (Exception $e) {
            $msoutput =
                '<div class="mswarningbar"><strong>En feil har oppstått:</strong>
                <br /><br /><em>'. $e->getMessage() . '</em>
                <br /><br />Feil oppstod under generering av modulen: ' . $page . '</div><br />';
        }
											
		return '<div class="minside">' . $msoutput . '<div class="msclearer"></div></div>';
		
		
	}
    
    public function genModul($page, $act, $vars = array()) {
        // Når minside lastes "normalt" via do=minside, redirecter vi til login dersom
        // bruker ikke er logget inn. Det kan vi ikke gjøre her, derfor gir vi feilmelding i stedet.
        if(empty(self::$username)) return 'Kan ikke laste MinSide-modul "' . $page . '", bruker er ikke logget inn.';
        
        $this->_lastmoduler();
        $dispatcher = new msdispatcher($page, $this->_msmod, $this, $act, $vars);
        return $dispatcher->dispatch();
    }
	
	private function _lastmoduler() {
		if (!isset($this->_msmod)) {
            foreach (mscfg::$moduler as $modulnavn) { 											// se msconfig.php
                require_once 'moduler/' . $modulnavn . '/' . $modulnavn . '.msmodul.php';		// f.eks. moduler/testmodul/testmodul.msmodul.php
                $msclassnavn = 'msmodul_' . $modulnavn;											// modulens hoved class skal være f.eks. msmodul_testmodul
                $this->_msmod[$modulnavn] = new $msclassnavn($this->getUserID(), $this->sjekkAdgang($modulnavn)); // alle moduler får userid og accessnivå i forhold til modul @ instansiering
                // $this->_msmod holder alle lastede moduler
            }
        }
	
	}
	
	public static function getUserID($recursion = false) { // returnerer nåværende brukers interne userid, forsøker å opprette ny bruker om den ikke finnes
		
		if (self::$username == '') { die('Kritisk feil oppstått i MinSide: Bruker ikke logget inn.'); } // skal i utgangspunktet aldri skje
			
		if (isset(self::$UserID)) {
			return self::$UserID;			// dersom denne funksjonen allerede er kjørt er det bare å svare samme som sist gang
		} else {
			$lookupid = self::lookupUserID(self::$username); // lookupUserID sjekker db for username
			if ($lookupid === false) { // finner ikke username i db
				if ($recursion === true) {die('Klarer ikke å opprette brukerid');} // dersom denne funksjonen er kallt av seg selv gir vi opp nå. user er i så fall forsøkt opprettet, men finnes enda ikke i db.
				self::_createUser(self::$username); // forsøker å opprette bruker
				return self::getUserID(true); // kaller seg selv for å sjekke om bruker nå finnes i db
			} else { 					// fant username i db
				self::$UserID = $lookupid; // lagre resultat for neste gang funksjonen kalles
				return $lookupid;		
			}
		}

	}
	
	public static function lookupUserID($username) {  // sjekker db for gitt username, returnerer userid eller false
		global $msdb;
		if ($username == '') { die('Kan ikke sjekke id til tomt brukernavn'); }
		
		$result = $msdb->assoc('SELECT id FROM internusers WHERE wikiname = ' . $msdb->quote($username) . ' LIMIT 1;');
		
		if ($result[0]['id'] != 0) {
			return $result[0]['id']; // fant userid
		} else {
			return false;			// fant ikke userid
		}		
	}
	
	private static function _createUser($username){ // forsøker å opprette ny userid for gitt username
		global $msdb;
		if ($username == '') { die('Kan ikke opprette bruker med tomt brukernavn'); }
	
		$result = $msdb->exec("INSERT INTO internusers (wikiname, createtime, lastlogin, isactive) VALUES (" . $msdb->quote($username) . ", now(), now(), '1');");
		if ($result = 1) {
			if(MinSide::DEBUG) msg("Opprettet ny brukerid for bruker: $username",1); //debug
			return true;
		} else {
			return false; // klarte ikke å opprette bruker - db classen klikker sikkert før vi får returnert false her...
		}
	
	}
	
	public function getMeny($getObj = false) { // returnerer streng med nødvendig xhtml for å vise menyen
        if (isset($this->toc) && !$getObj) {
            return $this->toc; // cached streng med toc
        }
        
		$meny = new MenyitemCollection(); // collection-variabel som sendes til alle lastede moduler
		
		foreach ($this->_msmod as $msmod) {
			$msmod->registrer_meny($meny); // hver modul får collection by reference, slik at menyitems kan legges til
		}
        
        global $INFO;
        if($INFO['isadmin']) {
            $admintekst = 'Admin';
            if($_REQUEST['page'] == 'admin') {
                $admintekst = '<span class="selected">'.$admintekst.'</span>';
            }
            $meny->addItem(new Menyitem($admintekst,'&amp;page=admin'));
        }
        
		if ($getObj) return $meny;
	
		$output .= '<div class="mstoc">'."\n";
		$output .= '<div class="mstocheader">Min Side - Meny</div>'."\n";
		$output .= '<div class="meny">'."\n";
		$output .= $this->_genMenyitem($meny, 1); // recursive funksjon som kaller seg selv dersom et item har underitems
		$output .= '</div>'."\n";
		$output .= '</div>'."\n";
		
		$output .= '';
		
        $this->toc = $output;
        
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
    
    public static function getUsers() {
        global $msdb;
        $sql = "
            SELECT 
                id,
                wikiname,
                wikifullname, 
                wikiepost,
                createtime,
                isactive,
                wikigroups
            FROM 
                internusers
            ORDER BY
                wikifullname ASC
            ;";
		$data = $msdb->assoc($sql);
		
		$arUsers = array();
        
		if(is_array($data) && sizeof($data)) {
			foreach($data as $datum) {
                $groups = explode(',', $datum['wikigroups']);
				$user = array(
                    'id' => $datum['id'],
                    'wikiname' => $datum['wikiname'],
                    'wikifullname' => $datum['wikifullname'],
                    'wikiepost' => $datum['wikiepost'],
                    'createtime' => $datum['createtime'],
                    'isactive' => $datum['isactive'],
                    'wikigroups' => $groups
                );
                $arUsers[$datum['id']] = $user;
			}
		}
        if(MinSide::DEBUG) msg('Lastet inn ' . count($arUsers) . ' brukere fra MinSide-database.');
        return $arUsers;
    }
    
    public static function forceUserUpdate() {
        global $conf;
        global $msdb;
        
        // Laster dokuwiki auth backend
        if (@file_exists(DOKU_INC.'inc/auth/'.$conf['authtype'].'.class.php')) {
            require_once (DOKU_INC.'inc/auth/basic.class.php');
            require_once(DOKU_INC.'inc/auth/'.$conf['authtype'].'.class.php');

            $auth_class = "auth_".$conf['authtype'];
            if (class_exists($auth_class)) {
                $auth = new $auth_class();
            } else {
              nice_die($lang['authmodfailed']);
            }
        } else {
            nice_die($lang['authmodfailed']);
        }
        
        $minsideusers = self::getUsers();
        $dokuusers = $auth->retrieveUsers();
        if(self::DEBUG) msg('Lastet inn ' . count($dokuusers) . ' brukere fra DokuWiki');
        
        $arMatched = array();
        $arChanged = array();
        $arNew = array();
        $arGone = array();
        
        foreach($dokuusers as $username => $wikidata) {
            foreach($minsideusers as $userid => $minsidedata) {
                if($username == $minsidedata['wikiname']) {
                    // Sjekk for changes
                    if( $wikidata['name'] != $minsidedata['wikifullname'] ||
                        $wikidata['mail'] != $minsidedata['wikiepost'] ||
                        implode(',', $wikidata['grps']) != implode(',', $minsidedata['wikigroups']) ) {
                        if(self::DEBUG) msg('Bruker ' . $username . ' er endret', 2);
                        $arChanged[$username] = $wikidata;
                    }
                    // Merk minsideuser som matched, så vi kan sjekke hvilke som ikke finnes i DW.
                    $arMatched[] = $userid;
                    continue 2;
                }
            }
            // Ny bruker
            if(self::DEBUG) msg('Ny bruker: ' . $username, 2);
            $arNew[$username] = $wikidata;
        }
        
        // Sjekk for slettede dokuusers
        foreach($minsideusers as $userid => $minsidedata) {
            if($minsidedata['isactive'] && !in_array($userid, $arMatched)) {
                if(self::DEBUG) msg('Slettet bruker: ' . $minsidedata['wikiname'], 2);
                $arGone[$userid] = $minsidedata;
            }
        }
        
        $arSql = array();
        
        // Behandle slettede brukere
        if(!empty($arGone)) {
            if(self::DEBUG) msg('Genererer SQL for deaktivering av ' . count($arGone) . ' slettede brukere');
            foreach($arGone as $userid => $minsidedata) {
                $safeid = $msdb->quote($userid);
                
                $arSql[] = "UPDATE internusers SET
                                wikigroups = '',
                                modifytime = now(),
                                isactive = '0'
                            WHERE id = $safeid
                            LIMIT 1
                        ;";
            }
        }
        
        // Behandle nye brukere
        if(!empty($arNew)) {
            if(self::DEBUG) msg('Genererer SQL for opprettelse av ' . count($arNew) . ' nye brukere');
            foreach($arNew as $username => $wikidata) {
                $safewikiname = $msdb->quote($username);
                $safewikifullname = $msdb->quote($wikidata['name']);
                $safewikiepost = $msdb->quote($wikidata['mail']);
                $safewikigroups = $msdb->quote(implode(',', $wikidata['grps']));
                
                $arSql[] = "INSERT INTO internusers SET
                                wikiname = $safewikiname,
                                wikifullname = $safewikifullname,
                                wikiepost = $safewikiepost,
                                wikigroups = $safewikigroups,
                                createtime = now(),
                                modifytime = now(),
                                isactive = '1'
                        ;";
            }
        }
        
        // Behandle endrede brukere
        if(!empty($arChanged)) {
            if(self::DEBUG) msg('Genererer SQL for korrigering av ' . count($arChanged) . ' endrede brukere');
            foreach($arChanged as $username => $wikidata) {
                $safewikiname = $msdb->quote($username);
                $safewikifullname = $msdb->quote($wikidata['name']);
                $safewikiepost = $msdb->quote($wikidata['mail']);
                $safewikigroups = $msdb->quote(implode(',', $wikidata['grps']));
                
                $arSql[] = "UPDATE internusers SET
                                wikifullname = $safewikifullname,
                                wikiepost = $safewikiepost,
                                wikigroups = $safewikigroups,
                                modifytime = now(),
                                isactive = '1'
                            WHERE wikiname = $safewikiname
                            LIMIT 1
                        ;";
            }
        }
        
        $altok = true;
        if(!empty($arSql)) {
            if(self::DEBUG) msg('User update: Kjører ' . count($arSql) . ' sql-queries.');
            foreach($arSql as $sql) {
                try{
                    $msdb->exec($sql, true);
                } catch(Exception $e) {
                    $altok = false;
                    if(self::DEBUG) msg('Sql feilet: ' . $e->getMessage(), -1);
                    continue;
                }
            }
        } else {
            if(self::DEBUG) msg('User update: Ingen endringer');
        }
        
        return $altok;
    }
    
    private static function _genAdmin() {
        if($_POST['act'] == 'do_force_user_update') {
            if(self::DEBUG) msg('ForceUserUpdate requested, starting...');
            if(self::forceUserUpdate()) {
                msg('Brukersynkronisering mellom DokuWiki og MinSide var vellykket.', 1);
            } elseif (self::DEBUG) {
                msg('Brukersynkronisering mellom DokuWiki og MinSide feilet.', -1);
            } else {
                msg('Brukersynkronisering mellom DokuWiki og MinSide feilet, skru på debug for detaljer.', -1);
            }
        }
        
        return '
            <div class="minside">
                <h1>MinSide - Admin</h1>
                <div class="level1">
                    <h2>Brukersynkronisering</h2>
                    <div class="level2">
                        Denne funksjonen synkroniserer MinSides brukerdatabase med DokuWiki, for alle brukere.<br />
                        Data synkroniseres normalt kun når en bruker besøker en MinSide-modul direkte (ikke lastet via DokuWiki-syntax, eller i template).<br />
                        <br />
                        Brukere som ligger i MinSide, men er fjernet helt fra DokuWiki vil miste alle tilganger, og markeres som inaktive, men fremdeles eksistere i databasen. Dette grunnet knytninger mot brukeres handlinger, f.eks. nyheter. Det anbefales at tilganger fjernes i DokuWiki, i stedet for at bruker slettes. Brukere som ikke finnes i MinSide-databasen vil bli opprettet der.<br />
                        <br />
                        <form action="' . MS_LINK . '&amp;page=admin" method="post">
                            <input type="hidden" name="act" value="do_force_user_update" />
                            <input type="submit" value="Synkoniser data" class="button" />
                        </form>
                    </div>
                </div>
            </div>
        ';
    }
}
