<?php
if(!defined('MS_INC')) die();
define('MS_NYHET_LINK', MS_LINK . "&page=nyheter");
require_once('class.nyheter.nyhetcollection.php');
require_once('class.nyheter.msnyhet.php');
require_once('class.nyheter.omrade.php');
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
		$dispatcher->addActHandler('list', 'gen_nyheter_full', MSAUTH_1);
		$dispatcher->addActHandler('show', 'gen_nyheter_ulest', MSAUTH_1);
		$dispatcher->addActHandler('edit', 'gen_edit_nyhet', MSAUTH_3);
		$dispatcher->addActHandler('subedit', 'save_nyhet_changes', MSAUTH_3);
		$dispatcher->addActHandler('extupdate', 'update_nyhet_from_wp', MSAUTH_1);
		$dispatcher->addActHandler('addnyhet', 'gen_add_nyhet', MSAUTH_3);
		$dispatcher->addActHandler('lest', 'merk_nyhet_lest', MSAUTH_1);
		$dispatcher->addActHandler('lest', 'gen_nyheter_ulest', MSAUTH_1);
		$dispatcher->addActHandler('omradeadm', 'gen_omrade_admin', MSAUTH_ADMIN);
		
	}
	
	public function registrer_meny(MenyitemCollection &$meny) {
		$lvl = $this->_adgangsNiva;
	
		if ($lvl > MSAUTH_NONE) { 
			$toppmeny = new Menyitem('Nyheter','&page=nyheter');
			if (isset($this->_msmodulact)) { // Modul er lastet/vises
				$toppmeny->addChild(new Menyitem('Vis alle','&page=nyheter&act=list'));
				if ($lvl >= MSAUTH_3) {
					$toppmeny->addChild(new Menyitem('Opprett nyhet','&page=nyheter&act=addnyhet'));
				}
				if ($lvl = MSAUTH_ADMIN) {
					$toppmeny->addChild(new Menyitem('Områder','&page=nyheter&act=omradeadm'));
				}
			}
			$meny->addItem($toppmeny);
		}
			
	}
	
/********************************\
 *           HANDLERS           *
\********************************/

	public function gen_nyheter_full() {
		
        $objNyhetCol = NyhetFactory::getAlleNyheter();
        
        foreach ($objNyhetCol as $objNyhet) {
            $output .= NyhetGen::genFullNyhetViewOnly($objNyhet);
        }
        
		return $output;
		
	}
    
	public function gen_nyheter_ulest() {
		
        $objNyhetCol = NyhetFactory::getUlesteNyheterForBrukerId($this->_userID);
        
        foreach ($objNyhetCol as $objNyhet) {
            $output .= NyhetGen::genFullNyhetViewOnly($objNyhet);
        }
        
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
    
    public function gen_add_nyhet() {
    
        $objNyhet = new MsNyhet();
        
        return NyhetGen::genEdit($objNyhet);
    
    }
    
    public function save_nyhet_changes() {
        
        $nyhetid = $_REQUEST['nyhetid'];
        if ($nyhetid) {
            try{
                $objNyhet = NyhetFactory::getNyhetById($nyhetid);
            } catch (Exception $e) {
                msg('Klarte ikke å laste redigeringsverktøy for nyhet med id: ' . htmlspecialchars($nyhetid), -1);
                return false;
            }
        } else {
            $objNyhet = new MsNyhet();
        }
        
        $objNyhet->setTitle($_POST['nyhettitle']);
        if (!$objNyhet->isSaved()) {
            $objNyhet->setWikiPath('auto');
            $objNyhet->setType(1);
            $objNyhet->setViktighet(1);
            $objNyhet->setTilgang('ksusr');
        }
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
    
    public function merk_nyhet_lest() {
        $inputid = (int) $_REQUEST['nyhetid'];
        
        if ($inputid < 1 || $inputid > 9999999999) {
            throw new Exception('Ugyldig nyhetid gitt.');
        }
        
        try{
            $objNyhet = NyhetFactory::getNyhetById($inputid);
        } catch (Exception $e) {
            msg('Klarte ikke å laste redigeringsverktøy for nyhet med id: ' . htmlspecialchars($inputid), -1);
            return false;
        }
        
        ($objNyhet->merkLest($this->_userID))?
            msg("Merket nyhetid $inputid som lest", 1):
            msg("Klarte ikke å merke nyhetid $inputid som lest", -1);
        
    }
	
	public function gen_omrade_admin() {
		
		$arOmrader = NyhetOmrade::getOmrader('msnyheter', 255);
		return NyhetGen::genOmradeAdmin($arOmrader);
		
	}

}
