<?php
if(!defined('MS_INC')) die();

class NyhetGen {

	const THUMB_BREDDE = 100;
	const TIME_FORMAT = 'd.m.Y \k\l. H.i';

	private function __construct() { }
	
	public static function genFullNyhetViewOnly(msnyhet &$nyhet) {
		return self::_genFullNyhet($nyhet);
	}
	
	public static function genFullNyhet(msnyhet &$nyhet, array $extraoptions = array(), $returnto = NULL) {
        $acl = $nyhet->getAcl();
        $arOptions = array();
        
        switch($acl) {
            case MSAUTH_ADMIN:
            case MSAUTH_5:
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
		return self::_genFullNyhet($nyhet, $arOptions, $returnto);
	}
	
	public static function genFullNyhetDeleted(msnyhet &$nyhet, $returnto = NULL) {
		$arOptions = array(
			'restore',
			'permslett'
		);
		
		return self::_genFullNyhet($nyhet, $arOptions, $returnto);
	}
	
	private static function _genFullNyhet(msnyhet &$nyhet, array $inoptions = array(), $returnto = NULL) {
		// Data
        $type = $nyhet->getType();
		$id = $nyhet->getId();
        $title = $nyhet->getTitle(true);
		$body = $nyhet->getHtmlBody();
        $omrade = $nyhet->getOmrade();
        $omradeinfo = NyhetOmrade::getVisningsinfoForNyhet($nyhet, 'msnyheter');
        $pubdiff = time() - strtotime($nyhet->getPublishTime());
        $pubdager = floor($pubdiff / 60 / 60 / 24);
        $pubtimer = floor(($pubdiff - $pubdager * 60 * 60 * 24) / 60 / 60);
        if ($nyhet->hasImage()) {
			$img = $nyhet->getImageTag(self::THUMB_BREDDE);
		} else {
			$img = '';
		}
        
        // HTML
        $returnto_html = ($returnto) ? '&returnto='.$returnto : '';
        $omrade_html = '<div class="nyhetomrade">Område: ' . $omradeinfo['visningsnavn'] . '</div>';
        $omrade_farge = ($omradeinfo['farge']) ? ' style="background-color: #' . $omradeinfo['farge'] . ';"' : '';
        $create = ($nyhet->isSaved())
			? '<div class="nyhetcreate">Opprettet '. self::dispTime($nyhet->getCreateTime()) .
				' av ' . self::getMailLink($nyhet->getCreateByNavn(), $nyhet->getCreateByEpost()) . '</div>'
			: '';
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
            if ($pubdager == 0) {
                $tid_siden = $pubtimer . (($pubtimer === 1) ? ' time' : ' timer');
            } else {
                $tid_siden = $pubdager . (($pubdager === 1) ? ' dag' : ' dager');
            }
            $publish = '<div class="nyhetpub">Publisert '. self::dispTime($nyhet->getPublishTime()) .
				' (' . $tid_siden . ' siden) av ' . self::getMailLink($nyhet->getCreateByNavn(), $nyhet->getCreateByEpost()) . '</div>';
        } else {
            $publish = '<div class="nyhetpub">Publiseres '. self::dispTime($nyhet->getPublishTime()) . '</div>';
        }
		
        // Options/icon
		$opt['link'] = '<a href="' . wl($nyhet->getWikiPath()) . '">' .
            '<img alt="link" title="Direktelenke til nyhet" width="16" ' .
            'height="16" src="' . MS_IMG_PATH . 'link.png" /></a>';
		$opt['lest'] = '<a href="' . MS_NYHET_LINK . "&act=lest&nyhetid=$id\">" .
            '<img alt="lest" title="Merk nyhet som lest" width="16" ' .
            'height="16" src="' . MS_IMG_PATH . 'success.png" /></a>';
		$opt['edit'] = '<a href="' . MS_NYHET_LINK . "&act=edit&nyhetid=$id\">" .
            '<img alt="rediger" title="Rediger nyhet" width="16" ' .
            'height="16" src="' . MS_IMG_PATH . 'pencil.png" /></a>';
		$opt['slett'] = '<a href="' . MS_NYHET_LINK . $returnto_html . "&act=slett&nyhetid=$id\">" .
            '<img alt="slett" title="Slett nyhet" width="16" ' .
            'height="16" src="' . MS_IMG_PATH . 'trash.png" /></a>';
		$opt['permslett'] = '<a href="' . MS_NYHET_LINK . "&act=permslett&nyhetid=$id\">" .
            '<img alt="permslett" title="Slett nyhet permanent" width="16" ' .
            'height="16" src="' . MS_IMG_PATH . 'trash.png" /></a>';
		$opt['restore'] = '<a href="' . MS_NYHET_LINK . "&act=restore&nyhetid=$id\">" .
            '<img alt="gjenopprett" title="Gjenopprett nyhet" width="16" ' .
            'height="16" src="' . MS_IMG_PATH . 'success.png" /></a>';
		
		foreach ($inoptions as $k => $v) {
			$options[] = $opt[$v];
		}
		if (!empty($options)) {
			$valg = implode('&nbsp;', $options);
		} else {
			$valg = '';
		}
        
        // Wannabetemplate :D
		$output = "
			<div class=\"nyhetcontainer\">
			<div class=\"nyhet $omrade\">
				<!-- NyhetsID: $id -->
				<div class=\"nyhettopbar\"$omrade_farge>
					<div class=\"nyhettitle\">$sticky$title</div>
					<div class=\"nyhetoptions\">$valg</div>
					<div class=\"nyhetinfo\">$omrade_html$publish$lastmod$delete</div>
                    <div class=\"msclearer\"></div>
				</div>
				<div class=\"nyhetcontent\">
					<div class=\"nyhetimgleft\">$img</div>
					<div class=\"nyhetbody\">$body</div>
					<div class=\"msclearer\"></div>
				</div>
			</div>
			</div>
		";
		
		return $output;
		
	}

    public static function genEdit(msnyhet &$objNyhet, $preview=false) {
		// Område
		if ($objNyhet->isSaved()) {
			$html_omrade = '<div class="nyhetomradevelger">Område:
				<input class="edit" style="width:58px;" type="text" name="nyhetomrade" value="' . $objNyhet->getOmrade() . '" disabled /></div>';
		} else {
			$colOmrader = NyhetOmrade::getOmrader('msnyheter', AUTH_CREATE);
			$html_omrade = '<div class="nyhetomradevelger">Område: <select name="nyhetomrade" tabindex="4" class="edit">';
			if ($colOmrader->length() === 0) {
				$html_omrade .= 'Du har ikke tilgang til noen områder!';
			} else {
				foreach ($colOmrader as $objOmrade) {
					$html_omrade .= '<option value="' . $objOmrade->getOmrade() . '"' .
                    (($objOmrade->isDefault()) ? ' selected="selected"' : '') .
                    '">'. $objOmrade->getOmrade() . '</value>';
				}
			}
			$html_omrade .= '</select></div>';
			
		}
		
		// Sticky
		$checked = ($objNyhet->isSticky()) ? ' checked="checked"' : '';
		$html_sticky = '<div class="nyhetvelgsticky">Skal nyheten være <acronym title="Sticky nyheter vises øverst, ' .
			'og blir liggende til de merkes som &quot;ikke sticky&quot;.">sticky</acronym>?' .
			' <input class="edit" value="sticky" type="checkbox" name="nyhetsticky" '.$checked.' /></div>';
		
		// Bilde
		$html_bilde = '<div class="nyhetbildevelger"><div class="nyhetsettext">Bilde:</div> <input class="edit" type="text" tabindex="3" name="nyhetbilde" id="nyhet__imgpath"' .
			'value="' . $objNyhet->getImagePath() . '" /> ' .
			'<img onClick="openNyhetImgForm('.$objNyhet->getId().')" class="ms_imgselect_nyhet" alt="img" ' .
			'title="Legg til bilde" width="16" ' .
            'height="16" src="' . MS_IMG_PATH . 'image.png" /><img alt="Slett felt" title="Slett felt" class="" src="' .
				MS_IMG_PATH . 'trash.png" onClick="cleartxt(\'nyhet__imgpath\');" /></div>';
		
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
            
            $html_calendar .= '&nbsp;&nbsp;kl. <input type="text" size="1" maxlength="2" value="'. $hour .'" name="nyhetpubdato_hour" id="nyhetpubdato_hour" class="tchour msedit">';
            $html_calendar .= ':<input type="text" size="1" maxlength="2" value="'. $minute .'" name="nyhetpubdato_minute" id="nyhetpubdato_minute" class="tcminute msedit"></div>';
        } else {
            // Bruker har ikke create rights på området
            $html_calendar = '';
        }
        
        // Wikitekst
        $rawwiki = $objNyhet->getWikiTekst();
        if (empty($rawwiki)) {
            $rawwiki = formText(rawWiki($objNyhet->getWikiPath()));
        }
        
        
        // "Template"
        $output .= '
                    <div class="editnyhet">
                        <p class="editnyhetoverskrift"><strong>Rediger nyhet</strong></p>
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
                        <form action="' . MS_NYHET_LINK . '&act=subedit" method="POST">
                            <input type="hidden" name="nyhetid" value="' . $objNyhet->getId() . '" />
                            <input type="hidden" name="id" value="'.$objNyhet->getWikiPath().'" />
                            <input type="hidden" name="rev" value="" />
                            <textarea name="wikitext" id="wiki__text" class="edit" cols="80" rows="10" tabindex="1" style="width:99%">'
                               . $rawwiki .
                            '</textarea>
                            <div class="nyhetattrib">
                                <div class="msnyhetoverskrift">
                                    <div class="nyhetsettext">Overskrift:</div> <input class="edit" style="width:30em;" type="text" tabindex="2" name="nyhettitle" value="' . $objNyhet->getTitle() . '" />
                                </div>'
                                .$html_omrade. '
                                <div class="msclearer"></div>'
                                .$html_bilde
                                .$html_sticky. '
                                <div class="msclearer"></div>'
                                .(($html_calendar) ?: ''). '
                                <div class="msclearer"></div>
                            </div>
                            <div id="wiki__editbar" >
                                <div id="size__ctl" ></div>
                                <div class="editButtons" >
                                    <input name="editsave" type="submit" value="Lagre" class="button" id="edbtn__save" accesskey="s" tabindex="4" title="Lagre [S]" />
                                    <input name="editpreview" type="submit" value="Forhåndsvis" class="button" id="edbtn__preview" accesskey="p" tabindex="5" title="Forhåndsvis [P]" />
                                    <input name="editabort" type="submit" value="Avbryt" class="button" tabindex="6" />
                                </div>
                            </div>
                        </form>
                    </div>'; // editnyhet
        
        if ($preview) $output .= self::genFullNyhetViewOnly($objNyhet);
		
        return $output;
    }
	
	public static function genIngenNyheter($ekstratekst='') {
        if ($ekstratekst) $ekstratekst = '<p>' . $ekstratekst . '</p>';
		return '<div class="mswarningbar">Ingen nyheter her!'.$ekstratekst.'</div>';
	}
    
    public static function genOmradeAdmin($colOmrader) {
        $output .= "<h2>Områdeadministrasjon</h2>\n";
        
        $output .= '<div class="omradeadmwrap">
            <form action="' . MS_NYHET_LINK . '&act=subomradeadm" method="POST">
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
        $output .= '</table></div><input type="submit" value="Lagre" class="button" /></form>';
        
        return $output;
    }
	
	protected static function getMailLink($name, $epost) {
		$format = '<a title="%2$s" class="mail JSnocheck" href="mailto:%2$s">%1$s</a>';
		return sprintf($format, $name, $epost);
	}
	
	protected static function dispTime($sqltime) {
		$timestamp = strtotime($sqltime);
		return date(self::TIME_FORMAT, $timestamp);
	}
	
}
