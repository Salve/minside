<?php
if(!defined('MS_INC')) die();

class RapportTemplate {
	
	public static function getRawTemplate($tplid = null) {
		global $msdb;
		if (!isset($tplid)) $tplid = self::getCurrentTplId();
		
		$safetplid = $msdb->quote($tplid);
		
		$sql = "SELECT templatetekst FROM feilrap_raptpl WHERE raptplid=$safetplid;";
		$result = $msdb->num($sql);
		
		return $result[0][0];
	
	}
	
	public static function getCurrentTplId() {
	
		return (integer) 1;
	
	}
	
	public static function saveTemplate($inputtekst, $tplid = null) {
		global $msdb;
		
		$safeinputtekst = $msdb->quote($inputtekst);
		
		if ($tplid) {
			$safetplid = $msdb->quote($tplid);
			$sql = "UPDATE feilrap_raptpl SET templatetekst=$safeinputtekst WHERE raptplid=$safetplid;";	
		} else {
			$sql = "INSERT INTO feilrap_raptpl (templatetekst) VALUES ($safeinputtekst);";
		}
		
		$result = $msdb->exec($sql);
		
		if ($result == 1) {
			return true;
		} else {
			return false;
		}
		
	}
	
}
