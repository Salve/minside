<?php
if(!defined('MS_INC')) die();

class ServiceGen {

	private function __construct() { }
	
	public static function genIngenOppdrag($ekstratekst='') {
        if ($ekstratekst) $ekstratekst = '<p>' . $ekstratekst . '</p>';
		return '<div class="mswarningbar">Ingen serviceoppdrag her!'.$ekstratekst.'</div>';
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
