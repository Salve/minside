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
        $act = $event->data;
        if (is_array($act))
            list($act) = array_keys($act);
     
        if ($act != 'minwiki')
            return; // Actions som ikke er 'minwiki' håndteres ikke.
     
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
        if ($event->data == 'minwiki') {
            $event->preventDefault(); // Hindrer feilmelding om ukjent action variabel i å vises.
            $event->stopPropagation(); // Hindrer eventuelle andre plugins fra å behandle eventen (kan ikke garantere at andre plugins får eventen først)
			
			require_once(DOKU_PLUGIN.'minwiki/minwiki/minwiki.php');
			
			$mwgen = new minwiki;
			print $mwgen->gen_minwiki();
			
			
        }
    }
    
    }