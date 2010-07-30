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
		
		$options = 'lest';
		
		$output = "
			<div class=\"nyhetcontainer\">
			<div class=\"nyhet\">
				<!-- NyhetsID: $id -->
				<div class=\"nyhettopbar\">
					<div class=\"nyhettitle\">$title</div>
					<div class=\"nyhetoptions\">$options</div>
				</div>
				<div class=\"nyhetcontent\">
					<div class=\"nyhetimgleft\">$img</div>
					<div class=\"nyhetbody\">$body</div>
					<div class=\"msclearer\"></div>
				</div>
			</div>
			</div>
		";
		
	}
	
}