<?php
if(!defined('MS_INC')) die();
define('MS_NYHET_LINK', MS_LINK . "&amp;page=nyheter");
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
        // Siste nyheter
        $dispatcher->addActHandler('list', 'gen_nyheter_full', MSAUTH_1);
        $dispatcher->addActHandler('show', 'gen_nyheter_full', MSAUTH_1);
        // Uleste nyheter / merk lest
        $dispatcher->addActHandler('forside', 'gen_nyheter_forside', MSAUTH_1);
        $dispatcher->addActHandler('redirforside', 'gen_redirect_forside', MSAUTH_1);
        $dispatcher->addActHandler('ulest', 'gen_nyheter_ulest', MSAUTH_1);
        $dispatcher->addActHandler('lest', 'merk_nyhet_lest', MSAUTH_1);
        $dispatcher->addActHandler('allelest', 'merk_alle_lest', MSAUTH_1);
        // Mine nyheter
        $dispatcher->addActHandler('mine', 'gen_nyheter_mine', MSAUTH_1);
        $dispatcher->addActHandler('addmine', 'add_nyheter_mine', MSAUTH_1); // Bruker returnto
        $dispatcher->addActHandler('fjernmine', 'fjern_nyheter_mine', MSAUTH_1); // Bruker returnto
        // Rediger / opprett
        $dispatcher->addActHandler('addnyhet', 'gen_add_nyhet', MSAUTH_3);
        $dispatcher->addActHandler('edit', 'gen_edit_nyhet', MSAUTH_2);
        $dispatcher->addActHandler('subedit', 'save_nyhet_changes', MSAUTH_2);
        // Arkiv
        $dispatcher->addActHandler('arkiv', 'gen_nyhet_arkiv', MSAUTH_1);
        $dispatcher->addActHandler('arkivintegrert', 'gen_nyhet_arkiv_integrert', MSAUTH_1);
        // Upubliserte
        $dispatcher->addActHandler('upub', 'gen_nyheter_upub', MSAUTH_3);
        $dispatcher->addActHandler('pubnaa', 'publiser_nyhet', MSAUTH_3);
        // Slett nyhet (bruker returnto, dispatcher direkte) og slettede nyheter
        $dispatcher->addActHandler('slett', 'slett_nyhet', MSAUTH_2);
        $dispatcher->addActHandler('showdel', 'gen_nyheter_del', MSAUTH_5);
        $dispatcher->addActHandler('restore', 'restore_nyhet', MSAUTH_5);
        $dispatcher->addActHandler('restore', 'gen_nyheter_del', MSAUTH_5);
        $dispatcher->addActHandler('permslett', 'permslett_nyhet', MSAUTH_5);
        $dispatcher->addActHandler('permslett', 'gen_nyheter_del', MSAUTH_5);
        // Stats
        $dispatcher->addActHandler('nyhetstats', 'gen_nyhet_stats', MSAUTH_5);
        // Admin
        $dispatcher->addActHandler('admin', 'gen_admin', MSAUTH_ADMIN);
        $dispatcher->addActHandler('doimport', 'import_nyheter', MSAUTH_ADMIN);
        $dispatcher->addActHandler('doimport', 'gen_admin', MSAUTH_ADMIN);
        $dispatcher->addActHandler('subtagadm', 'save_tag_changes', MSAUTH_ADMIN);
        $dispatcher->addActHandler('subtagadm', 'gen_admin', MSAUTH_ADMIN);
        $dispatcher->addActHandler('sletttag', 'slett_tag', MSAUTH_ADMIN);
        $dispatcher->addActHandler('sletttag', 'gen_admin', MSAUTH_ADMIN);
        $dispatcher->addActHandler('subomradeadm', 'save_omrade_changes', MSAUTH_ADMIN);
        $dispatcher->addActHandler('subomradeadm', 'gen_admin', MSAUTH_ADMIN, true);
        $dispatcher->addActHandler('readlog', 'gen_read_log', MSAUTH_5);
        $dispatcher->addActHandler('doreparse', 'do_reparse', MSAUTH_ADMIN);
        $dispatcher->addActHandler('dobulklest', 'do_bulklest', MSAUTH_ADMIN);
        $dispatcher->addActHandler('dobulklest', 'gen_admin', MSAUTH_ADMIN);
        // System / interne
        $dispatcher->addActHandler('searchpagelookup', 'gen_searchpagelookup', MSAUTH_1);
        $dispatcher->addActHandler('searchfullpage', 'gen_searchfullpage', MSAUTH_1);
        $dispatcher->addActHandler('extupdate', 'update_nyhet_from_wp', MSAUTH_NONE);
        $dispatcher->addActHandler('extview', 'gen_ext_view', MSAUTH_NONE);
	}
	
	public function registrer_meny(MenyitemCollection &$meny) {
		$lvl = $this->_adgangsNiva;
        
		if ($lvl > MSAUTH_NONE) { 
			$toppmeny = new Menyitem('Nyheter','&amp;page=nyheter');
			if ($_REQUEST['page'] == 'nyheter') { // Modul er lastet/vises
                
                $menyitem_opprett = new Menyitem('Opprett nyhet','&amp;page=nyheter&amp;act=addnyhet');
                $menyitem_list = new Menyitem('Aktuelle nyheter','&amp;page=nyheter&amp;act=list');
                $menyitem_ulest = new Menyitem('Uleste nyheter','&amp;page=nyheter&amp;act=ulest');
                $menyitem_mine = new Menyitem('Mine nyheter','&amp;page=nyheter&amp;act=mine');
                $menyitem_upub = new Menyitem('Upublisert','&amp;page=nyheter&amp;act=upub');
                $menyitem_showdel = new Menyitem('Slettet','&amp;page=nyheter&amp;act=showdel');
                $menyitem_arkiv = new Menyitem('Arkiv','&amp;page=nyheter&amp;act=arkiv');
                $menyitem_admin = new Menyitem('Admin','&amp;page=nyheter&amp;act=admin');
                
                switch($this->_msmodulact) {
                    case 'show':
                    case 'list':
                        $objStrong = $menyitem_list;
                        break;
                    case 'lest':
                    case 'allelest':
                    case 'ulest':
                        $objStrong = $menyitem_ulest;
                        break;
                    case 'mine':
                        $objStrong = $menyitem_mine;
                        break;
                    case 'arkiv':
                        $objStrong = $menyitem_arkiv;
                        break;
                    case 'pubnaa':
                    case 'upub':
                        $objStrong = $menyitem_upub;
                        break;
                    case 'addnyhet':
                        $objStrong = $menyitem_opprett;
                        break;
                    case 'subtagadm':
                    case 'readlog':
                    case 'sletttag':
                    case 'subomradeadm':
                    case 'doimport':
                    case 'doreparse':
                    case 'admin':
                        $objStrong = $menyitem_admin;
                        break;
                    case 'restore':
                    case 'permslett':
                    case 'showdel':
                        $objStrong = $menyitem_showdel;
                        break;
                    default:
                        $objStrong = null;
                        break;
                }
                if($objStrong instanceof Menyitem) {
                    $strongtekst = '<span class="selected">' . $objStrong->getTekst() . '</span>';
                    $objStrong->setTekst($strongtekst);
                }
                
                $toppmeny->addChild($menyitem_ulest);
                $toppmeny->addChild($menyitem_mine);
                $toppmeny->addChild($menyitem_list);
                if ($lvl >= MSAUTH_3) {
					$toppmeny->addChild($menyitem_opprett);
				}
                if ($lvl >= MSAUTH_3) {
					$toppmeny->addChild($menyitem_upub);
				}
				if ($lvl >= MSAUTH_5) {
					$toppmeny->addChild($menyitem_showdel);
				}
                $toppmeny->addChild($menyitem_arkiv);
                if ($lvl >= MSAUTH_ADMIN) {
					$toppmeny->addChild($menyitem_admin);
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
        $objMineNyheterCol = NyhetFactory::getMineNyheterForBrukerId($this->_userID, false);
		$objUlestCol = NyhetFactory::getUlesteNyheterForBrukerId($this->_userID, false);
        
		if ($objNyhetCol->length() === 0) {
			return NyhetGen::genIngenNyheter('Her vises kun nyheter publisert de siste syv dagene. '.
                'Se <a href="'.MS_NYHET_LINK.'&amp;act=arkiv">arkivet</a> for eldre nyheter.');
		}
        
        foreach ($objNyhetCol as $objNyhet) {
            $options = array();
            if($objUlestCol->exists( $objNyhet->getId() )) {
                $options[] = 'lest';
            }
            if($objMineNyheterCol->exists( $objNyhet->getId() )) {
                $options[] = 'fjernmine';
            }
            
            $nyhet = NyhetGen::genFullNyhet($objNyhet, $options, 'list');
            if ($objNyhet->isSticky()) {
                $sticky .= $nyhet;
            } else {
                $normal .= $nyhet;
            }
        }
        
        $pre = '<h1>Aktuelle nyheter</h1><div class="level2">';
        $post = '</div>';
        
		return $pre . $sticky . $normal . $post;
		
	}
    
	public function gen_nyheter_ulest($returnto='ulest') {
		
        $objNyhetCol = NyhetFactory::getUlesteNyheterForBrukerId($this->_userID);
        $objMineNyheterCol = NyhetFactory::getMineNyheterForBrukerId($this->_userID, false);
        

        $pre = '<h1>Uleste nyheter</h1><div class="level2">';
        $post = '</div>';
        
		if ($objNyhetCol->length() === 0) {
			return $pre . NyhetGen::genIngenNyheter('Her vises kun nyheter du ikke har market som lest.<br /><br /> '.
                'For andre nyheter, se: <li class="msnyhetmsg"><a href="'.MS_NYHET_LINK.'&amp;act=show">Aktuelle nyheter</a></li><li class="msnyhetmsg"><a href="'.MS_NYHET_LINK.'&amp;act=arkiv">Arkivet</a></li>') . $post;
		}
		
        foreach ($objNyhetCol as $objNyhet) {
            $options = array('lest');
            if($objMineNyheterCol->exists( $objNyhet->getId() )) {
                $options[] = 'fjernmine';
            }
            
            $output .= NyhetGen::genFullNyhet($objNyhet, $options, $returnto);
        }
        
        $output = '<p><a href="'.MS_NYHET_LINK.'&amp;returnto='.$returnto.'&amp;act=allelest">Merk alle nyheter lest</a></p>' . $output;
        
		return $pre . $output . $post;
		
	}
    
    public function gen_nyheter_forside() {
        $objNyhetCol = NyhetFactory::getUlesteNyheterForBrukerId($this->_userID);
        $objMineNyheterCol = NyhetFactory::getMineNyheterForBrukerId($this->_userID, false);
        $objStickyCol = NyhetFactory::getStickyNyheter();
        
        $html_ulest_start = '<h2 class="sectionedit1"><a name="dine_uleste_nyheter" id="dine_uleste_nyheter">Dine uleste nyheter</a></h2>
                            <div class="level2">';
        $html_ulest_slutt = '</div>';
        
        $html_sticky_start = '<h2 class="sectionedit1"><a name="sticky_nyheter" id="sticky_nyheter">Sticky nyheter</a></h2>
                            <div class="level2">';
        $html_sticky_slutt = '</div>';
        
		if ($objNyhetCol->length() === 0 && $objStickyCol->length() === 0) {
			return $html_ulest_start . NyhetGen::genIngenNyheter('Her vises kun nyheter du ikke har market som lest og nyheter som er <em>&quot;sticky&quot;</em>.<br /><br /> '.
                'For andre nyheter, se: <li class="msnyhetmsg"><a href="'.MS_NYHET_LINK.'&amp;act=show">Aktuelle nyheter</a></li><li class="msnyhetmsg"><a href="'.MS_NYHET_LINK.'&amp;act=arkiv">Arkivet</a></li>') . $html_ulest_slutt;
		}
		
        if ($objNyhetCol->length() !== 0) {
            $output .= $html_ulest_start . '<p><a href="'.MS_NYHET_LINK.'&amp;returnto=redirforside&amp;act=allelest">Merk alle nyheter lest</a></p>';
            foreach ($objNyhetCol as $objNyhet) {
                $options = array('lest');
                if($objMineNyheterCol->exists( $objNyhet->getId() )) {
                    $options[] = 'fjernmine';
                }
                
                $output .= NyhetGen::genFullNyhet($objNyhet, $options, 'redirforside');
            }
            $output .= $html_ulest_slutt;
        }
        
        $minst_en_sticky = false;
        foreach ($objStickyCol as $objNyhet) {
            if($objNyhetCol->exists( $objNyhet->getId() )) {
                continue;
            }
            $options = array();
            if($objMineNyheterCol->exists( $objNyhet->getId() )) {
                    $options[] = 'fjernmine';
            }
            $minst_en_sticky = true;
            $stickyoutput .= NyhetGen::genFullNyhet($objNyhet, $options, 'redirforside');
        }
        if ($minst_en_sticky) {
            $output .= $html_sticky_start . $stickyoutput . $html_sticky_slutt;
        }
        
		return $output;
    }
    
    public function gen_admin($force_reload=false) {
        $output = '';
        $output .= $this->gen_omrade_admin($force_reload);
        $output .= $this->gen_tag_admin();
        $output .= $this->gen_readlog_admin();
        $output .= $this->gen_import_admin();
        $output .= $this->gen_reparse_admin();
        $output .= $this->gen_bulklest_admin();
        
        return $output;
    }
    
    public function add_nyheter_mine() {
        $nyhetid = (int) $_REQUEST['nyhetid'];
		
        try{
			$objNyhet = NyhetFactory::getNyhetById($nyhetid);
		} catch (Exception $e) {
			$error_text = 'Klarte ikke å legge nyhet til i &quot;Mine nyheter&quot: ' . $e->getMessage();
            throw new Exception($error_text);
		}
        
        if ($objNyhet->getAcl() < MSAUTH_1) throw new Exception('Du har ikke lesetilgang på denne nyheten!');
		
		($objNyhet->flag_mine_nyheter($this->_userID))
			? msg('La nyhet til i &quot;Mine nyheter&quot;: ' . $objNyhet->getTitle(true), 1)
			: msg('Klarte ikke å legge nyhet til i &quot;Mine nyheter&quot;! Id: ' . $objNyhet->getId(), -1);
            
        if (isset($_REQUEST['returnto'])) {
            $this->_msmodulact = $_REQUEST['returnto'];
            return self::$dispatcher->dispatch($_REQUEST['returnto']);
        } else {
            $this->_msmodulact = 'mine';
            return self::$dispatcher->dispatch('mine');
        }
		
    }
    
    public function fjern_nyheter_mine() {
        $nyhetid = (int) $_REQUEST['nyhetid'];
		
        try{
			$objNyhet = NyhetFactory::getNyhetById($nyhetid);
		} catch (Exception $e) {
			$error_text = 'Klarte ikke å fjerne nyhet fra &quot;Mine nyheter&quot;: ' . $e->getMessage();
            throw new Exception($error_text);
		}
		
		($objNyhet->unflag_mine_nyheter($this->_userID))
			? msg('Fjernet nyhet fra &quot;Mine nyheter&quot;: ' . $objNyhet->getTitle(true), 1)
			: msg('Klarte ikke å fjerne nyhet fra &quot;Mine nyheter&quot;! Id: ' . $objNyhet->getId(), -1);
        
        if (isset($_REQUEST['returnto'])) {
            $this->_msmodulact = $_REQUEST['returnto'];
            return self::$dispatcher->dispatch($_REQUEST['returnto']);
        } else {
            $this->_msmodulact = 'mine';
            return self::$dispatcher->dispatch('mine');
        }
        
    }
    
    public function gen_nyheter_mine() {
        $objNyhetCol = NyhetFactory::getMineNyheterForBrukerId($this->_userID);
        
		if ($objNyhetCol->length() === 0) {
			return NyhetGen::genIngenNyheter('<p>Her vises nyheter du har lagt til i "Mine nyheter". '.
                'Bruk "Mine nyheter" ikonet oppe til høyre på en nyhet for å legge den til her.</p>'.
                '<p>Her kan du legge inn nyheter du ofte refererer til, eller må huske på.</p>');
		}
		
        foreach ($objNyhetCol as $objNyhet) {
            $output .= NyhetGen::genFullNyhet($objNyhet, array('fjernmine'), 'mine');
        }
        
		return $output;
    }
    
    public function gen_nyhet_stats() {
        $nyhetid = $_REQUEST['nyhetid'];
        $mode = $_REQUEST['mode'];
        if(!empty($_REQUEST['fratid'])) {
            $fratid = strtotime($_REQUEST['fratid']);
        } else {
            $fratid = null;
        }
        if(!empty($_REQUEST['tiltid'])) {
            $tiltid = strtotime($_REQUEST['tiltid']);
        } else {
            $tiltid = null;
        }
        
        try{
            $objNyhet = NyhetFactory::getNyhetById($nyhetid);
        } catch (Exception $e) {
            msg('Klarte ikke å laste statistikk for nyhet med id: ' . htmlspecialchars($nyhetid), -1);
            return false;
        }
        
        return NyhetGen::genNyhetStats($objNyhet, $mode, $fratid, $tiltid);
    }
    
    public function gen_nyhet_arkiv_integrert() {
        if(in_array('nopagination', $this->_msmodulvars)) {
            $show_pagination = false;
        } else {
            $show_pagination = true;
        }
        $params = $this->_msmodulvars[0];
        $parsed_vars = array();
        parse_str($params, $parsed_vars);
        
        $limits = array();
        
        // Fradato
        if (!empty($parsed_vars['fdato'])) {
            $timestamp = strtotime($parsed_vars['fdato']);
            if($timestamp !== false) {
                $limits['fdato'] = $timestamp;
            }
        }
        // Tildato
        if (!empty($parsed_vars['tdato'])) {
            $timestamp = strtotime($parsed_vars['tdato']);
            if($timestamp !== false) {
                $limits['tdato'] = $timestamp;
            }
        }
        // Overskriftsøk
        if (!empty($parsed_vars['oskrift'])) {
            $limits['oskrift'] = $parsed_vars['oskrift'];
        }
        // Kategori
        $arInputKat = (array) $parsed_vars['fkat'];
        if (!empty($arInputKat)) {
            $limits['fkat'] = $arInputKat;
        }
        // Tags
        $arInputTag = (array) $parsed_vars['ftag'];
        if (!empty($arInputTag)) {
            $limits['ftag']['data'] = $arInputTag;
            $limits['ftag']['mode'] = ($parsed_vars['tagfilter'] == 'AND') ? 'AND' : 'OR';
        }
        // Sortorder - DESC er default
        if($parsed_vars['sortorder'] == 'ASC') {
            $limits['sortorder'] = 'ASC';
        }
        // Publisher
        $arInputPublishers = (array) $parsed_vars['fpublishers'];
        if (!empty($arInputPublishers)) {
            $limits['fpublishers'] = $arInputPublishers;
        }
        // Områder
        $arInputOmrader = (array) $parsed_vars['fomrader'];
        if (!empty($arInputOmrader)) {
            $limits['fomrader'] = $arInputOmrader;
        }
        // Nyheter vist per side
        $perside = (int) $parsed_vars['perside'];
        $limits['pages']['perside'] = ($perside > 0) ? $perside : 10;
        
        // Pagination
        $limits['pages']['count'] = NyhetFactory::getNyheterMedLimits($limits, true);

        // Antall sider (ingen integer division i php, slapp av)
        $limits['pages']['numpages'] = ceil($limits['pages']['count'] / $limits['pages']['perside']);
        // Current page
        
        if ($show_pagination && (((int) $_GET['visside']) > 0)) {
            $currpage = (int) $_GET['visside'];
        } elseif (!empty($parsed_vars['visside'])) {
            $currpage = $parsed_vars['visside'];
        }
        if($currpage > 1 && !($currpage > $limits['pages']['numpages'])) {
            $limits['pages']['currpage'] = $currpage;
        } else {
            $limits['pages']['currpage'] = 1;
        }
        
        if(MinSide::DEBUG) {
            msg('Laster customvisning av nyheter i wiki-side');
            msg('OBS! Ved problemer, husk ~~NOCACHE~~ i wikiside!');
            msg('Pagination er: ' . (($show_pagination) ? 'enabled' : 'disabled'));
            msg('Bruker requested side nr: '. $_GET['visside']);
            msg('Syntax requested side nr: '.$parsed_vars['visside']);
            msg('Viser side nr: '. $limits['pages']['currpage']);
        }

        $objNyhetCol = NyhetFactory::getNyheterMedLimits($limits);
        $objMineNyheterCol = NyhetFactory::getMineNyheterForBrukerId($this->_userID, false);
        
        if ($show_pagination) {
            // Fjern evt visside param
            $stripped_req_uri = preg_replace('/&?visside=([0-9]*)/', '', $_SERVER['REQUEST_URI']);
            $selflink = hsc($stripped_req_uri);
            $html_pagination = '<br />' . NyhetGen::getPagination($limits['pages']['numpages'], $limits['pages']['currpage'], $selflink.'&amp;visside=');
        } else {
            $html_pagination = '';
        }
        
		if ($objNyhetCol->length() === 0) {
			return $output . '<br />' . NyhetGen::genIngenNyheter('<br />Ingen nyheter å vise.');
		}
        foreach ($objNyhetCol as $objNyhet) {
            $options = array();
            if($objMineNyheterCol->exists( $objNyhet->getId() )) {
                $options[] = 'fjernmine';
            }
            $output .= NyhetGen::genFullNyhet($objNyhet, $options);
        }
        
        $pre = '<div class="integrert_nyhetsvisning">';
        $post = '</div>';
        
		return $pre . $output . $html_pagination . $post;
        
        
        
        
    }
    
    public function gen_nyhet_arkiv() {
        
        $limits = array();
        $limits['advanced'] = false;
        
        if($_POST['dofilter'] != 'Nullstill') {
            // Fradato
            if (!empty($_REQUEST['fdato'])) {
                $timestamp = strtotime($_REQUEST['fdato']);
                if($timestamp !== false) {
                    $limits['fdato'] = $timestamp;
                    $limits['advanced'] = true;
                }
            }
            // Tildato
            if (!empty($_REQUEST['tdato'])) {
                $timestamp = strtotime($_REQUEST['tdato']);
                if($timestamp !== false) {
                    $limits['tdato'] = $timestamp;
                    $limits['advanced'] = true;
                }
            }
            // Overskriftsøk
            if (!empty($_REQUEST['oskrift'])) {
                $limits['oskrift'] = $_REQUEST['oskrift'];
            }
            // Kategori
            $arInputKat = (array) $_REQUEST['fkat'];
            if (!empty($arInputKat)) {
                $limits['fkat'] = $arInputKat;
            }
            // Tags
            $arInputTag = (array) $_REQUEST['ftag'];
            if (!empty($arInputTag)) {
                $limits['ftag']['data'] = $arInputTag;
                $limits['ftag']['mode'] = ($_REQUEST['tagfilter'] == 'AND') ? 'AND' : 'OR';
            }
            // Sortorder - DESC er default
            if($_REQUEST['sortorder'] == 'ASC') {
                $limits['sortorder'] = 'ASC';
                $limits['advanced'] = true;
            }
            // Publisher
            $arInputPublishers = (array) $_REQUEST['fpublishers'];
            if (!empty($arInputPublishers)) {
                $limits['fpublishers'] = $arInputPublishers;
                $limits['advanced'] = true;
            }
            // Områder
            $arInputOmrader = (array) $_REQUEST['fomrader'];
            if (!empty($arInputOmrader)) {
                $limits['fomrader'] = $arInputOmrader;
                $limits['advanced'] = true;
            }
            // Nyheter vist per side
            $perside = (int) $_REQUEST['perside'];
            if(!empty($perside)) {
                $validperside = array(5, 10, 20, 30, 50, 100);
                $limits['pages']['perside'] = (in_array($perside, $validperside)) ? $perside : 10;
            }
        } 
        
        // Default ved bruk av nullstill
        if(empty($limits['pages']['perside'])) {
            $limits['pages']['perside'] = 10;
        }
        
        // Pagination
        $limits['pages']['count'] = NyhetFactory::getNyheterMedLimits($limits, true);

        // Antall sider (ingen integer division i php, slapp av)
        $limits['pages']['numpages'] = ceil($limits['pages']['count'] / $limits['pages']['perside']);
        // Current page
        $currpage = (int) $_GET['visside'];
        if($currpage > 1 && !($currpage > $limits['pages']['numpages'])) {
            $limits['pages']['currpage'] = $currpage;
        } else {
            $limits['pages']['currpage'] = 1;
        }

        $objNyhetCol = NyhetFactory::getNyheterMedLimits($limits);
        $objMineNyheterCol = NyhetFactory::getMineNyheterForBrukerId($this->_userID, false);
        
        $arkiv_selflink_params = NyhetGen::genArkivLinkParams($limits);
        $returntoextras = $arkiv_selflink_params . '&amp;visside=' . $limits['pages']['currpage'];
        $html_tools = NyhetGen::genArkivOptions($limits, $arkiv_selflink_params);
        $output = $html_tools[0];
        $pagination = $html_tools[1];
        
		if ($objNyhetCol->length() === 0) {
			return $output . '<br />' . NyhetGen::genIngenNyheter('<br />Ingen nyheter matcher filterne du satt.');
		}
        foreach ($objNyhetCol as $objNyhet) {
            $options = array();
            if($objMineNyheterCol->exists( $objNyhet->getId() )) {
                $options[] = 'fjernmine';
            }
            $output .= NyhetGen::genFullNyhet($objNyhet, $options, 'arkiv', $returntoextras);
        }
        
        $pre = '<h1>Nyhetsarkiv</h1><div class="level2">';
        $post = '</div>';
        
		return $pre . $output . '<br />' . $pagination . $post;
        
    }
    
    public function gen_ext_view() {
        $inputpath = ($this->_msmodulvars[0]) ?: $_REQUEST['id'];
        
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
        
        $objMineNyheterCol = NyhetFactory::getMineNyheterForBrukerId($this->_userID, false);
        $objUlestCol = NyhetFactory::getUlesteNyheterForBrukerId($this->_userID, false);
        $options = array();
        if($objUlestCol->exists( $objNyhet->getId() )) {
            $options[] = 'lest';
        }
        if($objMineNyheterCol->exists( $objNyhet->getId() )) {
            $options[] = 'fjernmine';
        }
        
        $id_for_returnto = '&amp;id=' . $objNyhet->getWikiPath();
        
        return '<div class="minside"><p>' . NyhetGen::genFullNyhet($objNyhet, $options, 'extview', $id_for_returnto) . '</p></div>';
    }
    
    public function gen_nyheter_upub() {
        $objNyhetCol = NyhetFactory::getUpubliserteNyheter();
        $pre = '<h1>Upubliserte nyheter</h1><div class="level2">';
        $post = '</div>';
        
        if ($objNyhetCol->length() === 0) {
			return $pre . NyhetGen::genIngenNyheter('Upubliserte nyheter vises kun for områder hvor du har rett til å opprette nye nyheter.') . $post;
		}
		
        foreach ($objNyhetCol as $objNyhet) {
            $output .= NyhetGen::genFullNyhet($objNyhet, array('publiser'), 'upub');
        }
        
		return $pre . $output . $post;
    }
    
    public function publiser_nyhet() {
        $inputid = (int) $_REQUEST['nyhetid'];
        
        try{
            $objNyhet = NyhetFactory::getNyhetById($inputid);
        } catch (Exception $e) {
            msg('Klarte hente nyhet med id: ' . htmlspecialchars($inputid), -1);
            return false;
        }
        
        if($objNyhet->getAcl() < MSAUTH_3) throw new Exception('Kan ikke publisere nyhet: manglende tilgang.');
        if($objNyhet->isDeleted()) throw new Exception('Kan ikke publisere nyhet, nyhet er slettet.');
        if($objNyhet->isPublished()) throw new Exception('Kan ikke publisere nyhet, nyhet er allerde publisert. Rediger nyher for å endre publiseringstidspunkt.');
        
        
        if ($objNyhet->publiserNaa()) {
            msg('Nyhet publisert: "'. $objNyhet->getTitle(true) .'"', 1);
        } else {
            msg("Klarte ikke å publisere nyhet", -1);
        }
        
        if (isset($_REQUEST['returnto'])) {
            $this->_msmodulact = $_REQUEST['returnto'];
            return self::$dispatcher->dispatch($_REQUEST['returnto']);
        } else {
            $this->_msmodulact = 'upub';
            return self::$dispatcher->dispatch('upub');
        }
    }
	
	public function gen_nyheter_del() {
		
        $objNyhetCol = NyhetFactory::getDeletedNyheter();
        $pre = '<h1>Slettede nyheter</h1><div class="level2">';
        $post = '</div>';
      
		if ($objNyhetCol->length() === 0) {
			return $pre . NyhetGen::genIngenNyheter() . $post;
		}
		
        foreach ($objNyhetCol as $objNyhet) {
            $output .= NyhetGen::genFullNyhetDeleted($objNyhet);
        }
        
		return $pre . $output . $post;
	}
    
    public function gen_edit_nyhet() {
    
        $nyhetid = $_REQUEST['nyhetid'];
        try{
            $objNyhet = NyhetFactory::getNyhetById($nyhetid);
        } catch (Exception $e) {
            msg('Klarte ikke å laste redigeringsverktøy for nyhet med id: ' . htmlspecialchars($nyhetid), -1);
            return false;
        }
        
        $pre = '<h1>Rediger nyhet</h1><div class="level2">';
        $post = '</div>';
        
        return $pre . NyhetGen::genEdit($objNyhet) . $post;
    
    }
    
    public function gen_add_nyhet() {
    
        $objNyhet = new MsNyhet();
        
        $pre = '<h1>Opprett nyhet</h1><div class="level2">';
        $post = '</div>';
        
        return $pre . NyhetGen::genEdit($objNyhet) . $post;
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
            // Valg av kategori som er merket "noselect" er kun gyldig dersom nyheten allerede har denne kategorien.
            if (($objKategori->getNavn() === $inputkategori) &&
                ( (!$objKategori->noSelect()) || ($objNyhet->getKategori() == $objKategori) ) ) 
            {
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
            // Valg av tag som er merket "noselect" er kun gyldig dersom nyheten allerede er tagget med denne tagen.
            if( ($objTag instanceof NyhetTag) &&
                ( (!$objTag->noSelect()) || ($objNyhet->hasTag($objTag)) ) )
            {
                $colSelectedTags->additem($objTag, $objTag->getId());
            } else {
                msg('Tag med id: ' . htmlspecialchars($k) . ' er ukjent eller ugyldig for denne nyheten!', -1);
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
                if ($timestamp && ($timestamp < time())) {
                    // Hvis publiseringstid er angitt, men før nåtid, settes publiseringstid til nå
                    // Dette da redigeringsskjema kan ha stått åpent lenge, og tidspunktet være gammelt.
                    $indato = date('Y-m-d');
                    $inhour = date('H');
                    $inmin = date('i');
                }
            }
            $res = $objNyhet->setPublishTime($indato . ' ' . $inhour . ':' . $inmin);
            if (!$res) msg('Ugyldig dato/klokkeslett for publiseringstidspunkt. Nyheten publiseres ikke før korrekt tidspunkt settes!', -1);
        }
        
        // Merk ulest for alle
        if ($_POST['merkulestalle'] && $objNyhet->isSaved() && $acl >= MSAUTH_3) {
            $res = $objNyhet->merkUlestForAlle();
            if ($res === false) {
                msg('Klarte ikke å merke nyhet ulest.', -1);
            } elseif ($res === 0) {
                msg('Merk ulest: Ingen brukere har markert denne nyheten som lest.');
            } else {
                msg('Merk ulest: ' . $res . ' brukere hadde markert nyheten som lest.', 1);
            }
        }
        
        // Innhold
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
            $pre = '<h1>Visning av enkeltnyhet</h1><div class="level2">';
            $post = '</div>';
            return $pre . NyhetGen::genFullNyhet($objNyhet) . $objNyhet->getWebbug() . $post;
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
        
        if ($objNyhet->getAcl() < MSAUTH_2) throw new Exception('Du har ikke adgang til å slette denne nyheten!');
		
		($objNyhet->slett())
			? msg('Slettet nyhet: ' . $objNyhet->getTitle(true), 1)
			: msg('Klarte ikke å slette nyhet med id: ' . $objNyhet->getId(), -1);
            
        if (isset($_REQUEST['returnto'])) {
            $this->_msmodulact = $_REQUEST['returnto'];
            return self::$dispatcher->dispatch($_REQUEST['returnto']);
        } else {
            $this->_msmodulact = 'show';
            return self::$dispatcher->dispatch('show');
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
        
        if ($objNyhet->getAcl() < MSAUTH_5) throw new Exception('Du har ikke adgang til å gjenopprette denne nyheten!');
		
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
        
        if ($objNyhet->getAcl() < MSAUTH_5) throw new Exception('Du har ikke adgang til å permanent slette denne nyheten!');
		
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
        
        if($objNyhet->getAcl() < AUTH_READ) throw new Exception('Kan ikke merke nyhet som lest, du har ikke tilgang til denne nyheten.');
        if($objNyhet->isDeleted() || !$objNyhet->isPublished()) throw new Exception('Kan ikke merke nyhet som lest, nyhet er slettet eller ikke publisert.');
        
        
        if ($objNyhet->merkLest($this->_userID)) {
            if(MinSide::DEBUG) {
                msg('Merket nyhet som lest: "'. $objNyhet->getTitle(true) .'"', 1);
            }
        } else {
            msg("Klarte ikke å merke nyhet som lest", -1);
        }
        
        if (isset($_REQUEST['returnto'])) {
            $this->_msmodulact = $_REQUEST['returnto'];
            return self::$dispatcher->dispatch($_REQUEST['returnto']);
        } else {
            $this->_msmodulact = 'ulest';
            return self::$dispatcher->dispatch('ulest');
        }
    }
    
    public function merk_alle_lest() {
        try{
            $NyhetCol = NyhetFactory::getUlesteNyheterForBrukerId($this->_userID, false);
        } catch (Exception $e) {
            msg('Klarte ikke å hente uleste nyheter.', -1);
            return false;
        }
        
        (MsNyhet::merk_flere_lest($NyhetCol)) ?
            msg('Merket alle nyheter lest', 1):
            msg('Klarte ikke å merke alle nyheter lest', -1);
        
        if (isset($_REQUEST['returnto'])) {
            $this->_msmodulact = $_REQUEST['returnto'];
            return self::$dispatcher->dispatch($_REQUEST['returnto']);
        } else {
            $this->_msmodulact = 'ulest';
            return self::$dispatcher->dispatch('ulest');
        }
    }
    
    public function gen_searchpagelookup() {
        $data = $this->_msmodulvars;
        if(!is_array($data) || empty($data)) {
            throw new Exception('Ugyldig data', 9400);
        }
        
        $errcount = 0;
        $output = '';
        
        $colPubNyheter = new NyhetCollection();
        foreach($data as $nyhet_wikipath) {
            try {
                $objNyhet = NyhetFactory::getNyhetByWikiPath($nyhet_wikipath);
            } catch (Exception $e) {
                if(MinSide::DEBUG) msg('Klarte ikke å laste nyhet med path: ' . $nyhet_wikipath, -1);
                $errcount++;
                continue;
            }
            
            if($objNyhet->getAcl() >= MSAUTH_1 && $objNyhet->isPublished() && !$objNyhet->isDeleted()) {
                $colPubNyheter->addItem($objNyhet, $objNyhet->getId());
            } else {
                if(MinSide::DEBUG) msg('Nyhet med path: ' . $nyhet_wikipath . ' ble blokkert fra visning grunnet pub dato eller acl.');
            }
        }

        return NyhetGen::genSearchTitleOnly($colPubNyheter);
    }
    
    public function gen_searchfullpage() {
        $data = $this->_msmodulvars;
        if(!is_array($data) || empty($data)) {
            throw new Exception('Ugyldig data', 9400);
        }
        
        $errcount = 0;
        $output = '';
        
        $colPubNyheter = new NyhetCollection();
        foreach($data as $nyhet_wikipath) {
            try {
                $objNyhet = NyhetFactory::getNyhetByWikiPath($nyhet_wikipath);
            } catch (Exception $e) {
                if(MinSide::DEBUG) msg('Klarte ikke å laste nyhet med path: ' . $nyhet_wikipath, -1);
                $errcount++;
                continue;
            }
            
            if($objNyhet->getAcl() >= MSAUTH_1 && $objNyhet->isPublished() && !$objNyhet->isDeleted()) {
                $colPubNyheter->addItem($objNyhet, $objNyhet->getId());
            } else {
                if(MinSide::DEBUG) msg('Nyhet med path: ' . $nyhet_wikipath . ' ble blokkert fra visning grunnet pub dato eller acl.');
            }
        }

        return NyhetGen::genSearchHits($colPubNyheter);
    }
    
    public function gen_bulklest_admin() {
        return NyhetGen::genBulklestAdmin();
    }
    
    public function do_bulklest() {
        global $msdb;
        $msdb->startTrans();
        
        if(!empty($_POST['bulklest_cutoff'])) {
            $cutoff = strtotime($_POST['bulklest_cutoff']);
        } else {
            $cutoff = time();
        }
        
        msg('Starter bulk-lesing av nyheter');
        msg('Cutoff satt til: ' . date('d.m.Y H:i', $cutoff));
        
        $ulest_counter = 0;
        
        $arUsers = MinSide::getUsers();
        $arActiveUserIds = array();
        foreach($arUsers as $usr) {
            if(!$usr['isactive']) {
                continue;
            }
            
            $colFiltered = new NyhetCollection();
            $colUnfiltered = NyhetFactory::getUlesteNyheterForBrukerId($usr['id'], false);

            foreach($colUnfiltered as $objNyhet) {
                $pubtime = strtotime($objNyhet->getPublishTime());
                if ($pubtime < $cutoff) {
                    $colFiltered->addItem($objNyhet);
                }
            }
            unset($colUnfiltered);
            
            $num_nyheter = $colFiltered->length();
            $ulest_counter += $num_nyheter;
            
            if(MinSide::DEBUG) msg('Merker ' . $num_nyheter . ' nyheter lest for bruker: ' .
                $usr['wikiname']);
            
            MsNyhet::merk_flere_lest($colFiltered, $usr['id']);
        }
        
        msg('Merket ' . $ulest_counter . ' nyheter lest!', 1);
        
        if($_POST['bulklest_dryrun']) {
            msg('Dry-run, roller back transaction!', 1);
            $msdb->rollBack();
        } else {
            msg('Ikke dry-run, comitter transaction!', 2);
            $msdb->commit();
        }
        
    }
    
    public function gen_reparse_admin() {
        return NyhetGen::genReparseAdmin();
    }
    
    public function do_reparse() {
        // Forsøk å øke execution time:
        if( !ini_get('safe_mode') ){
            set_time_limit(290);
        } 
        $timeout = (int) ini_get('max_execution_time');
        if($timeout < 10 && $timeout != 0) throw new Exception('max_execution_time er satt til mindre enn 10 sekund og scriptet klarte ikke å øke denne. Må økes manuellt.');

        $start_nyhet_nr = (int) $_POST['reparse_offset'];
        $colNyheter = NyhetFactory::getAlleNyheter($start_nyhet_nr, false);
        
        $res = MsNyhet::reparse_bulk($colNyheter, $start_nyhet_nr, $timeout); // Denne skriver direkte
        if($res === true) {
            msg('Reparse fullført!', 1);
        }
        
        // Returnerer ikke noe her, da vi outputer til sidebar, grunnet buffer-flushes i reparse-kode.
    }
    
    public function gen_import_admin() {
        $colSkrivbareOmrader = NyhetOmrade::getOmrader('msnyheter', MSAUTH_3);
        return NyhetGen::genImportAdmin($colSkrivbareOmrader);
    }
    
    public function import_nyheter() {
        if(MinSide::DEBUG) msg('Starter nyhetimport');
        
        $input_omrade = $_POST['importomrade'];
        // Hent områder bruker har create rights på
        $colSkrivbareOmrader = NyhetOmrade::getOmrader('msnyheter', MSAUTH_3);
        if($colSkrivbareOmrader->exists($input_omrade)) {
            $objOmrade = $colSkrivbareOmrader->getItem($input_omrade);
            $omrade = $objOmrade->getOmrade();
        } else {
            throw new Exception('Oppgitt område for importering eksisterer ikke, eller manglende tilgang.');
        }
        
        // Sjekk lese/skrivetilganger
        $filpath = DOKU_INC.'lib/plugins/minside/cache/';
        if(!is_writable(DOKU_INC.'lib/plugins/minside/cache')) {
            throw new Exception('Kan ikke importere nyheter: server kan ikke skrive til cache-mappen.');
        } 
        if(!is_readable(DOKU_INC.'lib/plugins/minside/cache/nyhet_import.txt')) {
            throw new Exception('Kan ikke importere nyheter: filen '.DOKU_INC.'lib/plugins/minside/cache/nyhet_import.txt eksisterer ikke eller er ikke lesbar av serveren.');
        }
        
        // Hvis vi er her har vi valid område å opprette nyheter i, lesetilgang til input-fil, og skrivetilgang for output fil.
        $raw_input = file_get_contents($filpath . 'nyhet_import.txt');
        $input_len = strlen($raw_input);
        if($input_len < 1) {
            throw new Exception('Input fil er tom.');
        }
        if(MinSide::DEBUG) msg('Checks passed, input har ' . $input_len . ' tegn.');
        
        // Hent ut nyheter, vi går ut fra at alle valid nyheter er i en <hidden> </hidden> tag.
        $pattern = "#<hidden\b([^>]*)>(.*?)</hidden>#is";
        $matches = array();
        preg_match_all($pattern, $raw_input, $matches, PREG_SET_ORDER);
        $num_matches = count($matches);
        if(MinSide::DEBUG) msg('Fant ' . $num_matches . ' nyheter i inputfil.');
        if($num_matches < 1) {
            throw new Exception('Import feilet, fant ingen nyheter i inputfil. (Feilet i første søk)');
        }
        
        // Hent brukere
        $arUsers = MinSide::getUsers();
        
        // $matches er et 0-indeksert array med ett item for hver nyhet.
        // Hvert item i arrayet er et array med 3 items.
        // [0] inneholder hele strengen som matchet, altså <hidden x="y">Her er nyheten</hidden>
        // [1] inneholder innhold i åpningstagen, altså x="y"
        // [2] inneholder tekst mellom <hidden ... > og </hidden>, altså Her er nyheten
        
        
        $failoutput = '';
        $failcounter = 0;
        $okcounter = 0;
        foreach($matches as $key => $match) {
            $nyhetnummer = $key + 1;
            $match_feil = array();
            
            // Finn overskrift
            $omatches = array();
            preg_match('/\*\*(.*?)\*\*/is', $match[1], $omatches);
            $overskrift = trim($omatches[1], ' #');
            if(!strlen($overskrift)) {
                if(MinSide::DEBUG) msg('Nyhet nummer ' . $nyhetnummer . ' feilet pga. tom overskrift', -1);
                $match_feil[] = "Feilet på overskrift";
            }
            
            // Finn bruker
            $bmatches = array();
            preg_match('#---[\w\s]*//\[\[[^\|]*\|(.*?)\]\]#is', $match[1], $bmatches);
            $brukernavn = trim($bmatches[1]);
            if(!strlen($brukernavn)) {
                if(MinSide::DEBUG) msg('Nyhet nummer ' . $nyhetnummer . ' feilet pga. manglende brukernavn', -1);
                $match_feil[] = "Feilet på brukernavn: ikke funnet i input";
            } else {
                $brukerid = false;
                foreach($arUsers as $arUser) {
                    if($arUser['wikifullname'] == $brukernavn) {
                        $brukerid = $arUser['id'];
                    }
                }
                if($brukerid === false) {
                    if(MinSide::DEBUG) msg('Nyhet nummer ' . $nyhetnummer . ' feilet pga. bruker ikke i database: ' . $brukernavn, -1);
                    $match_feil[] = 'Feilet på brukernavn: bruker ikke i database';
                }
            }

            // Finn tidspunkt
            $tmatches = array();
            // Format på datestring ble endret på et tidspunkt, slik at noen nyheter har yyyy/mm/dd og noen har dd/mm/yyyy
            preg_match('#([0-9]{2,4})/([0-9]{2})/([0-9]{2,4})\s([0-2][0-9]):([0-5][0-9])#is', $match[1], $tmatches);
            $raw_tid = trim($tmatches[0]);
            $dato1 = $tmatches[1];
            $mnd = $tmatches[2];
            $dato2 = $tmatches[3];
            $time = $tmatches[4];
            $minutt = $tmatches[5];
            if(strlen($dato1) == 4) {
                $aar = $dato1;
                $dag = $dato2;
            } else {
                $aar = $dato2;
                $dag = $dato1;
            }
            $timestamp = mktime($time, $minutt, 0, $mnd, $dag, $aar);
            $checkyear = date('Y', $timestamp);
            if($checkyear < 2000 || $checkyear > 2020) {
                if(MinSide::DEBUG) msg('Nyhet nummer ' . $nyhetnummer . ' feilet pga. ugyldig eller manglende dato', -1);
                $match_feil[] = "Feilet på dato";
            }
            
            // Finn innhold
            $nyhettekst = trim($match[2]);
            if(strlen($nyhettekst) < 1) {
                if(MinSide::DEBUG) msg('Nyhet nummer ' . $nyhetnummer . ' feilet pga. manglende innhold', -1);
                $match_feil[] = "Feilet på innhold (tom nyhet)";
            }
            
            // Dersom ingen feil sålangt forsøker vi å opprette nyhet
            if(count($match_feil) == 0) {
            
                try{
                    $objNyhet = new MsNyhet();
                    
                    $objNyhet->setTitle($overskrift);
                    $objNyhet->setOmrade($omrade);
                    // Wikipath
                    $path = str_replace(array(':', ';', '/', '\\'), '_' , $overskrift);
                    if (strlen($path) > MsNyhet::PATH_MAX_LEN) $path = substr($path, 0, MsNyhet::PATH_MAX_LEN);
                    $path = 'msnyheter:'.$omrade.':' . date('YmdHis', $timestamp) . 'old_' . $path;
                    $path = cleanID($path, true);
                    $objNyhet->setWikiPath($path);
                    
                    $objNyhet->setType(1);
                    $objNyhet->setIsSticky(false);
                    $objNyhet->setImagePath('');
                    
                    $pubtime = date('Y-m-d H:i', $timestamp);                    
                    $objNyhet->setPublishTime($pubtime);
                    
                } catch(Exception $e) {
                    if(MinSide::DEBUG) msg('Nyhet nummer ' . $nyhetnummer . ' feilet under generering av objekt ', -1);
                    $match_feil[] = "Feilet på generering av objekt: " . $e->getMessage();
                }
                
                try{
                    $objNyhet->setWikiTekst($nyhettekst, false);
                    $objNyhet->update_db($brukerid, $pubtime);
                } catch(Exception $e) {
                    if(MinSide::DEBUG) msg('Nyhet nummer ' . $nyhetnummer . ' feilet under lagring av objekt ', -1);
                    $match_feil[] = "Feilet på lagring av objekt: " . $e->getMessage();
                }
                
            
            }
            
            // Sjekk for feil, legg nyhet med info om hva som feilt i streng som skrives til fil
            if(count($match_feil) > 0) {
                $failcounter++;
                $failtekst = implode("\n", $match_feil);
                $failoutput .= $failtekst . "\n---------------\n" . $match[0] . "\n\n\n";
            } else {
                $okcounter++;
                
                // Lagre path til bruk i webbugs for å oppdatere indexes
                $webbugs[] = $objNyhet->getWikiPath();
            }
        }
        
        // Update indexes
        if(MinSide::DEBUG) msg('Generer ' . count($webbugs) . ' webbugs, for å oppdatere indexes.');
        $output .= '<div class="no">';
        foreach ($webbugs as $path) {
            $output .= '<img src="'.DOKU_BASE.'lib/exe/indexer.php?id='.$path.'" width="1" height="1" alt="" />' . "\n";
        }
        $output .= '</div>';
        
        
        msg('Importsekvens ferdig. ' . $okcounter . ' nyheter importert, ' . $failcounter . ' feilet.');
        
        if ($failoutput) {
            $fil = $filpath . date('YmdHis') . '.failedimport.txt';
            file_put_contents($fil, $failoutput);
        }
        
        
        return $output;
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
    
    public function gen_redirect_forside() {
        $output = '
            Sender deg tilbake til forsiden...<br />
            Trykk <a href="'.DOKU_BASE.'doku.php">her</a> dersom du ikke blir tatt videre innen få sekunder.
            <script type="text/javascript"><!--//--><![CDATA[//><!--
                window.location="'.DOKU_BASE.'doku.php"
            //--><!]]></script>
            ';
        return $output;
    }
    
    public function gen_readlog_admin() {
        return NyhetGen::genReadLogAdmin();
    }
    
    public function gen_read_log() {
        $fra = ($_POST['fratid']) ? date('Y-m-d H:i:s', strtotime($_POST['fratid'])) : null;
        $til = ($_POST['tiltid']) ? date('Y-m-d H:i:s', strtotime($_POST['tiltid'])) : null;
        
        msg('Fratid: ' . $fra);
        msg('Tiltid: ' . $til);
        
        $log = NyhetFactory::getReadLog($fra, $til);
        
        $output = '<h1>Logg over leste nyheter</h1><pre>';
        $output .= "NyhetsID\tMerket som lest\tPublisert\tOverskrift\tOpprettet av\tKategori\tTags\n";
        foreach($log as $item) {
            $output .= 
                $item['nyhetid'] . "\t" .
                $item['readtime'] . "\t" .
                $item['pubtime'] . "\t" .
                $item['nyhettitle'] . "\t" .
                $item['wikifullname'] . "\t" .
                $item['kategori'] . "\t" .
                $item['tagnavn'] . "\n";
        }
        $output .= '</pre>';
        
        return $output;
    }

}
