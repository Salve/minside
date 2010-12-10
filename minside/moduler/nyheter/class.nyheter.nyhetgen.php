<?php
if(!defined('MS_INC')) die();

class NyhetGen {

	const THUMB_BREDDE = 100;
	const TIME_FORMAT = 'd.m.Y \k\l. H.i';
    const TAGSELECTOR_TAGS_PER_ROW = 8;

    public static $mnd_navn_kort = array(
        1 => 'jan',
        2 => 'feb',
        3 => 'mar',
        4 => 'apr',
        5 => 'mai',
        6 => 'jun',
        7 => 'jul',
        8 => 'aug',
        9 => 'sep',
        10 => 'okt',
        11 => 'nov',
        12 => 'des'
        );
    
    public static $dag_navn_kort = array(
        0 => 'søn',   
        1 => 'man',   
        2 => 'tir',   
        3 => 'ons',   
        4 => 'tor',   
        5 => 'fre',   
        6 => 'lør',   
    );
    
	private function __construct() { }
	
	public static function genFullNyhetViewOnly(msnyhet &$nyhet) {
		return self::_genFullNyhet($nyhet);
	}
	
	public static function genFullNyhet(msnyhet &$nyhet, array $extraoptions = array(), $returnto = NULL, $extra_url_params='') {
        $acl = $nyhet->getAcl();
        $arOptions = array();
        
        switch($acl) {
            case MSAUTH_ADMIN:
            case MSAUTH_5:
                $arOptions[] = 'stats';
            case MSAUTH_4:
            case MSAUTH_3:
            case MSAUTH_2:
                $arOptions[] = 'edit';
                $arOptions[] = 'slett';
            case MSAUTH_1:
                $arOptions[] = 'link';
                break;
            case MSAUTH_NONE:
                break;
        }
        
        $arOptions = array_merge($arOptions, $extraoptions);
		return self::_genFullNyhet($nyhet, $arOptions, $returnto, $extra_url_params);
	}
	
	public static function genFullNyhetDeleted(msnyhet &$nyhet, $returnto = NULL, $extra_url_params='') {
		$arOptions = array(
			'restore',
			'permslett'
		);
		
		return self::_genFullNyhet($nyhet, $arOptions, $returnto, $extra_url_params);
	}
	
	private static function _genFullNyhet(msnyhet &$nyhet, array $inoptions=array(), $returnto=NULL, $extra_url_params='') {
		// Data
        $type = $nyhet->getType();
		$id = $nyhet->getId();
        $title = $nyhet->getTitle(true);
		$body = $nyhet->getHtmlBody();
        $omrade = $nyhet->getOmrade();
        $objKategori = $nyhet->getKategori();
        $omradeinfo = NyhetOmrade::getVisningsinfoForNyhet($nyhet, 'msnyheter');
        if ($nyhet->hasImage()) {
			$img = $nyhet->getImageTag(self::THUMB_BREDDE);
		} else {
			$img = '';
		}
        
        // HTML
        $returnto_html = ($returnto) ? '&amp;returnto='.$returnto.$extra_url_params : $extra_url_params;
        $omrade_html = '<div class="nyhetomrade">Område: <a href="'.MS_NYHET_LINK.'&amp;act=arkiv&amp;fomrader[]='. $omrade . 
                '" title="område:' . $omrade . '">' . $omradeinfo['visningsnavn'] . '</a></div>';
        if($_GET['pink'] == 'manly') {
            $farge = 'FF00FF';
        } else {
            $farge = $omradeinfo['farge'];
        }
        $omrade_farge = ($farge) ? ' style="background-color: #' . $farge . ';"' : '';
        $kategori_html = '<div class="nyhetkategori">Kategori: <a href="'.MS_NYHET_LINK.'&amp;act=arkiv&amp;fkat[]='. $objKategori->getId() . 
                '" title="kategori:' . $objKategori->getNavn() . '">' . $objKategori->getNavn() . '</a></div>';
        $tags_html = self::genTagList($nyhet->getTags());
        $lastmod = ($nyhet->isModified())
			? '<div class="nyhetmod">Sist endret '. self::dispTime($nyhet->getLastModTime()) .
				' av ' . self::getMailLink($nyhet->getLastModByNavn(), $nyhet->getLastModByEpost()) . '</div>'
			: '';
		$delete = ($nyhet->isDeleted()) 
			? '<div class="nyhetdel">Nyhet slettet '. self::dispTime($nyhet->getDeleteTime()) .
				' av ' . self::getMailLink($nyhet->getDeleteByNavn(), $nyhet->getDeleteByEpost()) . '</div>'
			: '';
        $sticky = ($nyhet->isSticky()) 
			? '<img alt="sticky" title="Sticky nyhet" class="sticky" src="' .
				MS_IMG_PATH . 'NeedleLeftYellow.png" />' 
			: '' ;
        if (!$nyhet->getPublishTime()) {
            $publish = '<div class="nyhetpub">Nyhet publiseres ikke! Dato ikke satt.</div>';
        } elseif (strtotime($nyhet->getPublishTime()) < time()) {
            $tid_siden = $nyhet->getTidSidenPub();
            $publish = '<div class="nyhetpub">Publisert '. self::dispTime($nyhet->getPublishTime()) .
				' (' . $tid_siden . ' siden) av ' . self::getMailLink($nyhet->getCreateByNavn(), $nyhet->getCreateByEpost()) . '</div>';
        } else {
            $publish = '<div class="nyhetpub">Publiseres '. self::dispTime($nyhet->getPublishTime()) . '</div>';
        }
		
        // Options/icon
		$opt['link'] = '<a href="' . wl($nyhet->getWikiPath()) . '">' .
            '<img alt="link" title="Direktelenke til nyhet" width="16" ' .
            'height="16" src="' . MS_IMG_PATH . 'link.png" /></a>';
		$opt['edit'] = '<a href="' . MS_NYHET_LINK . "&amp;act=edit&amp;nyhetid=$id\">" .
            '<img alt="rediger" title="Rediger nyhet" width="16" ' .
            'height="16" src="' . MS_IMG_PATH . 'pencil.png" /></a>';
		$opt['slett'] = '<a href="' . MS_NYHET_LINK . $returnto_html . "&amp;act=slett&amp;nyhetid=$id\"  onClick='return heltSikker()'>" .
            '<img alt="slett" title="Slett nyhet" width="16" ' .
            'height="16" src="' . MS_IMG_PATH . 'trash.png" /></a>';
		$opt['permslett'] = '<a href="' . MS_NYHET_LINK . "&amp;act=permslett&amp;nyhetid=$id\"  onClick='return heltSikker()'>" .
            '<img alt="permslett" title="Slett nyhet permanent" width="16" ' .
            'height="16" src="' . MS_IMG_PATH . 'trash.png" /></a>';
		$opt['restore'] = '<a href="' . MS_NYHET_LINK . "&amp;act=restore&amp;nyhetid=$id\">" .
            '<img alt="gjenopprett" title="Gjenopprett nyhet" width="16" ' .
            'height="16" src="' . MS_IMG_PATH . 'success.png" /></a>';
        $opt['stats'] = '<a href="' . MS_NYHET_LINK . $returnto_html . "&amp;act=nyhetstats&amp;nyhetid=$id\">" .
            '<img alt="stats" title="Statistikk for enkeltnyhet" width="16" ' .
            'height="16" src="' . MS_IMG_PATH . 'bargraf.gif" /></a>';
        $opt['publiser'] = '<a href="' . MS_NYHET_LINK . $returnto_html . "&amp;act=pubnaa&amp;nyhetid=$id\">" .
            '<img alt="stats" title="Publiser nyhet nå" width="16" ' .
            'height="16" src="' . MS_IMG_PATH . 'iconCalendar.gif" /></a>';
		
		foreach ($inoptions as $k => $v) {
            if (array_key_exists($v, $opt)) {
                $options[] = $opt[$v];
            }
		}
		if (!empty($options)) {
			$valg = implode('&nbsp;', $options);
		} else {
			$valg = '';
		}
        if(array_search('lest', $inoptions)) {
            $lest_html = '<div class="nyhetlestbox"><a href="' . MS_NYHET_LINK . $returnto_html . "&amp;act=lest&amp;nyhetid=$id\">" .
            '<img src="' . MS_IMG_PATH . 'konv.png"> Merk som lest</a></div>';
        } else {
            $lest_html = '';
        }
        
        // Wannabetemplate :D
		$output = "
			<div class=\"nyhetcontainer\">
			<div class=\"nyhet $omrade\">
				<!-- NyhetsID: $id -->
				<div class=\"nyhettopbar\"$omrade_farge>
                    <div class=\"seksjontopp\">
                        <div class=\"nyhetoptions\">$valg</div>
                        <div class=\"nyhettitle\">$sticky$title</div>
                        $lest_html
                    </div>
					<div class=\"nyhetinfo\">
                        <div class=\"nyhetinforight\">
                            $omrade_html$kategori_html
                        </div>
                        <div class=\"nyhetinfoleft\">
                            $publish$lastmod$delete
                        </div>
                        <div class=\"msclearer\"></div>
                    </div>
                    <div class=\"msclearer\"></div>
				</div>
				<div class=\"nyhetcontent\">
					<div class=\"nyhetimgleft\">$img</div>
					<div class=\"nyhetbody\">$body</div>
                    $tags_html
					<div class=\"msclearer\"></div>
				</div>
			</div>
			</div>
		";
		
		return $output;
		
	}
    
    public static function genSearchTitleOnly(NyhetCollection $nyhetCol) {
        
        if($nyhetCol->length() < 1) return '';
        
        $output = '<ul class="search_quickhits">' . "\n";
        
        foreach($nyhetCol as $objNyhet) {
            $path = $objNyhet->getWikiPath();
            $url = wl($path);
            $title = $objNyhet->getTitle();
            
            $output .= '<li><a href="'. $url .'" class="wikilink1" title="'. $path .'">'. $title .'</a></li>' . "\n";
        }
        
        $output .= '</ul>' . "\n";
        
        return $output;
    }
    
    public static function genSearchHits(NyhetCollection $nyhetCol) {
        
        if($nyhetCol->length() < 1) return '';
        
        foreach($nyhetCol as $objNyhet) {
            $output .= self::genFullNyhet($objNyhet);
        }
        
        
        return '<div class="minside">' . $output . '</div>';
    }

    public static function genEdit(msnyhet &$objNyhet, $preview=false) {
		// Område
		if ($objNyhet->isSaved()) {
			$html_omrade = '<div class="nyhetomradevelger">Område:
				<input class="edit" style="width:58px;" type="text" name="nyhetomrade" value="' . $objNyhet->getOmrade() . '" disabled /></div>';
		} else {
			$colOmrader = NyhetOmrade::getOmrader('msnyheter', AUTH_CREATE);
			$html_omrade = '<div class="nyhetomradevelger">Område: <select name="nyhetomrade" tabindex="3" class="edit">';
			if ($colOmrader->length() === 0) {
				$html_omrade .= 'Du har ikke tilgang til noen områder!';
            } elseif ($objNyhet->getOmrade() != false) {
                foreach ($colOmrader as $objOmrade) {
					$html_omrade .= '<option value="' . $objOmrade->getOmrade() . '"' .
                    (($objOmrade->getOmrade() == $objNyhet->getOmrade()) ? ' selected="selected"' : '') .
                    '>'. $objOmrade->getOmrade() . '</option>';
				}
			} else {
				foreach ($colOmrader as $objOmrade) {
					$html_omrade .= '<option value="' . $objOmrade->getOmrade() . '"' .
                    (($objOmrade->isDefault()) ? ' selected="selected"' : '') .
                    '>'. $objOmrade->getOmrade() . '</option>';
				}
			}
			$html_omrade .= '</select></div>';
			
		}
        
        // Kategori
        $colKategorier = NyhetTagFactory::getAlleNyhetTags(true, true, false, NyhetTag::TYPE_KATEGORI);
        $html_kategori = '<div class="nyhetkategorivelger">Kategori: <select name="nyhetkategori" tabindex="5" class="edit">';
        $html_kategori .= '<option value="0">Velg: </option>';
        foreach ($colKategorier as $objKategori) {
            if($objKategori == $objNyhet->getKategori()) {
                $selected = ' selected="selected"';
            } else {
                // Kategorier som ikke skal kunne velges vises bare dersom de allerede er aktive på nyheten
                if($objKategori->noSelect()) continue;
                $selected = '';
            }
            $html_kategori .= '<option value="' . $objKategori->getNavn() . '"' . $selected .
            '>' . $objKategori->getNavn() . '</option>';
        }
        $html_kategori .= '</select></div>'; // nyhetkategorivelger
        
        // Tags
        $colAllTags = NyhetTagFactory::getAlleNyhetTags(true, true, false, NyhetTag::TYPE_TAG);
        $colTagsSomSkalVises = new NyhetTagCollection();
        foreach($colAllTags as $objTag) {
            // Tags som ikke skal kunne velges vises bare dersom de allerede er aktive på nyheten
            if(!$objTag->noSelect() || $objNyhet->hasTag($objTag)) {
                $colTagsSomSkalVises->addItem($objTag, $objTag->getId());
            }
        }
        // Returnerer array med tag-collections, parameter 2 er bredde på radene, en collection per rad
        $arTagCollections = self::tableSortColTags($colTagsSomSkalVises, self::TAGSELECTOR_TAGS_PER_ROW);
		$html_tags = '<div class="nyhettagvelger">Tags:&nbsp;';
        $i = 0;
        $html_tags .= "<table>\n";
        foreach ($arTagCollections as $colTagRow) {
            $html_tags .= "<tr>\n";
            foreach ($colTagRow as $objTag) {
                $checked = ($objNyhet->hasTag($objTag)) ? 'checked="checked"' : '';
                $html_tags .= '<td class="tagtable"><input type="checkbox" class="edit" id="tag' . ++$i . 
                    '" name="nyhettags[' . $objTag->getId() . ']" '.$checked.' />&nbsp;' . 
                    '<label for="tag' . $i . '">' . $objTag->getNavn() . "</label></td> \n";
            }
            $html_tags .= "</tr>\n";
        }
        $html_tags .= '</table></div>'; // nyhettagvelger
        
		// Sticky
		$checked = ($objNyhet->isSticky()) ? ' checked="checked"' : '';
		$html_sticky = '<div class="nyhetvelgsticky"><label for="stickycheckbox">Skal nyheten være <acronym title="' .
			'Sticky nyheter vises øverst i listen med aktuelle nyheter, og blir liggende der til sticky-merking manuellt fjernes.">sticky</acronym>?</label>' .
			' <input id="stickycheckbox" class="edit" value="sticky" type="checkbox" name="nyhetsticky" '.$checked.' /></div>';
		
		// Bilde
		$html_bilde = '<div class="nyhetbildevelger"><div class="nyhetsettext">Bilde:</div> <input class="edit" type="text" tabindex="4" name="nyhetbilde" id="nyhet__imgpath"' .
			'value="' . $objNyhet->getImagePath() . '" /> ' .
			'<img onClick="openNyhetImgForm('.$objNyhet->getId().')" class="ms_imgselect_nyhet" alt="Velg bilde" ' .
			'title="Legg til bilde" width="16" ' .
            'height="16" src="' . MS_IMG_PATH . 'image.png" />&nbsp;<img alt="Slett" title="Tøm felt" class="" src="' .
				MS_IMG_PATH . 'trash.png" height="16" width="16" onClick="cleartxt(\'nyhet__imgpath\');" /></div>';
		
		// Publiseringsdato
        // Vises kun dersom bruker har create rigths på nyhetområdet
        if(!$objNyhet->isSaved() || $objNyhet->getAcl() >= MSAUTH_3) {
            $pubtime = $objNyhet->getPublishTime();
            if (!empty($pubtime)) {
                $pubtimestamp = strtotime($pubtime);
            } else {
                $pubtimestamp = time();
            }
            $minute = date('i', $pubtimestamp);
            $hour = date('H', $pubtimestamp);
            $dag = (int) date('j', $pubtimestamp);
            $md = (int) date('n', $pubtimestamp);
            $aar = (int) date('Y', $pubtimestamp);
            
            $objCalendar = new tc_calendar("nyhetpubdato", true);
            $objCalendar->setPath('lib/plugins/minside/minside/');
            $objCalendar->startMonday(true);
            $objCalendar->setIcon(MS_IMG_PATH . 'iconCalendar.gif');
            $objCalendar->setDate($dag, $md, $aar);
            $objCalendar->dateAllow('2010-01-01', '2020-01-01', false);
            
            ob_start(); // må ta vare på output...
            $objCalendar->writeScript();
            $html_calendar = '<div class="nyhetpubdatovelger">Publiseringsdato: ' . ob_get_clean();
            $html_calendar .= '&nbsp;&nbsp;kl. <input type="text" size="1" maxlength="2" onChange="checkHour(this.id);" value="'. 
                $hour .'" name="nyhetpubdato_hour" id="nyhetpubdato_hour" class="tchour msedit">';
            $html_calendar .= ':<input type="text" size="1" maxlength="2" onChange="checkMins(this.id);" value="'. 
                $minute .'" name="nyhetpubdato_minute" id="nyhetpubdato_minute" class="tcminute msedit">';
            $html_calendar .= '&nbsp;<img alt="Sett dags dato" align="absmiddle" onClick="setTodaysdate();" title="'.
                'Sett publiseringstidspunkt til nå, dette flytter nyhet til toppen av listen over aktuelle nyheter." src="' . MS_IMG_PATH . 'up.png" /></div>';
        } else {
            // Bruker har ikke create rights på området
            $html_calendar = '';
        }
        
        // Merk ulest for alle
        // Vises kun dersom bruker har create rights på nyhetsområde og nyhet er saved
        if($objNyhet->isSaved() && $objNyhet->getAcl() >= MSAUTH_3) {
            $html_merkulest = '<div class="nyhetmerkulestalle"><label for="ulestallecheckbox">Merk nyhet som ulest for alle brukere?</label>' .
			' <input id="ulestallecheckbox" class="edit" type="checkbox" name="merkulestalle" /></div>';
        } else {
            // Bruker har ikke create rights på området eller nyhet er ikke lagret enda
            $html_merkulest = '';
        }
        
        // Wikitekst
        $rawwiki = $objNyhet->getWikiTekst();
        if (empty($rawwiki)) {
            $rawwiki = formText(rawWiki($objNyhet->getWikiPath()));
        }
        
        
        // "Template"
        $output .= '
                    <div class="editnyhet">
                        <div class="toolbar">
                            <div id="draft__status"></div>
                            <div id="tool__bar">
                                <a href="'.DOKU_BASE.'lib/exe/mediamanager.php?ns=msnyheter" target="_blank">
                                    Valg av mediafil
                                </a>
                            </div>

                            <script type="text/javascript" charset="utf-8">
                                <!--//--><![CDATA[//><!-- textChanged = false; //--><!]]>
                            </script>
                        </div> <!-- toolbar -->
                        <form id="dw__editform" action="' . MS_NYHET_LINK . '&amp;act=subedit" method="POST">
                            <input type="hidden" name="nyhetid" value="' . $objNyhet->getId() . '" />
                            <input type="hidden" name="id" value="'.$objNyhet->getWikiPath().'" />
                            <input type="hidden" name="rev" value="" />
                            <textarea name="wikitext" id="wiki__text" class="edit nyhet__edit" cols="80" rows="10" tabindex="1">'
                               . $rawwiki .
                            '</textarea>
                            <input type="hidden" id="edit__summary" />
                            <div class="nyhetattrib">
                                <div class="msnyhetoverskrift">
                                    <div class="nyhetsettext">Overskrift:</div> <input class="edit" style="width:30em;" type="text" tabindex="2" name="nyhettitle" maxlength="'.MsNyhet::TITLE_MAX_LEN.'" value="' . $objNyhet->getTitle(true) . '" />
                                </div>'
                                .$html_omrade. '
                                <div class="msclearer"></div>'
                                .$html_bilde
                                .$html_kategori. '
                                <div class="msclearer"></div>'
                                .(($html_calendar) ?: '')
                                .$html_sticky. '
                                <div class="msclearer"></div>'
                                .$html_merkulest. '
                                <div class="msclearer"></div>'
                                .$html_tags. '
                                <div class="msclearer"></div>
                            </div>
                            <div id="wiki__editbar" class="editnyhet">
                                <div id="size__ctl" ></div>
                                <div class="editButtons" >
                                    <input name="editsave" type="submit" value="Lagre" class="button" id="edbtn__save" accesskey="s" tabindex="6" title="Lagre [S]" />
                                    <input name="editpreview" type="submit" value="Forhåndsvis" class="button" id="edbtn__preview" accesskey="p" tabindex="7" title="Forhåndsvis [P]" />
                                    <input name="editabort" type="submit" value="Avbryt" class="button" tabindex="8" />
                                </div>
                            </div>
                        </form>
                    </div>'; // editnyhet
        
        if ($preview) $output .= self::genFullNyhetViewOnly($objNyhet);
		
        return $output;
    }
	
	public static function genIngenNyheter($ekstratekst='') {
        if ($ekstratekst) $ekstratekst = '<p>' . $ekstratekst . '</p>';
		return '<div class="mswarningbar"><strong>Ingen nyheter her!</strong><br /><br />'.$ekstratekst.'</div>';
	}
    
    public static function genReadLogAdmin() {
        return '<h2>Logg over leste nyheter</h2>
                    <div class="readlogadm level3">
                    <form action="' . MS_NYHET_LINK . '&amp;act=readlog" method="POST">
                    <p>Flere formater på dato kan benyttes, hvis / benyttes som skilletegn oppfattes dato som amerikansk format.<br />
                    Kan også skrive relative datoer, f.eks. -1 week, last monday eller first day last month<br />
                    La felt stå blanke for ingen begrensning.</p>
                    Fratid:&nbsp;<input type="text" name="fratid" value="-1 week" /><br />
                    Tiltid:&nbsp;&nbsp;<input type="text" name="tiltid" value="now" />
                    <br /><br /><input type="submit" value="Hent logg" class="button" /></form></div>';
    }
    
    public static function genOmradeAdmin($colOmrader) {
        $output .= "<h2>Områdeadministrasjon</h2>\n";
        
        $output .= '<div class="omradeadmwrap level3">
            <form action="' . MS_NYHET_LINK . '&amp;act=subomradeadm" method="POST">
            <table class="omradeadmtbl">
                <tr>
                    <th>Område </th>
                    <th>Default </th>
                    <th>Visningsnavn </th>
                    <th>Farge </th>
                </tr>
        ';
        foreach($colOmrader as $objOmrade) {
            $omrade = $objOmrade->getOmrade();
            $visningsnavn = $objOmrade->getVisningsnavn();
            $farge = $objOmrade->getFarge();
            $checked = ($objOmrade->isDefault()) ? ' checked="checked"' : '';
            $output .= "
                <tr>
                    <td>$omrade</td>
                    <td><input type=\"radio\" class=\"edit\" name=\"defaultomrade\" value=\"$omrade\"$checked /></td>
                    <td><input type=\"text\" class=\"edit\" name=\"visnavn[$omrade]\" value=\"$visningsnavn\" /></td>
                    <td><input type=\"text\" class=\"edit\" name=\"farge[$omrade]\" value=\"$farge\" /></td>
                </tr>
            ";
        }
        $output .= '</table><input type="submit" value="Lagre" class="button" /></form></div>';
        
        return $output;
    }
    
    public static function genNyhetStats(msnyhet &$objNyhet, $mode=null, $innfratid=null, $inntiltid=null) {
        $pubtime = strtotime($objNyhet->getPublishTime());
        $nowtime = time();
        
        switch($mode) {
            case '0':
                $fratid = $innfratid;
                $tiltid = $inntiltid;
                break;
            case 1:
                $fratid = $pubtime;
                $tiltid = $nowtime;
                break;
            case 2:
                $fratid = null;
                $tiltid = null;
                break;
            case 3:
                $fratid = $pubtime;
                $tiltid = strtotime('+1 hour', $pubtime);
                break;
            case 4:
                $fratid = $pubtime;
                $tiltid = strtotime('+4 hours', $pubtime);
                break;
            case 5:
                $fratid = $pubtime;
                $tiltid = strtotime('+8 hours', $pubtime);
                break;
            case 6:
                $fratid = $pubtime;
                $tiltid = strtotime('+24 hours', $pubtime);
                break;
            case 7:
                $fratid = $pubtime;
                $tiltid = strtotime('+3 days', $pubtime);
                break;
            case 8:
                $fratid = $pubtime;
                $tiltid = strtotime('+7 days', $pubtime);
                break;
            case 9:
                $fratid = $pubtime;
                $tiltid = strtotime('+2 weeks', $pubtime);
                break;
            case 10:
                $fratid = $pubtime;
                $tiltid = strtotime('+1 month', $pubtime);
                break;
            case 11:
                $fratid = strtotime('-1 hour');
                $tiltid = $nowtime;
                break;
            case 12:
                $fratid = strtotime('-4 hours');
                $tiltid = $nowtime;
                break;
            case 13:
                $fratid = strtotime('-8 hours');
                $tiltid = $nowtime;
                break;
            case 14:
                $fratid = strtotime('-24 hours');
                $tiltid = $nowtime;
                break;
            case 15:
                $fratid = strtotime('-3 days');
                $tiltid = $nowtime;
                break;
            case 16:
                $fratid = strtotime('-7 days');
                $tiltid = $nowtime;
                break;
            case 17:
                $fratid = strtotime('-2 weeks');
                $tiltid = $nowtime;
                break;
            case 18:
                $fratid = strtotime('-1 month');
                $tiltid = $nowtime;
                break;
            case 19:
                // Sist uke man-fre
                $ukedag = date('w');
                if($ukedag == 1) {
                    $fratid = mktime(0,0,0,date('n'),date('j'),date('Y'));
                } else {
                    $fratid = strtotime('last monday');
                }
                if($ukedag == 5) {
                    $tiltid = mktime(23,59,59,date('n'),date('j'),date('Y'));
                } elseif ($ukedag == 6 || $ukedag == 0) {
                    $tiltid = strtotime('last friday 23:59:59');
                } else {
                    $tiltid = strtotime('next friday 23:59:59');
                }
                break;
            case 20:
                // Denne uke man-søn
                $ukedag = date('w');
                if($ukedag == 1) {
                    $fratid = mktime(0,0,0,date('n'),date('j'),date('Y'));
                } else {
                    $fratid = strtotime('last monday');
                }
                if($ukedag == 0) {
                    $tiltid = mktime(23,59,59,date('n'),date('j'),date('Y'));
                } else {
                    $tiltid = strtotime('next sunday 23:59:59');
                }
                break;
            case 21:
                // Sist uke man-fre
                $ukedag = date('w');
                if($ukedag == 1) {
                    $fratid = strtotime('last monday');
                } else {
                    $fratid = strtotime('-2 monday');
                }
                if ($ukedag == 6 || $ukedag == 0) {
                    $tiltid = strtotime('-2 friday 23:59:59');
                } else {
                    $tiltid = strtotime('last friday 23:59:59');
                }
                break;
            case 22:
                // Sist uke man-søn
                $ukedag = date('w');
                if($ukedag == 1) {
                    $fratid = strtotime('last monday');
                } else {
                    $fratid = strtotime('-2 monday');
                }
                $tiltid = strtotime('last sunday 23:59:59');
                break;
            case 23:
                // Denne måneden
                $month = date('n');
                $year = date('Y');
                $fratid = mktime(0,0,0,$month,1,$year);
                $tiltid = mktime(23,59,59,$month+1,0,$year);
                break;
            case 24:
                // Sist måned
                $month = date('n');
                $year = date('Y');
                $fratid = mktime(0,0,0,$month-1,1,$year);
                $tiltid = mktime(23,59,59,$month,0,$year);
                break;
            default:
                $fratid = $pubtime;
                $tiltid = $nowtime;
                break;
        }
        $fratid_tekst = ($fratid) ? date('d.m.Y H:i:s', $fratid) : '';
        $tiltid_tekst = ($tiltid) ? date('d.m.Y H:i:s', $tiltid) : '';
        
        // Valg av periode
        $section_periode = '<h2>Valg av periode</h2><div class="level3">';
        $section_periode .= '
            <ul class="nyhetstatspreselect">
                Tid fra publiseringsdato:
                <li><a href="'.MS_NYHET_LINK.'&amp;act=nyhetstats&amp;nyhetid='.$objNyhet->getId().'&amp;mode=3">1 time</a></li>
                <li><a href="'.MS_NYHET_LINK.'&amp;act=nyhetstats&amp;nyhetid='.$objNyhet->getId().'&amp;mode=4">4 timer</a></li>
                <li><a href="'.MS_NYHET_LINK.'&amp;act=nyhetstats&amp;nyhetid='.$objNyhet->getId().'&amp;mode=5">8 timer</a></li>
                <li><a href="'.MS_NYHET_LINK.'&amp;act=nyhetstats&amp;nyhetid='.$objNyhet->getId().'&amp;mode=6">24 timer</a></li>
                <li><a href="'.MS_NYHET_LINK.'&amp;act=nyhetstats&amp;nyhetid='.$objNyhet->getId().'&amp;mode=7">3 dager</a></li>
                <li><a href="'.MS_NYHET_LINK.'&amp;act=nyhetstats&amp;nyhetid='.$objNyhet->getId().'&amp;mode=8">7 dager</a></li>
                <li><a href="'.MS_NYHET_LINK.'&amp;act=nyhetstats&amp;nyhetid='.$objNyhet->getId().'&amp;mode=9">2 uker</a></li>
                <li><a href="'.MS_NYHET_LINK.'&amp;act=nyhetstats&amp;nyhetid='.$objNyhet->getId().'&amp;mode=10">1 måned</a></li>
            </ul>
            <ul class="nyhetstatspreselect">
                Periode tilbake i tid (fra nå):
                <li><a href="'.MS_NYHET_LINK.'&amp;act=nyhetstats&amp;nyhetid='.$objNyhet->getId().'&amp;mode=11">1 time</a></li>
                <li><a href="'.MS_NYHET_LINK.'&amp;act=nyhetstats&amp;nyhetid='.$objNyhet->getId().'&amp;mode=12">4 timer</a></li>
                <li><a href="'.MS_NYHET_LINK.'&amp;act=nyhetstats&amp;nyhetid='.$objNyhet->getId().'&amp;mode=13">8 timer</a></li>
                <li><a href="'.MS_NYHET_LINK.'&amp;act=nyhetstats&amp;nyhetid='.$objNyhet->getId().'&amp;mode=14">24 timer</a></li>
                <li><a href="'.MS_NYHET_LINK.'&amp;act=nyhetstats&amp;nyhetid='.$objNyhet->getId().'&amp;mode=15">3 dager</a></li>
                <li><a href="'.MS_NYHET_LINK.'&amp;act=nyhetstats&amp;nyhetid='.$objNyhet->getId().'&amp;mode=16">7 dager</a></li>
                <li><a href="'.MS_NYHET_LINK.'&amp;act=nyhetstats&amp;nyhetid='.$objNyhet->getId().'&amp;mode=17">2 uker</a></li>
                <li><a href="'.MS_NYHET_LINK.'&amp;act=nyhetstats&amp;nyhetid='.$objNyhet->getId().'&amp;mode=18">1 måned</a></li>
                
            </ul>
            <ul class="nyhetstatspreselect">
                Custom visninger:
                <li><a href="'.MS_NYHET_LINK.'&amp;act=nyhetstats&amp;nyhetid='.$objNyhet->getId().'&amp;mode=1">Publiseringstidspunkt til nå</a></li>
                <li><a href="'.MS_NYHET_LINK.'&amp;act=nyhetstats&amp;nyhetid='.$objNyhet->getId().'&amp;mode=2">Fra første til siste datapunkt</a></li>
                <li><a href="'.MS_NYHET_LINK.'&amp;act=nyhetstats&amp;nyhetid='.$objNyhet->getId().'&amp;mode=19">Denne uken (man-fre)</a></li>
                <li><a href="'.MS_NYHET_LINK.'&amp;act=nyhetstats&amp;nyhetid='.$objNyhet->getId().'&amp;mode=20">Denne uken (man-søn)</a></li>
                <li><a href="'.MS_NYHET_LINK.'&amp;act=nyhetstats&amp;nyhetid='.$objNyhet->getId().'&amp;mode=21">Sist uke (man-fre)</a></li>
                <li><a href="'.MS_NYHET_LINK.'&amp;act=nyhetstats&amp;nyhetid='.$objNyhet->getId().'&amp;mode=22">Sist uke (man-søn)</a></li>
                <li><a href="'.MS_NYHET_LINK.'&amp;act=nyhetstats&amp;nyhetid='.$objNyhet->getId().'&amp;mode=23">Denne måneden</a></li>
                <li><a href="'.MS_NYHET_LINK.'&amp;act=nyhetstats&amp;nyhetid='.$objNyhet->getId().'&amp;mode=24">Sist måned</a></li>
            </ul>
            <div class="msclearer">&nbsp;</div>
            
            <form action="?doku.php" method="GET">
                <input type="hidden" name="do" value="minside" />
                <input type="hidden" name="act" value="nyhetstats" />
                <input type="hidden" name="nyhetid" value="'.$objNyhet->getId().'" />
                <input type="hidden" name="mode" value="0" />
                <table>
                    <tr>
                        <td>
                           Fra-tidspunkt:
                        </td>
                        <td>
                            <input type="text" class="edit" name="fratid" value="'.$fratid_tekst.'" />
                        </td>
                    </tr>
                    <tr>
                        <td>
                            Til-tidspunkt:
                        </td>
                        <td>
                            <input type="text" class="edit" name="tiltid" value="'.$tiltid_tekst.'" />
                        </td>
                    </tr>
                </table>
                <input type="submit" class="button" value="Generer" />
            </form>
            ';
        $section_periode .= '</div>';
        
        
        // Graph
        $section_graph = '<h2>Diagram</h2><div class="level3">';
        $arReadList = $objNyhet->getReadList();
        $googleurl = MsNyhet::getGoogleGraphUri($arReadList, null, $fratid, $tiltid);
        $section_graph .= "<img src=\"$googleurl\" height=\"450\" width=\"650\" alt=\"Prosent som har lest nyhet\" />";
        $section_graph .= '</div>';
        
        // Datagrunnlag
        $section_tabell = '<h2>Datagrunnlag</h2><div class="level3">';
        $section_tabell .= 'Tab-separert data over lesetidspunkt. Kan kopieres rett inn i Excel.<br>
            Brukere med blankt lesetidspunkt har ikke markert nyhet som lest.<pre>';
        $section_tabell .= "BrukerID\tNavn\tTidspunkt lest\n";
        foreach($arReadList as $readevent) {
            $section_tabell .= $readevent['brukerid'] . "\t";
            $section_tabell .= $readevent['brukerfullnavn'] . "\t";
            $section_tabell .= $readevent['readtime'] . "\n";
        }
        $section_tabell .= '</pre></div>';
        
        // Visning av nyhet
        $section_nyhet = '<h2>Nyhet</h2><div class="level3">';
        $section_nyhet .= self::genFullNyhet($objNyhet, array(), 'nyhetstats');
        $section_nyhet .= '</div>';
        
        
        
        $pre = '<h1>Statistikk for enkeltnyhet</h1><div class="level1">';
        $post = '</div>';
        $output = $pre . $section_graph . $section_periode . $section_tabell . 
            $section_nyhet . $post;
        
        return $output;
    }
    
	public static function genTagAdmin(NyhetTagCollection $colTags) {
        $output .= "<h2>Kategori og tag-administrasjon</h2>\n";
        
        $output .= '<div class="tagadm">';
        
        $output .= '<div class="level3">';
        if ($colTags->length() === 0)  {
            $output .= '<div class="mswarningbar">Ingen tags / kategorier her!</div>';
        } else {
            $output .= '<form action="' . MS_NYHET_LINK . '&amp;act=subtagadm" method="POST">
                <input type="hidden" name="tagact" value="edit" />
                <table class="tagadmtbl">
                    <tr>
                        <th>Type </th>
                        <th>Navn </th>
                        <th>Ikke velgbar </th>
                        <th>Ikke i arkiv </th>
                        <th>Handling </th>
                    </tr>
            ';

            foreach($colTags as $objTag) {
                switch($objTag->getType()) {
                    case NyhetTag::TYPE_KATEGORI:
                        $type = 'Kategori';
                        break;
                    case NyhetTag::TYPE_TAG:
                        $type = 'Tag';
                        break;
                    default:
                        $type = 'Ukjent / feil';
                        break;
                }
                $navn = $objTag->getNavn();
                $noview = ($objTag->noView()) ? 'checked="checked"' : '';
                $noselect = ($objTag->noSelect()) ? 'checked="checked"' : '';
                $id = $objTag->getId();
                $output .= "
                    <input type=\"hidden\" name=\"tagadmdata[$id][tagid]\" value=\"$id\" />
                    <tr>
                        <td>$type</td>
                        <td>$navn</td>
                        <td><input type=\"checkbox\" class=\"edit\" name=\"tagadmdata[$id][noselect]\" $noselect /></td>
                        <td><input type=\"checkbox\" class=\"edit\" name=\"tagadmdata[$id][noview]\" $noview /></td>
                        ".'<td><a href="'. MS_NYHET_LINK .'&amp;act=sletttag&amp;tagid='.$id.'"  onClick="return heltSikker()"><img src="'.MS_IMG_PATH.'trash.png" alt="slett" Title="Slett tag permanent"></a></td>'."
                    </tr>
                ";
            }
            
            $output .= '</table>';
            $output .= '<input type="submit" value="Lagre" class="button" /></form>';
        }
        $output .= '</div>'; // level3
        // Ny tag  
        $output .= '<div class="tagadmnytag">
                        <h2>Ny kategori / tag</h2>
                        <div class="level3">
                            <form action="' . MS_NYHET_LINK . '&amp;act=subtagadm" method="POST">
                            <input type="hidden" name="tagact" value="new" />
                            <table class="tagadmnytagtbl">
                                <tr>
                                    <th>Type</th>
                                    <th>Navn</th>
                                    <th>Handling</th>
                                </tr>
                                <tr>
                                    <td>
                                        <select name="nytagtype">
                                            <option value="'.NyhetTag::TYPE_TAG.'">Tag</option>
                                            <option value="'.NyhetTag::TYPE_KATEGORI.'">Kategori</option>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="text" class="edit" name="nytagnavn" />
                                    </td>
                                    <td>
                                        <input type="submit" value="Lagre" class="button" />
                                    </td>
                                </tr>
                            </table>
                            </form>
                        </div>
                    </div>
            ';
        
        $output .= '</div>'; // tagadm
        
        return $output;
    }
    
    public static function genImportAdmin($colSkrivbareOmrader) {
        $output = '<h2>Import-verktøy</h2>
            <div class="nyhet_import">
            <div class="level3">
            <p>
                For å importere nyheter fra gammel løsning med hidden-plugin, kopieres "kildekoden" til en eller flere nyheter med hidden-syntax
                til filen '.DOKU_INC.'lib/plugins/minside/cache/nyhet_import.txt. Denne filen må være lesbar av serveren.
            </p>
            <p>
                Eventuelle seksjoner som oppfattes som nyheter, men mangler info, ikke kan parses, eller er signert av en bruker som ikke finnes i databasen
                vil skrives til en fil i '.DOKU_INC.'lib/plugins/minside/cache/. Serveren må kunne skrive til, og evt. opprette denne filen.
            </p>
            ';
        
        if ($colSkrivbareOmrader->length() === 0)  {
            $output .= '<div class="mswarningbar">Kan ikke importere nyheter: du har ikke skrivetilgang til noe område.</div>';
        } elseif(!is_writable(DOKU_INC.'lib/plugins/minside/cache')) {
            $output .= '<div class="mswarningbar">Kan ikke importere nyheter: server kan ikke skrive til cache-mappen.</div>';
        } elseif(!is_readable(DOKU_INC.'lib/plugins/minside/cache/nyhet_import.txt')) {
            $output .= '<div class="mswarningbar">Kan ikke importere nyheter: filen '.DOKU_INC.'lib/plugins/minside/cache/nyhet_import.txt eksisterer ikke eller er ikke lesbar av serveren.</div>';
        } else {
            $output .= '<form action="' . MS_NYHET_LINK . '&amp;act=doimport" method="POST">
                Velg område nyheter skal opprettes i:<br />
                <select name="importomrade" class="edit">';
            foreach($colSkrivbareOmrader as $objOmrade) {
                $output .= '<option value="' . $objOmrade->getOmrade() . '">' . $objOmrade->getVisningsnavn() . '</option>';
            }
            $output .= '</select>';
            $output .= '<br /><br /><input type="submit" value="Start importering" onClick="return heltSikker()" class="button" /></form>';
        }
        $output .= '</div>'; // level3
        $output .= '</div>'; // nyhet_import
        
        return $output;        
    }
    
    public static function genArkivOptions(array $data, $arkivlinkparams='') {
        
        // Linker
        $selflink = MS_NYHET_LINK . '&amp;act=arkiv' . $arkivlinkparams;
        
        // Datofilter
        $min_fradato = '2005-01-01';
        $max_tildato_stamp = (60 * 60 * 24 * 365 * 5) + time();
        $max_tildato = date('Y-m-d', $max_tildato_stamp);

        // Fradato
        $infdato = $data['fdato'];
        $objCalendarFra = new tc_calendar("fdato", true);
        if ($infdato) {
            $objCalendarFra->setDate(date('d', $infdato), date('m', $infdato), date('Y', $infdato));
        }
        $objCalendarFra->setPath('lib/plugins/minside/minside/');
        $objCalendarFra->startMonday(true);
        $objCalendarFra->setIcon(MS_IMG_PATH . 'iconCalendar.gif');
        $objCalendarFra->dateAllow($min_fradato, $max_tildato, false);
        ob_start(); // må ta vare på output...
        $objCalendarFra->writeScript();
        $html_datofra = ob_get_clean();
        
        // Tildato
        $intdato = $data['tdato'];
        $objCalendarTil = new tc_calendar("tdato", true);
        if ($intdato) {
            $objCalendarTil->setDate(date('d', $intdato), date('m', $intdato), date('Y', $intdato));
        }
        $objCalendarTil->setPath('lib/plugins/minside/minside/');
        $objCalendarTil->startMonday(true);
        $objCalendarTil->setIcon(MS_IMG_PATH . 'iconCalendar.gif');
        $objCalendarTil->dateAllow($min_fradato, $max_tildato, false);
        ob_start(); // må ta vare på output...
        $objCalendarTil->writeScript();
        $html_datotil = ob_get_clean();
        
        // Warn @ til < fradato
        if($infdato && $intdato && ($infdato > $intdato)) {
            msg('Fradato er etter tildato, dette vil utelukke alle resultater!', -1);
        }
        
        $html_datofilter = 
            '<table>
                <tr>
                    <td>
                         Fra:
                    </td>
                    <td>'
                        .$html_datofra.'
                    </td>
                </tr>
                <tr>
                    <td>
                        Til:
                    </td>
                    <td>'
                        .$html_datotil.'
                    </td>
                </tr>
             </table>';
        
        // Overskriftfilter
        $html_overskriftfilter =
            '<input class="edit" id="overskriftedit" type="text" name="oskrift" value="'.((hsc($data['oskrift']))?:'').'" /><br /><br />';
        // Tagfilter
        
        // Kategorifilter
        $colKat = NyhetTagFactory::getAlleNyhetTags(true, false, false, NyhetTag::TYPE_KATEGORI);
        foreach($colKat as $objKat) {
            $selected = (in_array($objKat->getId(), (array) $data['fkat'])) ? ' selected' : '';
            $katopts .= '<option value="' . $objKat->getId() . "\"$selected>" . $objKat->getNavn() . '</option>';
        }
        $html_kategorifilter = 
            '<select class="edit tagselect" name="fkat[]" size="7" multiple="multiple">'. $katopts .'</select><br />
            <span class="filterinfo">Hold inne ctrl for å velge flere.</span>';
            
        // Tagfilter
        $colTag = NyhetTagFactory::getAlleNyhetTags(true, false, false, NyhetTag::TYPE_TAG);
        foreach($colTag as $objTag) {
            $selected = (in_array($objTag->getId(), (array) $data['ftag']['data'])) ? ' selected' : '';
            $tagopts .= '<option value="' . $objTag->getId() . "\"$selected>" . $objTag->getNavn() . '</option>';
        }
        if ($data['ftag']['mode'] == 'AND') {
            $tfANDchecked = ' checked="checked"';
            $tfORchecked = '';
        } else {
            $tfANDchecked = '';
            $tfORchecked = ' checked="checked"';
        }
        $html_tagfilter = 
            '<select class="edit tagselect" name="ftag[]" size="7" multiple="multiple">'. $tagopts .'</select><br />
            <span class="filterinfo">Hold inne ctrl for å velge flere.</span><br />
            <input type="radio" id="tfOR" name="tagfilter" value="OR"'.$tfORchecked.' />
            <label for="tfOR">Minst en av valgte tags</label><br />
            <input type="radio" id="tfAND" name="tagfilter" value="AND"'.$tfANDchecked.' />
            <label for="tfAND">Alle valgte tags</label>';
        
        // Sortering
        if ($data['sortorder'] == 'ASC') {
            $sortASCyes = ' checked="checked"';
            $sortASCno = '';
        } else {
            $sortASCyes = '';
            $sortASCno = ' checked="checked"';
        }
        $html_sortering = '
            <input type="radio" id="sortASC" name="sortorder" value="ASC"'.$sortASCyes.' />
            <label for="sortASC">Eldste først</label><br />
            <input type="radio" id="sortDESC" name="sortorder" value="DESC"'.$sortASCno.' />
            <label for="sortDESC">Nyeste først</label>';
        
        // Publisert av
        $arPublishers = MsNyhet::getBrukereSomHarPublisertNyheter();
        foreach($arPublishers as $publisher) {
            $selected = (in_array($publisher['id'], (array) $data['fpublishers'])) ? ' selected' : '';
            $pubopts .= '<option value="' . $publisher['id'] . "\"$selected>" . $publisher['navn'] . '</option>';
        }
        $html_publisherfilter = 
            '<select class="edit tagselect" name="fpublishers[]" size="7" multiple="multiple">'. $pubopts .'</select><br />
            <span class="filterinfo">Hold inne ctrl for å velge flere.</span>';
            
        // Handlinger
        $html_antall = 
            'Nyheter vist per side: 
            <select class="edit" name="perside">
                <option value="5"'.(($data['pages']['perside']==5)?' selected':'').'>5</option>
                <option value="10"'.(($data['pages']['perside']==10)?' selected':'').'>10</option>
                <option value="20"'.(($data['pages']['perside']==20)?' selected':'').'>20</option>
                <option value="30"'.(($data['pages']['perside']==30)?' selected':'').'>30</option>
                <option value="50"'.(($data['pages']['perside']==50)?' selected':'').'>50</option>
                <option value="100"'.(($data['pages']['perside']==100)?' selected':'').'>100</option>
            </select>';

            $html_search = 
            '<input class="edit" type="submit" name="dofilter" value="Utfør søk" />
            <input class="edit" type="submit" name="dofilter" value="Nullstill" />';
             
        // Områdefilter
        $colOmrader = NyhetOmrade::getOmrader('msnyheter', MSAUTH_1);
        if ($colOmrader->length() > 1) {
            foreach($colOmrader as $objOmrade) {
                $selected = (in_array($objOmrade->getOmrade(), (array) $data['fomrader'])) ? ' selected' : '';
                $omradeopts .= '<option value="' . $objOmrade->getOmrade() . "\"$selected>" . $objOmrade->getVisningsnavn() . '</option>';
            }
            $html_omradefilter = 
                '<select class="edit tagselect" name="fomrader[]" size="7" multiple="multiple">'. $omradeopts .'</select><br />
                <span class="filterinfo">Hold inne ctrl for å velge flere.</span>';
        } else {
            $html_omradefilter =
                'Du har ikke tilgang til mer enn ett område.<br />
                Dette filteret er derfor ikke tilgjengelig.';
        }
        
        // Pagination
        $numhits = $data['pages']['count'];
        $currpage = $data['pages']['currpage'];
        $numpages = $data['pages']['numpages'];
        $html_pagination = self::getPagination($numpages, $currpage, $selflink.'&amp;visside=');
        
        // Selflink
        $html_selflink = '<a href="'.$selflink.'&amp;visside='.$currpage.'">Link til dette søket</a>';
        
        // Display avansert
        $html_display_adv = ($data['advanced']) ? 'block' : 'none';
        
        // "Template"
        $output = '
        <div class="arkivoptions_frame">
            <br />
            <div class="arkivoptions">
                <form method="POST" action="'.MS_NYHET_LINK.'&amp;act=arkiv">
                <div class="arkivbar">
                    <div class="topgroup">
                        <div class="gruppeheader">
                            Søk på nyhets-overskrift
                        </div>
                        <div class="gruppecontent">
                            '.$html_overskriftfilter.'
                        </div>
                    </div>
                    <div class="leftgroup">
                        <div class="gruppeheader">
                            Tag-filter
                        </div>
                        <div class="gruppecontent"  id="tagfilter">
                            '.$html_tagfilter.'
                        </div>
                    </div>
                    <div class="rightgroup">
                        <div class="gruppeheader">
                            Kategori-filter
                        </div>
                        <div class="gruppecontent" id="kategorifilter">
                            '.$html_kategorifilter.'
                        </div>
                    </div>
                </div>
                <div class="msclearer">&nbsp;</div>
                <div class="avansertgroup" style="display:'.$html_display_adv.';" id="avansert__group">
                    <div class="arkivbar">
                        <div class="leftgroup">
                            <div class="gruppeheader">
                                Dato-filter
                            </div>
                            <div class="gruppecontent">
                                '.$html_datofilter.'
                            </div>
                        </div>
                        <div class="rightgroup">
                            <div class="gruppeheader">
                                Sortering
                            </div>
                            <div class="gruppecontent">
                                '.$html_sortering.'
                            </div>
                        </div>
                    </div>
                    <div class="msclearer">&nbsp;</div>
                    <div class="arkivbar">
                        <div class="leftgroup">
                            <div class="gruppeheader">
                                Publisert av
                            </div>
                            <div class="gruppecontent" id="publisherfilter">
                                '.$html_publisherfilter.'
                            </div>
                        </div>
                        <div class="rightgroup">
                            <div class="gruppeheader">
                                Område-filter
                            </div>
                            <div class="gruppecontent" id="omradefilter">
                                '.$html_omradefilter.'
                            </div>
                        </div>
                    </div>
                    <div class="msclearer">&nbsp;</div>
                </div>
                <div class="hideshowadv"><a href="javascript:void(0);" id="filter__link" value="test">Trykk her for å vise/skjule avanserte valg</a></div>
                <div class="msclearer">&nbsp;</div>
            </div>
            <div class="arkivunder"> 
                <div class="antallsearch">
                    '.$html_antall.'
                </div>
                <div class="arkivsearch">
                    '.$html_search.'
                </div>
                <div class="msclearer">&nbsp;</div>
            </div>
            <div class="msclearer"></div>
                </form>
                
            <div class="pagination">
                Antall treff: <strong>'.$numhits.'</strong> &mdash; ('.$html_selflink.')<br />
                '.$html_pagination.'
            </div>
            <br />
        </div>
        ';

        return array($output, $html_pagination);
    }
	
	protected static function getMailLink($name, $epost) {
		$format = '<a title="%2$s" class="mail JSnocheck" href="mailto:%2$s">%1$s</a>';
		return sprintf($format, $name, $epost);
	}
	
	protected static function dispTime($sqltime) {
		$timestamp = strtotime($sqltime);
		return date(self::TIME_FORMAT, $timestamp);
	}
    
    protected static function genTagList(NyhetTagCollection $colTags) {
        if ($colTags->length() === 0) return '<div class="tags">&nbsp;</div>';
        $output = "\n".'<div class="tags"><span>';
        foreach($colTags as $objTag) {
            $arOutput[] .= '    <a href="'.MS_NYHET_LINK.'&amp;act=arkiv&amp;ftag[]='. $objTag->getId() .'" class="wikilink1" ' .
                'title="tag:' . $objTag->getNavn() . '">' . $objTag->getNavn() . '</a>';
        }
        $output .= implode(', ', $arOutput);
        $output .= '</span></div>';
        
        return $output;
    }
    
    public static function genArkivLinkParams(array $data) {
        $param = array();
        if(array_key_exists('fdato', $data)) {
            $param[] = 'fdato=' . date('Y-m-d', $data['fdato']);
        }
        if(array_key_exists('tdato', $data)) {
            $param[] = 'tdato=' . date('Y-m-d', $data['tdato']);
        }
        if(array_key_exists('oskrift', $data)) {
            $param[] = 'oskrift=' . urlencode($data['oskrift']);
        }
        if(array_key_exists('fkat', $data)) {
            foreach($data['fkat'] as $fkat) {
                $param[] = 'fkat[]=' . $fkat;
            }
        }
        if(array_key_exists('ftag', $data)) {
            if(array_key_exists('data', $data['ftag'])) {
                foreach($data['ftag']['data'] as $ftag) {
                    $param[] = 'ftag[]=' . $ftag;
                }
            }
            if(array_key_exists('mode', $data['ftag'])) {
                $param[] = 'tagfilter=' . $data['ftag']['mode'];
            }
        }
        if(array_key_exists('sortorder', $data)) {
            $param[] = 'sortorder=' . $data['sortorder'];
        }
        if(array_key_exists('fpublishers', $data)) {
            foreach($data['fpublishers'] as $publisher) {
                $param[] = 'fpublishers[]=' . $publisher;
            }
        }
        if(array_key_exists('fomrader', $data)) {
            foreach($data['fomrader'] as $omrade) {
                $param[] = 'fomrader[]=' . $omrade;
            }
        }
        if(array_key_exists('perside', $data['pages'])) {
            $param[] = 'perside=' . $data['pages']['perside'];
        }
        
        return '&amp;' . implode('&amp;', $param);
        
    }
    
    protected static function tableSortColTags(NyhetTagCollection $inputcol, $items_per_row) {
        // Tar i mot en collection tags, returnerer en tabell (array med en NyhetTagCollection per rad)
        // med gitt bredde hvor korte navn er fordelt mot venstre side av tabellen, 
        // slik at hver kolonne har mest mulig uniform bredde.
        
        // Calculationz and validationz
        $items_per_row = (int) $items_per_row;
        $antall_items = $inputcol->length();
        if($items_per_row < 1 || $antall_items < 1) return array();
        $antall_full_rows = floor($antall_items / $items_per_row);
        if($antall_full_rows === 0) return array($inputcol);
        $antall_i_siste_row = $antall_items % $items_per_row;
        if($antall_i_siste_row > 0) {
            $siste_item_i_siste_row = ($antall_i_siste_row * ($antall_full_rows + 1)) - 1; // - 1 pga 0indeksering i $arIndexed
        } else {
            $siste_item_i_siste_row = $antall_items - 1; // - 1 pga 0indeksering i $arIndexed
            $antall_full_rows--;
        }
        
        // Opprett collections - en mer enn antall_full_rows
        // Hver collection holder en rad med tags
        $arCollections = array();
        for($i=0;$i<=$antall_full_rows;$i++) {
            $arCollections[$i] = new NyhetTagCollection();
        }
        
        // Sorter etter streng-lengde på tagnavn
        if (!$inputcol->uasort(array('NyhetTag', 'compare_strlen_navn'))) throw new Exception('Sortering av tags feilet.');
        
        // Opprett array hvor tags er indeksert i henhold til sortering
        $arIndexed = array();
        foreach($inputcol as $objTag) {
            $arIndexed[] =  $objTag;
        }

        // Tildel tags til collections (rader i tabell)
        $j = 0; // Hvilken rad vi skriver til for øyeblikket
        foreach($arIndexed as $key => $objTag) {
            $arCollections[$j]->addItem($objTag, $objTag->getId());
            $maxrow = ($key > $siste_item_i_siste_row) ? $antall_full_rows - 1 : $antall_full_rows; // - 1 pga 0indeksering i $arCollections
            if($j == $maxrow) {
                $j = 0;
            } else {
                $j++;
            }
        }

        return $arCollections;
        
    }
    
    static function getPagination($numpages, $currpage, $selflink) {
        if($numpages < 1) return '';
        if($currpage < 1) $currpage = 1;
        if($currpage > $numpages) $currpage = $numpages;
        
        $forrige = ($currpage > 1) 
            ? '<a href="'.$selflink . ($currpage - 1).'">&lt;forrige</a> '
            : '';
        $neste = ($currpage < $numpages) 
            ? '<a href="'.$selflink . ($currpage + 1).'">neste&gt;</a>'
            : '';
            
        $start = ($currpage > 10) ? $currpage - 10 : 1;
        $end = ($currpage + 10 < $numpages) ? $currpage + 10 : $numpages;
        for($i=$start;$i <= $end;$i++) {
            $pagelinks .= ($currpage == $i)
                ? '<strong>' . $i . '</strong>&nbsp;'
                : '<a href="' . $selflink . $i . '">' . $i . '</a> ';
        }
        if($start > 1) $startinfo = '<a href="' . $selflink . '1">1</a>' . ' &#8230; ';
        if($end < $numpages) $sluttinfo = ' &#8230; ' . '<a href="' . $selflink . $numpages . '">' . $numpages . '</a> ';
        $output = 'Side: ' . $forrige . $startinfo . $pagelinks . $sluttinfo . $neste;
        
        return $output;
    }
	
}
