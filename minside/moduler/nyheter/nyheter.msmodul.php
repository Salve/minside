<?php
if(!defined('MS_INC')) die();
define('MS_NYHET_LINK', MS_LINK . "&page=nyheter");
require_once('class.nyheter.nyhetcollection.php');
require_once('class.nyheter.msnyhet.php');
require_once('class.nyheter.omrade.php');
require_once('class.nyheter.nyhetfactory.php');
require_once('class.nyheter.nyhetgen.php');
require_once(DOKU_INC.'inc/search.php');

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
		$dispatcher->addActHandler('upub', 'gen_nyheter_upub', MSAUTH_2);
		$dispatcher->addActHandler('edit', 'gen_edit_nyhet', MSAUTH_2);
        $dispatcher->addActHandler('extview', 'gen_ext_view', MSAUTH_NONE);
		$dispatcher->addActHandler('slett', 'slett_nyhet', MSAUTH_2);
		$dispatcher->addActHandler('slett', 'gen_nyheter_full', MSAUTH_1);
		$dispatcher->addActHandler('subedit', 'save_nyhet_changes', MSAUTH_2);
		$dispatcher->addActHandler('extupdate', 'update_nyhet_from_wp', MSAUTH_NONE);
		$dispatcher->addActHandler('addnyhet', 'gen_add_nyhet', MSAUTH_3);
		$dispatcher->addActHandler('lest', 'merk_nyhet_lest', MSAUTH_1);
		$dispatcher->addActHandler('lest', 'gen_nyheter_ulest', MSAUTH_1);
		$dispatcher->addActHandler('allelest', 'merk_alle_lest', MSAUTH_1);
		$dispatcher->addActHandler('allelest', 'gen_nyheter_ulest', MSAUTH_1);
		$dispatcher->addActHandler('omradeadm', 'gen_omrade_admin', MSAUTH_ADMIN);
		$dispatcher->addActHandler('showdel', 'gen_nyheter_del', MSAUTH_5);
		$dispatcher->addActHandler('restore', 'restore_nyhet', MSAUTH_5);
		$dispatcher->addActHandler('restore', 'gen_nyheter_del', MSAUTH_5);
		$dispatcher->addActHandler('permslett', 'permslett_nyhet', MSAUTH_5);
		$dispatcher->addActHandler('permslett', 'gen_nyheter_del', MSAUTH_5);
		$dispatcher->addActHandler('checkpublished', 'check_published', MSAUTH_NONE);
		
	}
	
	public function registrer_meny(MenyitemCollection &$meny) {
		$lvl = $this->_adgangsNiva;
	
		if ($lvl > MSAUTH_NONE) { 
			$toppmeny = new Menyitem('Nyheter','&page=nyheter');
			if (isset($this->_msmodulact)) { // Modul er lastet/vises
				$toppmeny->addChild(new Menyitem('Vis alle','&page=nyheter&act=list'));
				if ($lvl >= MSAUTH_2) {
					$toppmeny->addChild(new Menyitem('Upubliserte nyheter','&page=nyheter&act=upub'));
				}
				if ($lvl >= MSAUTH_5) {
					$toppmeny->addChild(new Menyitem('Slettede nyheter','&page=nyheter&act=showdel'));
				}
                if ($lvl >= MSAUTH_3) {
					$toppmeny->addChild(new Menyitem('Opprett nyhet','&page=nyheter&act=addnyhet'));
				}
			}
			$meny->addItem($toppmeny);
		}
			
	}
	
/********************************\
 *           HANDLERS           *
\********************************/

	public function gen_nyheter_full() {
		
        $objNyhetCol = NyhetFactory::getAllePubliserteNyheter();
		
		if ($objNyhetCol->length() === 0) {
			return NyhetGen::genIngenNyheter();
		}
        
        foreach ($objNyhetCol as $objNyhet) {
            $nyhet = NyhetGen::genFullNyhet($objNyhet);
            if ($objNyhet->isSticky()) {
                $sticky .= $nyhet;
            } else {
                $normal .= $nyhet;
            }
        }
        
		return $sticky . $normal;
		
	}
    
	public function gen_nyheter_ulest() {
		
        $objNyhetCol = NyhetFactory::getUlesteNyheterForBrukerId($this->_userID);
        
		if ($objNyhetCol->length() === 0) {
			return NyhetGen::genIngenNyheter();
		}
		
        foreach ($objNyhetCol as $objNyhet) {
            $output .= NyhetGen::genFullNyhet($objNyhet, array('lest'));
        }
        
        $merkallelest = '<p><a href="'.MS_NYHET_LINK.'&act=allelest">Merk alle nyheter lest</a></p>';
        
		return $merkallelest . $output;
		
	}
    
    public function gen_ext_view() {
        $inputpath = $this->_msmodulvars;
        
        try{
            $objNyhet = NyhetFactory::getNyhetByWikiPath($inputpath);
        } catch (Exception $e) {
            msg('Klarte ikke å laste redigeringsverktøy for nyhet med bane: ' . htmlspecialchars($inputpath), -1);
            return false;
        }
        
        if ($objNyhet->isDeleted()) {
            msg('Kan ikke vise nyhet: Nyhet er slettet.', -1);
            return false;
        }
        if (!$objNyhet->isPublished()) {
            msg('Kan ikke vise nyhet: Nyhet er ikke publisert.', -1);
            return false;
        }
        
        return '<div class="minside"><p>' . NyhetGen::genFullNyhet($objNyhet) . '</p></div>';
    }
    
    public function gen_nyheter_upub() {
        $objNyhetCol = NyhetFactory::getUpubliserteNyheter();
        
        if ($objNyhetCol->length() === 0) {
			return NyhetGen::genIngenNyheter('Upubliserte nyheter vises kun for områder hvor du har rett til å opprette nye nyheter.');
		}
		
        foreach ($objNyhetCol as $objNyhet) {
            $output .= NyhetGen::genFullNyhet($objNyhet);
        }
        
		return $output;        
    }
	
	public function gen_nyheter_del() {
		
        $objNyhetCol = NyhetFactory::getDeletedNyheter();
      
		if ($objNyhetCol->length() === 0) {
			return NyhetGen::genIngenNyheter();
		}
		
        foreach ($objNyhetCol as $objNyhet) {
            $output .= NyhetGen::genFullNyhetDeleted($objNyhet);
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
        if($_REQUEST['editabort']) {
            msg('Endringer ikke lagret.');
            return $this->gen_nyheter_full();
        };
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
			$objNyhet->setOmrade($_POST['nyhetomrade']);
            $objNyhet->setWikiPath('auto');
            $objNyhet->setType(1);
        }
		$objNyhet->setIsSticky(($_POST['nyhetsticky'] == 'sticky') ? true : false);
		$objNyhet->setImagePath($_POST['nyhetbilde']);
        
        // Publish time
        $acl = $objNyhet->getAcl();
        if ($acl >= MSAUTH_3) {
            $res = $objNyhet->setPublishTime($_POST['nyhetpubdato'] . ' ' . $_POST['nyhetpubdato_hour'] . ':' . $_POST['nyhetpubdato_minute']);
            if (!$res) msg('Ugyldig dato/klokkeslett for publiseringstidspunkt. Nyheten publiseres ikke før korrekt tidspunkt settes!', -1);
        }
        
        $objNyhet->setWikiTekst($_POST['wikitext']);
        
        if (!$_REQUEST['editpreview']) {
            // Ikke preview eller abort - lagre
            if ($objNyhet->hasUnsavedChanges()) {
                try{
                    $objNyhet->update_db();
                    $objNyhet = NyhetFactory::getNyhetById($objNyhet->getId());
                } catch (Exception $e) {
                    msg('Klarte ikke å lagre nyhet!', -1);
                    return false;
                }
            } else {
                msg('Lagring av nyhet: nyhet ikke endret.');
            }
            
            return NyhetGen::genFullNyhetViewOnly($objNyhet);
        } else {
            // Preview
            return NyhetGen::genEdit($objNyhet, true);
        }
        
        
    }
	
	public function slett_nyhet() {
		$nyhetid = $_REQUEST['nyhetid'];
		try{
			$objNyhet = NyhetFactory::getNyhetById($nyhetid);
		} catch (Exception $e) {
			msg('Klarte ikke å slette nyhet med id: ' . htmlspecialchars($nyhetid), -1);
			return false;
		}
		
		($objNyhet->slett())
			? msg('Slettet nyhet: ' . $objNyhet->getTitle(), 1)
			: msg('Klarte ikke å slette nyhet med id: ' . $objNyhet->getId(), -1);
		
	}
    
	public function restore_nyhet() {
		$nyhetid = $_REQUEST['nyhetid'];
		try{
			$objNyhet = NyhetFactory::getNyhetById($nyhetid);
		} catch (Exception $e) {
			msg('Klarte ikke å gjenopprette nyhet med id: ' . htmlspecialchars($nyhetid), -1);
			return false;
		}
		
		($objNyhet->restore())
			? msg('Gjenopprettet nyhet: ' . $objNyhet->getTitle(), 1)
			: msg('Klarte ikke å gjenopprette nyhet med id: ' . $objNyhet->getId(), -1);
		
	}
    
	public function permslett_nyhet() {
		$nyhetid = $_REQUEST['nyhetid'];
		try{
			$objNyhet = NyhetFactory::getNyhetById($nyhetid);
		} catch (Exception $e) {
			msg('Klarte ikke å perm-slette nyhet med id: ' . htmlspecialchars($nyhetid), -1);
			return false;
		}
		
		($objNyhet->permslett())
			? msg('Slettet nyhet: "' . $objNyhet->getTitle() . '" permanent.', 1)
			: msg('Klarte ikke å slette nyhet med id: ' . $objNyhet->getId(), -1);
		
	}
    
    public function update_nyhet_from_wp() {
        msg('Oppdaterer nyhet basert på ekstern redigering');
        
        $data = $this->_msmodulvars;
        
        $wikipath = $data[1] . ':' . $data[2];
        $wikitext = $data[0][1];
        
        try {
            $objNyhet = NyhetFactory::getNyhetByWikiPath($wikipath);
        } catch (Exception $e) {
            return false;
        }
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
    
    public function merk_alle_lest() {
        try{
            $NyhetCol = NyhetFactory::getUlesteNyheterForBrukerId($this->_userID);
        } catch (Exception $e) {
            msg('Klarte ikke å hente uleste nyheter.', -1);
            return false;
        }
        
        (MsNyhet::merk_flere_lest($NyhetCol)) ?
            msg('Merket alle nyheter lest', 1):
            msg('Klarte ikke å merke alle nyheter lest', -1);
        
    }
    
    public function check_published() {
        $wikipath = $this->_msmodulvars;
        
        try {
            $objNyhet = NyhetFactory::getNyhetByWikiPath($wikipath);
        } catch (Exception $e) {
            throw Exception('Fant ikke nyhet i database');
        }
        
        return (bool) $objNyhet->isPublished();
    }

}
