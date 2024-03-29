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
            'author' => 'Salve Spinnangr, Njål Kollbotn',
            'email'  => 'salve.spinnangr@gmail.com',
            'date'   => '2010-08-07',
            'name'   => 'Min Side - acthandler',
            'desc'   => 'Min Side er en brukerspesifik side, samt et sett verktøy laget for bruk i Lyse AS.',
            'url'    => ''
        );
    }
     
    // Registrer event handlers
    function register(&$controller) {
        $controller->register_hook('ACTION_ACT_PREPROCESS', 'BEFORE', $this, 'handleActPreprocess');
		$controller->register_hook('TPL_ACT_UNKNOWN', 'BEFORE', $this, 'handleTplActUnknown');
        
        // For nyheter - gjør nødvendige oppdateringer før/etter endringer
        $controller->register_hook('IO_WIKIPAGE_WRITE', 'BEFORE', $this, 'handlePreWikiWrite');
        $controller->register_hook('IO_WIKIPAGE_WRITE', 'AFTER', $this, 'handlePostWikiWrite');
		
        // Generer og viser nyhet når bruker forsøker å se nyhet direkte i dw
        $controller->register_hook('TPL_CONTENT_DISPLAY', 'BEFORE', $this, 'handleTplContentDisplay');
        
        // Kontroller søkeresultat for upubliserte nyheter
        $controller->register_hook('SEARCH_QUERY_FULLPAGE', 'AFTER', $this, 'handleSearchQueryFullpage');
        $controller->register_hook('SEARCH_QUERY_PAGELOOKUP', 'AFTER', $this, 'handleSearchQueryPagelookup');
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
			$objMinSide = MinSide::getInstance();

			print $objMinSide->gen_minside();

			if(MinSide::DEBUG) {
                $ms_endtime = microtime(true);
                $ms_gentime = $ms_endtime - $ms_starttime;
                $db_gentime = $objMinSide->getDbGentime();
                $db_percent = round($db_gentime / $ms_gentime * 100);
                $ms_gentime = round($ms_gentime,2);
                $db_gentime = round($db_gentime,4);
                $db_queries = $objMinSide->getNumDbQueries();
                print '<br /><br /><br />';
                $sGentime = "Min Side generert på $ms_gentime sec! Totalt $db_queries SQL-spørring" . (($db_queries == 1) ? '' : 'er') . " tok $db_gentime sec ($db_percent%)";
                msg($sGentime);
            }
			
        }
    }
    
    function handlePreWikiWrite($event, $param) {
        // Vi bryr oss kun om namespacet :msnyheter:
        if (substr($event->data[1], 0, 9) != 'msnyheter') return false;
        
        // Denne eventen triggres også når MinSide skriver til wiki
        // viktig at vi ikke stopper denne
        if ($GLOBALS['ms_writing_to_dw'] === true) return false;
        
        // Tom side skrives, forsøk på å slette nyhet!
        if (empty($event->data[0][1])) {
            die("Oppdaget førsøk på å slette nyhet gjennom DokuWiki\n<br />Vennligst benytt MinSide til å slette nyheter.");
        }
        
        // Redigering av eksisterende nyheter håndteres i handlePostWikiWrite
        if (@file_exists($event->data[0][0])) {
            return false;
        }
        
        // Ved ny side vil rev(ision) være false
        if ($event->data[3]) return false;
        
        // Hvis vi kommer hit er dette opprettelse av en ny side
        // i :msnyheter:-navnerommet.
        
        // Vi lar admins opprette sider, eneste måte å lage nye namespaces på
        // (alternativet var å kode eget adminpanel for dette...)
        global $INFO;
        if ($INFO['isadmin']) return false;
        
        die("Oppdaget førsøk på å opprette nyhet gjennom DokuWiki\n<br />Vennligst benytt MinSide til å opprette nyheter.");
    }
    
    function handlePostWikiWrite($event, $param) {
        // Vi bryr oss kun om namespacet :msnyheter:
        if (substr($event->data[1], 0, 9) != 'msnyheter') return false;
        
        // Denne eventen triggres også når MinSide skriver til wiki
        // viktig at vi ikke behandler dette, vil loope evig...
        if ($GLOBALS['ms_writing_to_dw'] === true) return false;
        
        /* Sjekk at dette er skriving av ny utgave
         * og ikke flytting av den gamle til attic.
         */
        
        // data[3] inneholder rev(ision) og er false når det skrives til
        // siste/aktive utgave av siden.
        if ($event->data[3]) return false;
        
        // Hvis vi kommer hit er ny utgave av nyhet skrevet til disk
        // og vi må oppdatere database.
        require_once(DOKU_PLUGIN.'minside/minside/minside.php');
        $objMinSide = MinSide::getInstance();
        $data = $event->data;
        $res = $objMinSide->genModul('nyheter', 'extupdate', $data);
        
        if ($res) {
            msg('MinSide har oppdatert nyhetsdatabase i henhold til ekstern redigering', 1);
            return true;
        } else {
            msg('MinSide har oppdaget ekstern redigering av nyhet, men klarte IKKE å oppdatere database.', -1);
            return false;
        }
      
    }
    
    function handleTplContentDisplay(&$event, $param) {
        global $INFO;
        global $ACT;
        if(substr($INFO['id'], 0, 10) != 'msnyheter:') return false;
        if($ACT != 'show') return false;
        if ($INFO['rev'] != false) return false;
        
        
        require_once(DOKU_PLUGIN.'minside/minside/minside.php');
        try {
            $objMinSide = MinSide::getInstance();
            $event->data = $objMinSide->genModul('nyheter', 'extview', $INFO['id']);
            return true;
        } catch (Exception $e) {
            msg('Klarte ikke å vise denne nyheten gjennom MinSide: ' . $e->getMessage(), -1);
            return false;
        }
        
    }
    
    function handleSearchQueryPagelookup(&$event, $param) {
        // Sjekker om $ACT = search, for å ungå å behandle data når det søkes via js/ajax
        // environment er da ikke på plass og vi får exception text i popup
        global $ACT;
        if($ACT != 'search') return false;
        
        $nyhet_hits = array();
        foreach($event->result as $key => $info) {
            // Funksjonen returnerer int(0) på match
            if((strlen($key) > 10) && (substr_compare($key, 'msnyheter:', 0, 10, true) === 0)) {
                $nyhet_hits[] = $key;
                // Sørg for at nyhet-hits ikke vises i normalt resultat
                unset($event->result[$key]);
            }
        }
        
        if(empty($nyhet_hits)) return;
        
        require_once(DOKU_PLUGIN.'minside/minside/minside.php');
        try {
            $objMinSide = MinSide::getInstance();
            $output = $objMinSide->genModul('nyheter', 'searchpagelookup', $nyhet_hits);
        } catch (Exception $e) {
            if(MinSide::DEBUG) msg('Klarte ikke å laste pagelookup resultater fra MinSide::Nyheter: ' . $e->getMessage(), -1);
            return;
        }
        
        if(empty($output)) return;
        
        print '<div class="search_quickresult"><h3>Matchende nyhetsnavn:</h3><div class="level1">';
        print $output;
        print '</div><div class="clearer">&nbsp;</div></div>';
        
    }
    
    function handleSearchQueryFullpage(&$event, $param) {
    
        $nyhet_hits = array();
        foreach($event->result as $id => $num_hits) {
            // Funksjonen returnerer int(0) på match
            if(substr_compare($id, 'msnyheter:', 0, 10, true) === 0) {
                $nyhet_hits[] = $id;
                // Sørg for at nyhet-hits ikke vises i normalt resultat
                unset($event->result[$id]);
            }
        }
        
        if(empty($nyhet_hits)) return;
        
        require_once(DOKU_PLUGIN.'minside/minside/minside.php');
        try {
            $objMinSide = MinSide::getInstance();
            $output = $objMinSide->genModul('nyheter', 'searchfullpage', $nyhet_hits);
        } catch (Exception $e) {
            if(MinSide::DEBUG) msg('Klarte ikke å laste pagelookup resultater fra MinSide::Nyheter: ' . $e->getMessage(), -1);
            return;
        }
        
        if(empty($output)) return;
        
        print '<div class="search_quickresult"><h3>Treff i nyhetsinnhold:</h3><div class="level1">';
        print $output;
        print '</div><div class="clearer">&nbsp;</div></div>';
    }
    
}
