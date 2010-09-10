<?php
if(!defined('MS_INC')) die();

class NyhetGen {

	const THUMB_BREDDE = 100;
	const TIME_FORMAT = 'd.m.Y \k\l. H.i';

	private function __construct() { }
	
	public static function genFullNyhetViewOnly(msnyhet &$nyhet) {
		return self::_genFullNyhet($nyhet);
	}
	
	public static function genFullNyhet(msnyhet &$nyhet, array $extraoptions = array()) {
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
		return self::_genFullNyhet($nyhet, $arOptions);
	}
	
	public static function genFullNyhetDeleted(msnyhet &$nyhet) {
		$arOptions = array(
			'restore',
			'permslett'
		);
		
		return self::_genFullNyhet($nyhet, $arOptions);
	}
	
	private static function _genFullNyhet(msnyhet &$nyhet, array $inoptions = array()) {
		// Data
        $type = $nyhet->getType();		
		$id = $nyhet->getId();
        $title = $nyhet->getTitle();
		$body = $nyhet->getHtmlBody();
        $pubdiff = time() - strtotime($nyhet->getPublishTime());
        $pubdager = floor($pubdiff / 60 / 60 / 24);
        $pubtimer = floor(($pubdiff - $pubdager * 60 * 60 * 24) / 60 / 60);
        if ($nyhet->hasImage()) {
			$img = $nyhet->getImageTag(self::THUMB_BREDDE);
		} else {
			$img = '';
		}
        
        // HTML
        $omrade = '<div class="nyhetomrade">Område: ' . $nyhet->getOmrade() . '</div>';
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
		$publish = (strtotime($nyhet->getPublishTime()) < time()) 
			? '<div class="nyhetpub">Nyhet publisert '. self::dispTime($nyhet->getPublishTime()) .
				' (' . $pubdager . ' dager, ' . $pubtimer . ' timer siden)</div>'
			: '<div class="nyhetpub">Nyhet publiseres '. self::dispTime($nyhet->getPublishTime()) . '</div>';
        $sticky = ($nyhet->isSticky()) 
			? '<img alt="sticky" title="Sticky nyhet" width="19" height="24" src="' .
				MS_IMG_PATH . 'pin_icon.png" />' 
			: '' ;
		
        // Options/icon
		$opt['link'] = '<a href="' . wl($nyhet->getWikiPath()) . '">' .
            '<img alt="link" title="Direktelenke til nyhet" width="16" ' .
            'height="16" src="' . MS_IMG_PATH . 'link.png" />';
		$opt['lest'] = '<a href="' . MS_NYHET_LINK . "&act=lest&nyhetid=$id\">" .
            '<img alt="lest" title="Merk nyhet som lest" width="16" ' .
            'height="16" src="' . MS_IMG_PATH . 'success.png" />';
		$opt['edit'] = '<a href="' . MS_NYHET_LINK . "&act=edit&nyhetid=$id\">" .
            '<img alt="rediger" title="Rediger nyhet" width="16" ' .
            'height="16" src="' . MS_IMG_PATH . 'pencil.png" /></a>';
		$opt['slett'] = '<a href="' . MS_NYHET_LINK . "&act=slett&nyhetid=$id\">" .
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
			<div class=\"nyhet\">
				<!-- NyhetsID: $id -->
				<div class=\"nyhettopbar\">
					<div class=\"nyhettitle\">$sticky$title</div>
					<div class=\"nyhetoptions\">$valg</div>
					<div class=\"nyhetinfo\">$omrade$create$publish$lastmod$delete</div>
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

    public static function genEdit(msnyhet &$objNyhet) {
		
		// Område
		if ($objNyhet->isSaved()) {
			$html_omrade = 'Område:
				<input class="edit" type="text" name="nyhetomrade" value="' . $objNyhet->getOmrade() . '" disabled />';
		} else {
			$colOmrader = NyhetOmrade::getOmrader('msnyheter', AUTH_CREATE);
			$html_omrade = 'Område: <select name="nyhetomrade">';
			if ($colOmrader->length() === 0) {
				$html_omrade .= 'Du har ikke tilgang til noen områder!';
			} else {
				foreach ($colOmrader as $objOmrade) {
					$html_omrade .= '<option value="' . $objOmrade->getOmrade() . '">'. 
						$objOmrade->getOmrade() . '</value>';
				}
			}
			$html_omrade .= '</select>';
			
		}
		
		// Sticky
		$checked = ($objNyhet->isSticky()) ? ' checked="checked"' : '';
		$html_sticky = 'Skal nyheten være <acronym title="Sticky nyheter vises øverst, ' .
			'og blir liggende til de merkes som &quot;ikke sticky&quot;.">sticky</acronym>?' .
			' <input class="edit" value="sticky" type="checkbox" name="nyhetsticky"'.$checked.' />';
		
		// Bilde
		$html_bilde = 'Bilde: <input class="edit" type="text" name="nyhetbilde" id="nyhet__imgpath"' .
			'value="' . $objNyhet->getImagePath() . '" /> ' .
			'<img onClick="openNyhetImgForm('.$objNyhet->getId().')" class="ms_imgselect_nyhet" alt="img" ' .
			'title="Legg til bilde" width="16" ' .
            'height="16" src="' . MS_IMG_PATH . 'image.png" />';
		
		// Publiseringsdato
		
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
		$html_calendar = 'Publiseringsdato: ' . ob_get_clean();
		
		$html_calendar .= '&nbsp;&nbsp;kl. <input type="text" size="1" maxlength="2" value="'. $hour .'" name="nyhetpubdato_hour" id="nyhetpubdato_hour" class="tchour">';
		$html_calendar .= ':<input type="text" size="1" maxlength="2" value="'. $minute .'" name="nyhetpubdato_minute" id="nyhetpubdato_minute" class="tcminute">';
		
        $output .= '<div class="editnyhet">';
        $output .= '<p><strong>Rediger nyhet</strong></p>';
		$output .= '<div class="toolbar">
                <div id="draft__status"></div>
                <div id="tool__bar"><a href="'.DOKU_BASE.'lib/exe/mediamanager.php?ns=msnyheter"
                    target="_blank">Valg av mediafil</a></div>

                <script type="text/javascript" charset="utf-8"><!--//--><![CDATA[//><!--
                    textChanged = false;
                    //--><!]]></script>
             </div>';
        $output .= '<form action="' . MS_NYHET_LINK . '&act=subedit" method="POST">';
        
		$output .= '<input type="hidden" name="nyhetid" value="' . $objNyhet->getId() . '" />';
        
        $rawwiki = formText(rawWiki($objNyhet->getWikiPath()));
        
        $output .= '
			
            <div style="width:99%;">
                         
            
            <input type="hidden" name="id" value="'.$objNyhet->getWikiPath().'" />
            <input type="hidden" name="rev" value="" />
            <textarea name="wikitext" id="wiki__text" class="edit" cols="80" rows="10" tabindex="1" >'
            . $rawwiki .
            '</textarea>';
			$output .= 'Overskrift: ';
		$output .= '<input class="edit" type="text" name="nyhettitle" value="' . $objNyhet->getTitle() . '" /><br />';
		$output .= $html_omrade . "<br />\n";
		$output .= $html_bilde . "<br />\n";
		$output .= $html_sticky . "<br />\n";
		$output .= $html_calendar . "<br />\n";
            $output .= '<div id="wiki__editbar" >
            <div id="size__ctl" >
            </div>
            <div class="editButtons" >
            <input name="editsave" type="submit" value="Lagre" class="button" id="edbtn__save" accesskey="s" tabindex="4" title="Lagre [S]" />
            <input name="editpreview" type="submit" value="Forhåndsvis" class="button" id="edbtn__preview" accesskey="p" tabindex="5" title="Forhåndsvis [P]" />
            <input name="editabort" type="submit" value="Avbryt" class="button" tabindex="6" />
            </div>
            
            </div>
            </div>
           
            
            </form>
        ';
        $output .= '</div>'; // editnyhet
		
        return $output;
    }
	
	public static function genIngenNyheter($ekstratekst='') {
        if ($ekstratekst) $ekstratekst = '<p>' . $ekstratekst . '</p>';
		return '<div class="mswarningbar">Ingen nyheter her!'.$ekstratekst.'</div>';
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
