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
	
	public function getTemplate(Erstatter $objErstatter, $tplid = null) {

		$rawtpl = self::getRawTemplate($tplid);
		
		return $objErstatter->erstatt($rawtpl);
	
	}
	
	public function getTemplates() {
		
	}
	
	public static function getCurrentTplId() {
		global $msdb;
		
		$sql = "SELECT raptplid FROM feilrap_raptpl WHERE tplisactive='1' ORDER BY raptplid DESC LIMIT 1;";
		$resultat = $msdb->num($sql);
		
		if ($resultat[0][0] > 0) {
			return $resultat[0][0];
		} else {
			return false;
		}
			
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
