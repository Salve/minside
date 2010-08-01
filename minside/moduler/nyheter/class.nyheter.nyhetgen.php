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
		$body = nl2br($nyhet->getHtmlBody());
		
		$opt[] = '<img alt="lest" title="Merk nyhet som lest" width="16" ' .
            'height="16" src="' . MS_IMG_PATH . 'success.png" />';
		$opt[] = '<img alt="rediger" title="Rediger nyhet" width="16" ' .
            'height="16" src="' . MS_IMG_PATH . 'pencil.png" />';
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
	
}