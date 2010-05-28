<?php
/**
 * @author     Salve Spinnangr <salve.spinnangr@gmail.com>
 *
 * MinWiki - DokuWiki plugin
 * 
 * Denne filen håndterer ?do= actionen "minwiki"
 *
 */
   
// must be run within DokuWiki
if(!defined('DOKU_INC')) die();
if (!defined('DOKU_LF')) define('DOKU_LF', "\n");
if (!defined('DOKU_TAB')) define('DOKU_TAB', "\t");
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'action.php');
   
class action_plugin_minwiki_acthandler extends DokuWiki_Action_Plugin {
     
	// DokuWiki krever info, vises i admin panel
    function getInfo() {
        return array(
            'author' => 'Salve Spinnangr',
            'email'  => 'salve.spinnangr@gmail.com',
            'date'   => '2010-05-15',
            'name'   => 'MinWiki - acthandler',
            'desc'   => 'MinWiki er en brukerspesifik side, samt et sett verktøy laget for bruk i Lyse AS. '.
						'Denne klassen er bindeledd mellom MinWiki-scriptet og DokuWiki. Når en DokuWiki url '.
						'som inneholder do=minwiki fanges opp, sørger denne klassen for å generere en dynamisk side, tilpasset brukeren.',
            'url'    => 'http://79.161.213.78/doku.php?id=hjelp:minwiki',
        );
    }
     
    // Registrer event handlers
    function register(&$controller) {
        $controller->register_hook('ACTION_ACT_PREPROCESS', 'BEFORE', $this, 'handleActPreprocess');
		$controller->register_hook('TPL_ACT_UNKNOWN', 'BEFORE', $this, 'handleTplActUnknown');
    }
     
    /**
     * Handler for ACTION_ACT_PREPROCESS, dersom do-action er 'minwiki' 
     */
    function handleActPreprocess(&$event, $param) {
        global $INFO;
		$act = $event->data;
        if (is_array($act))
            list($act) = array_keys($act);
     
        if ($act != 'minwiki') {
			if (!(strrpos($INFO['id'],'mwauth') === false) && $INFO['isadmin'] == false) {
				die('Kun administratorer har tilgang til mwauth-sider');
				$event->preventDefault();
				$event->stopPropagation();
			} else {
				return; // Actions som ikke er 'minwiki' håndteres ikke.
			}
		
		}
     
        if (empty($_SERVER['REMOTE_USER'])) {
            $event->data = 'login';
            return; // Hvis action er 'minwiki', men brukeren ikke er logget inn, endres action til 'login', denne vil bli behandlet av default handler.
        }
     
        // Hvis vi kommer hit er action 'minwiki' og brukeren er logget inn. Vi stopper videre behandling og aksepterer denne.
        $event->preventDefault();
        $event->stopPropagation();
    }
     
    /**
     * Handler for TPL_ACT_UNKNOWN, do-action er 'minwiki'
     * Gjør nødvendige kall for å starte generering av brukerspesifik side.
     */
    function handleTplActUnknown(&$event, $param) {
        if (($event->data == 'minwiki') && (auth_quickaclcheck('mwauth:vismeny:info') >= AUTH_READ)) {
            $event->preventDefault(); // Hindrer feilmelding om ukjent action variabel i å vises.
            $event->stopPropagation(); // Hindrer eventuelle andre plugins fra å behandle eventen (kan ikke garantere at andre plugins ikke får eventen først)
			
			$mw_starttime = microtime(true);

			require_once(DOKU_PLUGIN.'minwiki/minwiki/minwiki.php');
			
			$mwgen = new minwiki($_SERVER['REMOTE_USER']);
			print $mwgen->gen_minwiki();
			
			$mw_endtime = microtime(true);
			$mw_gentime = $mw_endtime - $mw_starttime;
			$db_gentime = $mwgen->getDbGentime();
			$db_percent = round($db_gentime / $mw_gentime * 100);
			$mw_gentime = round($mw_gentime,2);
			$db_gentime = round($db_gentime,4);
			$db_queries = $mwgen->getNumDbQueries();
			print '<br /><br /><br />';
			$sGentime = "MinWiki generert på $mw_gentime sec! Totalt $db_queries SQL-spørring" . (($db_queries == 1) ? '' : 'er') . " tok $db_gentime sec ($db_percent%)";
			msg($sGentime);
			
			
        }
    }
    
    }
	