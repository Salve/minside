<?php
if(!defined('MS_INC')) die();

class NyhetGen {

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
			$imgpath = $nyhet->getImagePath();
		} else {
			$imgpath = false;
		}
		$title = $nyhet->getTitle();
		$body = $nyhet->getHtmlBody();
		
		$opt[] = '<img alt="lest" title="Merk nyhet som lest" width="16" ' .
            'height="16" src="' . MS_IMG_PATH . 'success.png" />';
		$opt[] = '<a href="' . MS_NYHET_LINK . "&act=edit&nyhetid=$id\">" .
            '<img alt="rediger" title="Rediger nyhet" width="16" ' .
            'height="16" src="' . MS_IMG_PATH . 'pencil.png" /></a>';
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
        
        $output .= '<div class="editnyhet">';
        $output .= '<p><strong>Rediger nyhet</strong></p>';
        $output .= '<form action="' . MS_NYHET_LINK . '&act=subedit" method="POST">';
        $output .= '<input type="hidden" name="nyhetid" value="' . $objNyhet->getId() . '" />';
        $output .= 'Overskrift: ';
        $output .= '<input type="text" name="nyhettitle" value="' . $objNyhet->getTitle() . '" />';

        
        
        $rawwiki = formText(rawWiki($objNyhet->getWikiPath()));
        
        $output .= '
            <div style="width:99%;">
            <div class="toolbar">
                <div id="draft__status"></div>
                <div id="tool__bar"><a href="/wiki/lib/exe/mediamanager.php?ns=msnyheter"
                    target="_blank">Valg av mediafil</a></div>

                <script type="text/javascript" charset="utf-8"><!--//--><![CDATA[//><!--
                    textChanged = false;
                    //--><!]]></script>
             </div>
             
            
            <input type="hidden" name="id" value="msnyheter:ks:00001_en_liten_testnyhet" />
            <input type="hidden" name="rev" value="" />
            <textarea name="wikitext" id="wiki__text" class="edit" cols="80" rows="10" tabindex="1" >'
            . $rawwiki .
            '</textarea>
            <div id="wiki__editbar" >
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
	
}
