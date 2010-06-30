<?php
if(!defined('MS_INC')) die();

class RapportTemplate {
	
	private $_id;
	private $_isactive;
	private $_templatetekst;
	private $_createdate;
	private $_livedate;
	private $_numrapporter;
	
	public function __construct($id, $isactive, $createdate, $livedate, $templatetekst) {
		$this->_id = $id;
		$this->_isactive = (bool) $isactive;
		$this->_templatetekst = $templatetekst;
		$this->_createdate = $createdate;
		$this->_livedate = $livedate;
	}

	public function getTemplateTekst() {
		return $this->_templatetekst;
	}
	
	public function getId() {
		return $this->_id;
	}
	
	public function getNumRapporter() {
		if (isset($this->_numrapporter)) {
			return $this->_numrapporter;
		} else {
			global $msdb;
			$safetplid = $msdb->quote($this->_id);
			$sql = "SELECT COUNT(*) FROM (SELECT 'ingenting' FROM feilrap_rapport WHERE templateid=$safetplid) AS T;";
			$data = $msdb->num($sql);
			$resultat = $data[0][0];
			
			$this->_numrapporter = $resultat;
			return $resultat;	
		}
	}
	
	public function getCreateDate() {
		return strtotime($this->_createdate);
	}
	
	public function getLiveDate() {
		return strtotime($this->_livedate);
	}
	
	public function isActive() {
		return $this->_isactive;
	}
	
	public function getTemplateOutput(Erstatter $objErstatter) {
		return $objErstatter->erstatt($this->_templatetkest);
	}
	
}
