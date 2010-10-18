<?php
if(!defined('MS_INC')) die();
define('MS_NYHET_LINK', MS_LINK . "&page=nyheter");
require_once('class.nyheter.nyhetcollection.php');
require_once('class.nyheter.msnyhet.php');
require_once('class.nyheter.omrade.php');
require_once('class.nyheter.nyhettag.php');
require_once('class.nyheter.nyhettagcollection.php');
require_once('class.nyheter.nyhetfactory.php');
require_once('class.nyheter.nyhettagfactory.php');
require_once('class.nyheter.nyhetgen.php');
require_once(DOKU_INC.'inc/search.php');

class msmodul_nyheter implements msmodul {

	static $dispatcher;
	
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
		self::$dispatcher = new ActDispatcher($this, $this->_adgangsNiva);
		// Funksjon som definerer handles for act-values
		$this->_setHandlers(self::$dispatcher);
		
		// Dispatch $act, dispatcher returnerer output
		return self::$dispatcher->dispatch($act);

	}
	
	private function _setHandlers(&$dispatcher) {
		$dispatcher->addActHandler('list', 'gen_nyheter_full', MSAUTH_1);
		$dispatcher->addActHandler('show', 'gen_nyheter_full', MSAUTH_1);
		$dispatcher->addActHandler('ulest', 'gen_nyheter_ulest', MSAUTH_1);
		$dispatcher->addActHandler('arkiv', 'gen_nyhet_arkiv', MSAUTH_1);
		$dispatcher->addActHandler('upub', 'gen_nyheter_upub', MSAUTH_2);
		$dispatcher->addActHandler('edit', 'gen_edit_nyhet', MSAUTH_2);
        $dispatcher->addActHandler('extview', 'gen_ext_view', MSAUTH_NONE);
		$dispatcher->addActHandler('slett', 'slett_nyhet', MSAUTH_2);
		$dispatcher->addActHandler('subedit', 'save_nyhet_changes', MSAUTH_2);
		$dispatcher->addActHandler('extupdate', 'update_nyhet_from_wp', MSAUTH_NONE);
		$dispatcher->addActHandler('addnyhet', 'gen_add_nyhet', MSAUTH_3);
		$dispatcher->addActHandler('lest', 'merk_nyhet_lest', MSAUTH_1);
		$dispatcher->addActHandler('lest', 'gen_nyheter_ulest', MSAUTH_1);
		$dispatcher->addActHandler('allelest', 'merk_alle_lest', MSAUTH_1);
		$dispatcher->addActHandler('allelest', 'gen_nyheter_ulest', MSAUTH_1);
		$dispatcher->addActHandler('tagadm', 'gen_tag_admin', MSAUTH_ADMIN);
		$dispatcher->addActHandler('subtagadm', 'save_tag_changes', MSAUTH_ADMIN);
		$dispatcher->addActHandler('subtagadm', 'gen_tag_admin', MSAUTH_ADMIN);
		$dispatcher->addActHandler('sletttag', 'slett_tag', MSAUTH_ADMIN);
		$dispatcher->addActHandler('sletttag', 'gen_tag_admin', MSAUTH_ADMIN);
		$dispatcher->addActHandler('omradeadm', 'gen_omrade_admin', MSAUTH_ADMIN);
		$dispatcher->addActHandler('subomradeadm', 'save_omrade_changes', MSAUTH_ADMIN);
		$dispatcher->addActHandler('subomradeadm', 'gen_omrade_admin', MSAUTH_ADMIN, true);
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
				$toppmeny->addChild(new Menyitem('Uleste nyheter','&page=nyheter&act=ulest'));
				$toppmeny->addChild(new Menyitem('Arkiverte nyheter','&page=nyheter&act=arkiv'));
				if ($lvl >= MSAUTH_2) {
					$toppmeny->addChild(new Menyitem('Upubliserte nyheter','&page=nyheter&act=upub'));
				}
				if ($lvl >= MSAUTH_5) {
					$toppmeny->addChild(new Menyitem('Slettede nyheter','&page=nyheter&act=showdel'));
				}
                if ($lvl >= MSAUTH_3) {
					$toppmeny->addChild(new Menyitem('Opprett nyhet','&page=nyheter&act=addnyhet'));
				}
                if ($lvl >= MSAUTH_5) {
					$toppmeny->addChild(new Menyitem('Områdeadmin','&page=nyheter&act=omradeadm'));
				}
                if ($lvl >= MSAUTH_5) {
					$toppmeny->addChild(new Menyitem('Tag-/kategori-admin','&page=nyheter&act=tagadm'));
				}
			}
			$meny->addItem($toppmeny);
		}
			
	}
	
/********************************\
 *           HANDLERS           *
\********************************/

	public function gen_nyheter_full() {
		
        $objNyhetCol = NyhetFactory::getNyligePubliserteNyheter();
		
		if ($objNyhetCol->length() === 0) {
			return NyhetGen::genIngenNyheter();
		}
        
        foreach ($objNyhetCol as $objNyhet) {
            $nyhet = NyhetGen::genFullNyhet($objNyhet, array(), 'list');
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
            $output .= NyhetGen::genFullNyhet($objNyhet, array('lest'), 'ulest');
        }
        
        $output = '<p><a href="'.MS_NYHET_LINK.'&act=allelest">Merk alle nyheter lest</a></p>' . $output;
                
		return $output;
		
	}
    
    public function gen_nyhet_arkiv() {
        
        $limits = array();
        
        if($_POST['dofilter'] != 'Nullstill') {
            // Fradato
            if (!empty($_POST['fdato'])) {
                $timestamp = strtotime($_POST['fdato']);
                if($timestamp !== false) {
                    $limits['fdato'] = $timestamp;
                }
            }
            // Tildato
            if (!empty($_POST['tdato'])) {
                $timestamp = strtotime($_POST['tdato']);
                if($timestamp !== false) {
                    $limits['tdato'] = $timestamp;
                }
            }
            // Overskriftsøk
            if (!empty($_POST['oskrift'])) {
                $limits['oskrift'] = $_POST['oskrift'];
            }
            // Kategori
            $arInputKat = (array) $_POST['fkat'];
            if (!empty($arInputKat)) {
                $limits['fkat'] = $arInputKat;
            }
            // Tags
            $arInputTag = (array) $_POST['ftag'];
            if (!empty($arInputTag)) {
                $limits['ftag']['data'] = $arInputTag;
                $limits['ftag']['mode'] = ($_POST['tagfilter'] == 'AND') ? 'AND' : 'OR';
            }
            // Sortorder
            if($_POST['sortASC'] == 'y') {
                $limits['sortorder'] = 'ASC';
            } else {
                $limits['sortorder'] = 'DESC';
            }
            // Publisher
            $arInputPublishers = (array) $_POST['fpublishers'];
            if (!empty($arInputPublishers)) {
                $limits['fpublishers'] = $arInputPublishers;
            }
            // Områder
            $arInputOmrader = (array) $_POST['fomrader'];
            if (!empty($arInputOmrader)) {
                $limits['fomrader'] = $arInputOmrader;
            }
        }

        $objNyhetCol = NyhetFactory::getNyheterMedLimits($limits);
        $output = NyhetGen::genArkivOptions($limits);
        
		if ($objNyhetCol->length() === 0) {
			return $output . NyhetGen::genIngenNyheter('<br />Ingen nyheter matcher filterne du satt.');
		}
        foreach ($objNyhetCol as $objNyhet) {
            $output .= NyhetGen::genFullNyhet($objNyhet, array(), 'arkiv');
        }
                
		return $output;
        
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
            $output .= NyhetGen::genFullNyhet($objNyhet, array(), 'upub');
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
        $act_abort = !empty($_REQUEST['editabort']);
        $act_preview = !empty($_REQUEST['editpreview']);
        $nyhetid = $_REQUEST['nyhetid'];
        
        if($act_abort) {
            msg('Endringer ikke lagret.');
            return $this->gen_nyheter_full();
        };
        
        // Validation
        if (strlen(trim($_POST['nyhettitle'])) == 0) {
            msg('Obligatorisk data mangler: Overskrift kan ikke være blank.', -1);
            $act_preview = true;
        }

        if (strlen(trim($_POST['wikitext'])) == 0) {
            msg('Obligatorisk data mangler: Nyhet-tekst kan ikke være blank.', -1);
            $act_preview = true;
        }
        
        $inputkategori = $_POST['nyhetkategori'];
        if ($inputkategori == '0') {
            msg('Obligatorisk data mangler: Kategori må velges.', -1);
            $act_preview = true;
        }
        
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
        
        // Kategori
        $colKategorier = NyhetTagFactory::getAlleNyhetTags(true, true, false, NyhetTag::TYPE_KATEGORI);
        foreach($colKategorier as $objKategori) {
            if ($objKategori->getNavn() === $inputkategori) {
                $objFoundKategori = $objKategori;
                break;
            }
        }
        if($objFoundKategori instanceof NyhetTag) {
            $objNyhet->setKategori($objFoundKategori, $act_preview);
        } elseif (!$act_preview) {
            throw new Exception('Feil ved redigering av nyhet: Ugyldig kategori valgt!');
        }
        
        // Tags
        $colTags = NyhetTagFactory::getAlleNyhetTags(true, true, false, NyhetTag::TYPE_TAG);
        $colSelectedTags = new NyhetTagCollection();
        $arSelectedTags = (array) $_POST['nyhettags'];
        foreach($arSelectedTags as $k => $v) {
            $objTag = $colTags->getItem($k);
            if($objTag instanceof NyhetTag) {
                $colSelectedTags->additem($objTag, $objTag->getId());
            } else {
                msg('Tag med id: ' . htmlspecialchars($k) . ' er ikke kjent og kan ikke knyttes til nyhet!', -1);
                continue;
            }
        }
        $objNyhet->setTags($colSelectedTags, $act_preview);
        
        // Publish time
        $acl = $objNyhet->getAcl();
        if ($acl >= MSAUTH_3) {
            $indato = $_POST['nyhetpubdato'];
            $inhour = $_POST['nyhetpubdato_hour'];
            $inmin = $_POST['nyhetpubdato_minute'];
            if (!$objNyhet->isSaved()) {
                $timestamp = strtotime($indato . ' ' . $inhour . ':' . $inmin);
                if ($timestamp < time()) {
                    $inhour = date('H');
                    $inmin = date('i');
                }
            }
            $res = $objNyhet->setPublishTime($indato . ' ' . $inhour . ':' . $inmin);
            if (!$res) msg('Ugyldig dato/klokkeslett for publiseringstidspunkt. Nyheten publiseres ikke før korrekt tidspunkt settes!', -1);
        }
        
        $objNyhet->setWikiTekst($_POST['wikitext'], $act_preview);
        
        if (!$act_preview) {
            // Ikke preview eller abort - lagre
            if ($objNyhet->hasUnsavedChanges()) {
                try{
                    $objNyhet->update_db();
                    $objNyhet = NyhetFactory::getNyhetById($objNyhet->getId());
                } catch (Exception $e) {
                    msg('Klarte ikke å lagre nyhet: ' . $e->getMessage(), -1);
                    return false;
                }
            } else {
                if(MinSide::DEBUG) msg('Lagring av nyhet: nyhet ikke endret.');
            }
            
            return NyhetGen::genFullNyhet($objNyhet);
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
			? msg('Slettet nyhet: ' . $objNyhet->getTitle(true), 1)
			: msg('Klarte ikke å slette nyhet med id: ' . $objNyhet->getId(), -1);
            
        if (isset($_REQUEST['returnto'])) {
            return self::$dispatcher->dispatch($_REQUEST['returnto']);
        } else {
            throw new Exception('Sletting utført, men vet ikke hva som skal vises nå! (returnto ikke satt)');
        }
		
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
			? msg('Gjenopprettet nyhet: ' . $objNyhet->getTitle(true), 1)
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
			? msg('Slettet nyhet: "' . $objNyhet->getTitle(true) . '" permanent.', 1)
			: msg('Klarte ikke å slette nyhet med id: ' . $objNyhet->getId(), -1);
		
	}
    
    public function update_nyhet_from_wp() {
        if(MinSide::DEBUG) msg('Oppdaterer nyhet basert på ekstern redigering');
        
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
        
        
        if ($objNyhet->merkLest($this->_userID)) {
            if(MinSide::DEBUG) {
                msg("Merket nyhetid $inputid som lest", 1);
            }
        } else {
            if(MinSide::DEBUG) {
                msg("Klarte ikke å merke nyhetid $inputid som lest", -1);
            }
        }
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
    
    public function gen_tag_admin() {
        $colTag = NyhetTagFactory::getAlleNyhetTags(true, true);
        return NyhetGen::genTagAdmin($colTag);
    }
    
    public function save_tag_changes() {
        if ($_POST['tagact'] == 'edit') {
            $colTag = NyhetTagFactory::getAlleNyhetTags(true, true, false);
            $data = (array) $_REQUEST['tagadmdata'];
            foreach($data as $tagid => $tagoptions) {
                $objNyhetTag = $colTag->getItem($tagid);
                if(!($objNyhetTag instanceof NyhetTag)) {
                    msg('Fant ikke tag med id: ' . $tagid, -1);
                    continue;
                }
                
                $objNyhetTag->setNoSelect((bool)(array_key_exists('noselect', $tagoptions)));
                $objNyhetTag->setNoView((bool)(array_key_exists('noview', $tagoptions)));
                $objNyhetTag->updateDb();
            }
        } elseif ($_POST['tagact'] == 'new') {
            $objNyhetTag = new NyhetTag($_POST['nytagtype']);
            $objNyhetTag->setNavn(htmlspecialchars($_POST['nytagnavn']));
            $objNyhetTag->updateDb();
        } else {
            throw new Exception('Tag act er ikke satt, vet ikke hva som skal gjøres.');
        }
        
    }
    
    public function slett_tag() {
        $tagid = $_REQUEST['tagid'];
        if(!empty($tagid)) {
            $objTag = NyhetTagFactory::getNyhetTagById($tagid);
            if ($objTag->slett()) {
                msg('Slettet ' . (($objTag->getType() == NyhetTag::TYPE_TAG) ? 'tag' : 'kategori') . ' med navn: ' . $objTag->getNavn() .
                    ' og id: ' . $objTag->getId(), 1);
            } else {
                msg('Klarte ikke å slette ' . (($objTag->getType() == NyhetTag::TYPE_TAG) ? 'tag' : 'kategori') . '!', -1);
            } 
            unset($objTag);
            return;
        } else {
            throw new Exception('Kan ikke slette tag, tagid ikke gitt');
        }
        
    }
    
    public function gen_omrade_admin($force_reload=false) {
        $colOmrader = NyhetOmrade::getOmrader('msnyheter', MSAUTH_NONE, $force_reload);
        return NyhetGen::genOmradeAdmin($colOmrader);
    }
    
    public function save_omrade_changes() {
        global $msdb;
        $sql = array();
        $colOmrader = NyhetOmrade::getOmrader('msnyheter');
        
        $visnavn = $_REQUEST['visnavn'];
        $farge = $_REQUEST['farge'];
        $defaultomrade = $_REQUEST['defaultomrade'];
        
        // Sjekk default
        if (isset($defaultomrade)) {
            $objOmrade = $colOmrader->getItem($defaultomrade);
            if(!empty($objOmrade) && !$objOmrade->isDefault()) {
                $safeomrade = $msdb->quote($objOmrade->getOmrade());
                $sql[] = "UPDATE nyheter_omrade SET isdefault=0;";
                $sql[] = "UPDATE nyheter_omrade SET isdefault=1 WHERE omradenavn=$safeomrade;";
            }
        }
        
        // Sjekk visningsnavn
        if (is_array($visnavn) && sizeof($visnavn)) {
            foreach($visnavn as $k => $v) {
                $objOmrade = $colOmrader->getItem($k);
                if (!empty($objOmrade) && ($v != $objOmrade->getVisningsnavn()) ) {
                    $safeomrade = $msdb->quote($objOmrade->getOmrade());
                    $safevisningsnavn = $msdb->quote($v);
                    $sql[] = "UPDATE nyheter_omrade SET visningsnavn=$safevisningsnavn WHERE omradenavn=$safeomrade;";
                }
            }
        }
        
        // Sjekk farger
        if (is_array($farge) && sizeof($farge)) {
            foreach($farge as $k => $v) {
                $objOmrade = $colOmrader->getItem($k);
                if (!empty($objOmrade) && ($v != $objOmrade->getFarge()) ) {
                    if (!preg_match("/^[A-F0-9]{6}$/iAD", $v)) {
                        msg('Ugyldig fargekode "' . htmlspecialchars($v) . 
                            '" for område "' . htmlspecialchars($k) . 
                            '". Fargevalg lagres ikke for dette området!', -1);
                        continue;
                    }
                    $safeomrade = $msdb->quote($objOmrade->getOmrade());
                    $safefarge = $msdb->quote(strtoupper($v));
                    $sql[] = "UPDATE nyheter_omrade SET farge=$safefarge WHERE omradenavn=$safeomrade;";
                }
            }
        }
        
        foreach ($sql as $stmt) {
            $msdb->exec($stmt);
        }
        
        if (count($sql)) {
            msg('Lagret endringer i områdeadmin.', 1);
        } else {
            if(MinSide::DEBUG) msg('Ingen endringer å lagre i områdeadmin.');
        }
        
    }

}
