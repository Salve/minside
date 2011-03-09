<?php
if(!defined('MS_INC')) die();

class RapportTemplateFactory {
    
    protected $dbPrefix;
    
    public function __construct($dbprefix) {
        $this->dbPrefix = $dbprefix;
    }
	
	public function getRawTemplate($tplid = null) {
		$objTemplate = $this->getTemplate($tplid);
		
		return $objTemplate->getTemplateTekst();
	
	}
	
	public function getTemplateOutput(Erstatter $objErstatter, $tplid = null) {

		$rawtpl = $this->getRawTemplate($tplid);
		
		return $objErstatter->erstatt($rawtpl);
	
	}
	
	public function getTemplate($tplid = null) {
		global $msdb;
		if (!isset($tplid)) $tplid = $this->getCurrentTplId();
		
		$safetplid = $msdb->quote($tplid);
		
		$sql = "SELECT templatetekst, raptplid, tplisactive, createdate, activesince FROM ". $this->dbPrefix ."_raptpl WHERE raptplid=$safetplid AND isdeleted='0' LIMIT 1;";
		$result = $msdb->assoc($sql);
		
		$objTemplate = new RapportTemplate($result[0]['raptplid'], $result[0]['tplisactive'], $result[0]['createdate'], $result[0]['activesince'], $result[0]['templatetekst'], true);
		$objTemplate->dbPrefix = $this->dbPrefix;
        
		return $objTemplate;
	}
	
	public function getTemplates() {
		global $msdb;
		$colTemplates = new RapportTemplateCollection();
		
		if (!isset($tplid)) $tplid = $this->getCurrentTplId();
		
		$sql = "SELECT templatetekst, raptplid, tplisactive, createdate, activesince FROM ". $this->dbPrefix ."_raptpl WHERE isdeleted='0';";
		$data = $msdb->assoc($sql);
		
		foreach ($data as $datum) {
			$objTemplate = new RapportTemplate($datum['raptplid'], $datum['tplisactive'], $datum['createdate'], $datum['activesince'], $datum['templatetekst'], true);
            $objTemplate->dbPrefix = $this->dbPrefix;
			$colTemplates->addItem($objTemplate);
		}
		
		return $colTemplates;
	}
	
	public function getCurrentTplId() {
		global $msdb;
		$sql = "SELECT raptplid FROM ". $this->dbPrefix ."_raptpl WHERE tplisactive='1' ORDER BY raptplid DESC LIMIT 1;";
		$resultat = $msdb->num($sql);
		
		if ($resultat[0][0] > 0) {
			return $resultat[0][0];
		} else {
			return false;
		}
			
	}
	
}
