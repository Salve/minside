<?php
if(!defined('MS_INC')) die();
define('MS_NYHET_LINK', MS_LINK . "&page=nyheter");
require_once('class.nyheter.nyhetcollection.php');
require_once('class.nyheter.msnyhet.php');
require_once('class.nyheter.nyhetfactory.php');
require_once('class.nyheter.nyhetgen.php');

class msmodul_nyheter implements msmodul {

	public $dispatcher;
	
	private $debug = true;
	private $_msmodulact;
	private $_msmodulvars;
	private $_userID;
	private $_adgangsNiva; // int som angir innlogget brukers rettigheter for denne modulen, se toppen av minside.php for mulige verdier.
	
	public function __construct($UserID, $AdgangsNiva) {
		$this->_userID = $UserID;
		$this->_adgangsNiva = $AdgangsNiva;
	}
	
	public function gen_msmodul($act, $vars){
		$this->_msmodulact = $act;
		$this->_msmodulvars = $vars;

		// Opprett ny dispatcher
		$this->dispatcher = new ActDispatcher($this, $this->_adgangsNiva);
		// Funksjon som definerer handles for act-values
		$this->_setHandlers($this->dispatcher);
		
		// Dispatch $act, dispatcher returnerer output
		return $this->dispatcher->dispatch($act);

	}
	
	private function _setHandlers(&$dispatcher) {
		$dispatcher->addActHandler('show', 'gen_nyheter_full', MSAUTH_1);
		$dispatcher->addActHandler('edit', 'gen_edit_nyhet', MSAUTH_3);
		$dispatcher->addActHandler('subedit', 'save_nyhet_changes', MSAUTH_3);
		$dispatcher->addActHandler('extupdate', 'update_nyhet_from_wp', MSAUTH_1);
		
	}
	
	public function registrer_meny(MenyitemCollection &$meny) {
		$lvl = $this->_adgangsNiva;
	
		if ($lvl > MSAUTH_NONE) { 
			$toppmeny = new Menyitem('Nyheter','&page=nyheter');
			/*if (isset($this->_msmodulact)) { // Modul er lastet/vises
				if ($lvl == MSAUTH_ADMIN) {
					$toppmeny->addChild(new Menyitem('Nyhetsadmin','&page=nyheter&act=admin'));
				}
			}*/
			$meny->addItem($toppmeny);
		}
			
	}
	
/********************************\
 *           HANDLERS           *
\********************************/

	public function gen_nyheter_full() {
		
        $objNyhet = NyhetFactory::getNyhetById(1);
        
        $output .= NyhetGen::genFullNyhetViewOnly($objNyhet);
        
        //$output .= p_wiki_xhtml($objNyhet->getWikiPath(), '', false);
        
		return $output;
		
	}
    
    public function gen_edit_nyhet() {
    
        $nyhetid = $_REQUEST['nyhetid'];
        try{
            $objNyhet = NyhetFactory::getNyhetById($nyhetid);
        } catch (Exception $e) {
            msg('Klarte ikke å laste redigeringsverktøy for nyhet med id: ' . htmlspecialchars($nyhetid), -1);
            return false;
        }
        
        return NyhetGen::genEdit($objNyhet);
    
    }
    
    public function save_nyhet_changes() {
        
        $nyhetid = $_REQUEST['nyhetid'];
        try{
            $objNyhet = NyhetFactory::getNyhetById($nyhetid);
        } catch (Exception $e) {
            msg('Klarte ikke å laste redigeringsverktøy for nyhet med id: ' . htmlspecialchars($nyhetid), -1);
            return false;
        }
        
        $objNyhet->setTitle($_POST['nyhettitle']);
        $objNyhet->setWikiTekst($_POST['wikitext']);        
        
        if ($objNyhet->hasUnsavedChanges()) {
            $objNyhet->update_db();
        } else {
            msg('Ingen endringer, oppdaterer ikke db.');
        }
        
        return NyhetGen::genFullNyhetViewOnly($objNyhet);
    }
    
    public function update_nyhet_from_wp() {
        msg('Oppdaterer nyhet basert på ekstern redigering');
        
        $data = $this->_msmodulvars;
        
        $wikipath = $data[1] . ':' . $data[2];
        $wikitext = $data[0][1];
        
        $objNyhet = NyhetFactory::getNyhetByWikiPath($wikipath);
        $objNyhet->setWikiTekst($wikitext);

        return $objNyhet->update_db();
    }

}
