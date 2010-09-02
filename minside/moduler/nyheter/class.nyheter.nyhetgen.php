<?php
if(!defined('MS_INC')) die();

class NyhetGen {

	const THUMB_BREDDE = 100;

	private function __construct() { }
	
	public static function genFullNyhetViewOnly(msnyhet &$nyhet) {
		return self::genFullNyhet($nyhet);
	}
	
	public static function genFullNyhetEdit(msnyhet &$nyhet) {
		$arOptions = array(

		);
		
		return self::genFullNyhet($nyhet, $arOptions);
	}
	
	private static function genFullNyhet(msnyhet &$nyhet, array $options = array()) {
		$type = $nyhet->getType();		
		$id = $nyhet->getId();
		if ($nyhet->hasImage()) {
			$img = '<div class="nyhetimgleft">' .$nyhet->getImageTag(self::THUMB_BREDDE) .
				'</div>';
		} else {
			$img = '';
		}
		$title = $nyhet->getTitle();
		$body = $nyhet->getHtmlBody();
		
		$opt[] = '<a href="' . MS_NYHET_LINK . "&act=lest&nyhetid=$id\">" .
            '<img alt="lest" title="Merk nyhet som lest" width="16" ' .
            'height="16" src="' . MS_IMG_PATH . 'success.png" />';
		$opt[] = '<a href="' . MS_NYHET_LINK . "&act=edit&nyhetid=$id\">" .
            '<img alt="rediger" title="Rediger nyhet" width="16" ' .
            'height="16" src="' . MS_IMG_PATH . 'pencil.png" /></a>';
		$opt[] = '<img onClick="openNyhetImgForm('.$id.')" class="ms_imgselect_nyhet" alt="img" title="Legg til bilde" width="16" ' .
            'height="16" src="' . MS_IMG_PATH . 'image.png" />';
		$opt[] = '<img alt="slett" title="Slett nyhet" width="16" ' .
            'height="16" src="' . MS_IMG_PATH . 'trash.png" />';
		$options = implode('&nbsp;', $opt);
        
		$output = "
			<div class=\"nyhetcontainer\">
			<div class=\"nyhet\">
				<!-- NyhetsID: $id -->
				<div class=\"nyhettopbar\">
					<div class=\"nyhettitle\">$title</div>
					<div class=\"nyhetoptions\">$options</div>
                    <div class=\"msclearer\"></div>
				</div>
				<div class=\"nyhetcontent\">
					$img
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
				<input type="text" name="nyhetomrade" value="' . $objNyhet->getOmrade() . '" disabled />';
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
		
		// Viktighet
		$html_viktighet = 'Visningstid: <select name="nyhetviktighet">';
		for ($i=1;$i<=3;$i++) {
			if ((int) $i === (int) $objNyhet->getViktighet()) {
				$selected = ' selected="selected"';
			} else {
				$selected = '';
			}
			$html_viktighet .= "<option value=\"$i\"$selected>" . constant("MsNyhet::VIKTIGHET_$i") . "</option>\n";
		}
		$html_viktighet .= '</select>';
		
		// Publiseringsdato
		
		$pubtime = $objNyhet->getPublishTime();
		if (!empty($pubtime)) {
			$pubtimestamp = strtotime($pubtime);
			$dag = (int) date('j', $pubtimestamp);
			$md = (int) date('n', $pubtimestamp);
			$aar = (int) date('Y', $pubtimestamp);
		} else {
			$dag = $md = $aar = 0;
		}
		
		$objCalendar = new tc_calendar("nyhetpubdato", true);
		$objCalendar->setPath('lib/plugins/minside/minside/');
		$objCalendar->startMonday(true);
		$objCalendar->setIcon(MS_IMG_PATH . 'iconCalendar.gif');
		$objCalendar->setDate($dag, $md, $aar);
		
		ob_start(); // må ta vare på output...
		$objCalendar->writeScript();
		$html_calendar = 'Publiseringsdato: ' . ob_get_clean();
		
				
        $output .= '<div class="editnyhet">';
        $output .= '<p><strong>Rediger nyhet</strong></p>';
		$output .= '<div class="toolbar">
                <div id="draft__status"></div>
                <div id="tool__bar"><a href="/wiki/lib/exe/mediamanager.php?ns=msnyheter"
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
		$output .= '<input type="text" name="nyhettitle" value="' . $objNyhet->getTitle() . '" /><br />';
		$output .= $html_omrade . "<br />\n";
		$output .= $html_calendar . "<br />\n";
		$output .= $html_viktighet . "<br />\n";
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
	
	public static function genOmradeAdmin($OmradeCol) {
		
		$output .= "Tilgjengelige områder: <br /><br />\n";
		
		foreach ($OmradeCol as $objOmrade) {
			$output .= $objOmrade->getOmrade() . ' (' . 
				$objOmrade->getAcl() . ")<br />\n";
		}
		
		return $output;
	
	}
	
}
