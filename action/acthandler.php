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
		
		// Ajax handle for img-add på nyheter
		$controller->register_hook('AJAX_CALL_UNKNOWN', 'BEFORE',  $this, 'handleAjax');
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
        
        die("Oppdaget førsøk på å opprette nyhet gjennom DokuWiki\n<br />Vennligst benytt MinSide til å opprette nyheter.");
    }
    
    function handlePostWikiWrite($event, $param) {
        // Vi bryr oss kun om namespacet :msnyheter:
        if (substr($event->data[1], 0, 9) != 'msnyheter') return false;
        
        // Denne eventen triggres også når MinSide skriver til wiki
        // viktig at vi ikke behandler dette, vil loope evig...
        if ($GLOBALS['ms_writing_to_dw'] === true) return false;
        
        /* Opprettelse av nye sider er blokkert i handlePreWikiWrite
         * vi trenger kun å sjekke at dette er skriving av ny utgave
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
	
	function handleAjax($event, $param) {
		/*	Returnerer output direkte til ajax-caller
		 *	Må printe en av følgende:
		 *	'msok' dersom input er ok, og lagring gikk fint
		 *	'mserror' dersom noe gikk galt, bruker får en feilmelding popup
		 */
	
		if ($event->data != 'msimgsub') return;
		
		require_once(DOKU_PLUGIN.'minside/minside/minside.php');
        $objMinSide = MinSide::getInstance();
		
		$data = array(
			'nyhetid' => $_POST['nyhetid'],
			'rawimgpath' => $_POST['q']
		);
		$res = $objMinSide->genModul('nyheter', 'ajaxsetimgpath', $data);
		
		header('Content-Type: text/html; charset=utf-8');
		print $res;
		$fil = 'ajaxlogg.txt';
		$fh = fopen($fil, 'a') or die();
		$data = 'Value: ' . $_POST['q'] . ' NyhetID: ' . $_POST['nyhetid'] . "Resultat: $res\r\n";
		fwrite($fh, $data);
		fclose($fh);
		$event->preventDefault();
		return;
	}
    
}
