<?php
if(!defined('MS_INC')) die();

class RapportTemplateFactory {
	
	public static function getRawTemplate($tplid = null) {
		$objTemplate = self::getTemplate($tplid);
		
		return $objTemplate->getTemplateTekst();
	
	}
	
	public function getTemplateOutput(Erstatter $objErstatter, $tplid = null) {

		$rawtpl = self::getRawTemplate($tplid);
		
		return $objErstatter->erstatt($rawtpl);
	
	}
	
	public function getTemplate($tplid = null) {
		global $msdb;
		if (!isset($tplid)) $tplid = self::getCurrentTplId();
		
		$safetplid = $msdb->quote($tplid);
		
		$sql = "SELECT templatetekst, raptplid, tplisactive, createdate, activesince FROM feilrap_raptpl WHERE raptplid=$safetplid AND isdeleted='0' LIMIT 1;";
		$result = $msdb->assoc($sql);
		
		$objTemplate = new RapportTemplate($result[0]['raptplid'], $result[0]['tplisactive'], $result[0]['createdate'], $result[0]['activesince'], $result[0]['templatetekst'], true);
		
		return $objTemplate;
	}
	
	public function getTemplates() {
		global $msdb;
		$colTemplates = new RapportTemplateCollection();
		
		if (!isset($tplid)) $tplid = self::getCurrentTplId();
		
		$sql = "SELECT templatetekst, raptplid, tplisactive, createdate, activesince FROM feilrap_raptpl WHERE isdeleted='0';";
		$data = $msdb->assoc($sql);
		
		foreach ($data as $datum) {
			$objTemplate = new RapportTemplate($datum['raptplid'], $datum['tplisactive'], $datum['createdate'], $datum['activesince'], $datum['templatetekst'], true);
			$colTemplates->addItem($objTemplate);
		}
		
		return $colTemplates;
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
