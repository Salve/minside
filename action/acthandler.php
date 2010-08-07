<?php
/**
 * @author     Salve Spinnangr <salve.spinnangr@gmail.com>
 *
 * Min Side - DokuWiki plugin
 * 
 * Denne filen håndterer ?do= actionen "minside"
 *
 */
   
// must be run within DokuWiki
if(!defined('DOKU_INC')) die();
if (!defined('DOKU_LF')) define('DOKU_LF', "\n");
if (!defined('DOKU_TAB')) define('DOKU_TAB', "\t");
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'action.php');
   
class action_plugin_minside_acthandler extends DokuWiki_Action_Plugin {
     
	// DokuWiki krever info, vises i admin panel
    function getInfo() {
        return array(
            'author' => 'Salve Spinnangr',
            'email'  => 'salve.spinnangr@gmail.com',
            'date'   => '2010-05-15',
            'name'   => 'Min Side - acthandler',
            'desc'   => 'Min Side er en brukerspesifik side, samt et sett verktøy laget for bruk i Lyse AS. '.
						'Denne klassen er bindeledd mellom Min Side-scriptet og DokuWiki. Når en DokuWiki url '.
						'som inneholder do=minside fanges opp, sørger denne klassen for å generere en dynamisk side, tilpasset brukeren.',
            'url'    => 'http://79.161.213.78/doku.php?id=hjelp:minside',
        );
    }
     
    // Registrer event handlers
    function register(&$controller) {
        $controller->register_hook('DOKUWIKI_STARTED', 'AFTER', $this, 'handleDokiWikiStarted');
        $controller->register_hook('ACTION_ACT_PREPROCESS', 'BEFORE', $this, 'handleActPreprocess');
		$controller->register_hook('TPL_ACT_UNKNOWN', 'BEFORE', $this, 'handleTplActUnknown');
    }
    
    /**
     * Handler for DOKUWIKI_STARTED, genererer sidebar her 
     */
    function handleDokiWikiStarted(&$event, $param) {
        global $INFO;
        ob_start();
        var_dump($INFO);
        $res = ob_get_contents();
        ob_end_clean();
        msg($res);
        
        require_once(DOKU_PLUGIN.'minside/minside/minside.php');
        $objMinSide = MinSide::getInstance();
        $data['includemstoc'] = false;
        $res = $objMinSide->genModul('sidebar', 'show', $data);
        
        msg($res);
    }
     
    /**
     * Handler for ACTION_ACT_PREPROCESS, dersom do-action er 'minside' 
     */
    function handleActPreprocess(&$event, $param) {
        global $INFO;
		$act = $event->data;
        if (is_array($act))
            list($act) = array_keys($act);
     
        if ($act != 'minside') {
			if (!(strrpos($INFO['id'],'MSAUTH') === false) && $INFO['isadmin'] == false) {
				die('Kun administratorer har tilgang til MSAUTH-sider');
				$event->preventDefault();
				$event->stopPropagation();
			} else {
				return; // Actions som ikke er 'minside' håndteres ikke.
			}
		
		}
     
        if (empty($_SERVER['REMOTE_USER'])) {
            $event->data = 'login';
            return; // Hvis action er 'minside', men brukeren ikke er logget inn, endres action til 'login', denne vil bli behandlet av default handler.
        }
     
        // Hvis vi kommer hit er action 'minside' og brukeren er logget inn. Vi stopper videre behandling og aksepterer denne.
        $event->preventDefault();
        $event->stopPropagation();
    }
     
    /**
     * Handler for TPL_ACT_UNKNOWN, do-action er 'minside'
     * Gjør nødvendige kall for å starte generering av brukerspesifik side.
     */
    function handleTplActUnknown(&$event, $param) {
        if (($event->data == 'minside') && (auth_quickaclcheck('MSAUTH:vismeny:info') >= AUTH_READ)) {
            $event->preventDefault(); // Hindrer feilmelding om ukjent action variabel i å vises.
            $event->stopPropagation(); // Hindrer eventuelle andre plugins fra å behandle eventen (kan ikke garantere at andre plugins ikke får eventen først)
			
			$ms_starttime = microtime(true);

			require_once(DOKU_PLUGIN.'minside/minside/minside.php');
			
			$msgen = new minside($_SERVER['REMOTE_USER']);
			print $msgen->gen_minside();
			
			$ms_endtime = microtime(true);
			$ms_gentime = $ms_endtime - $ms_starttime;
			$db_gentime = $msgen->getDbGentime();
			$db_percent = round($db_gentime / $ms_gentime * 100);
			$ms_gentime = round($ms_gentime,2);
			$db_gentime = round($db_gentime,4);
			$db_queries = $msgen->getNumDbQueries();
			print '<br /><br /><br />';
			$sGentime = "Min Side generert på $ms_gentime sec! Totalt $db_queries SQL-spørring" . (($db_queries == 1) ? '' : 'er') . " tok $db_gentime sec ($db_percent%)";
			msg($sGentime);
			
			
        }
    }
    
    }
	