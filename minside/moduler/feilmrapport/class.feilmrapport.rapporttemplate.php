<?php
if(!defined('MS_INC')) die();

class RapportTemplate {
	
	private $_id;
	private $_isactive;
	private $_templatetekst;
	private $_createdate;
	private $_livedate;
	private $_numrapporter;
	private $_issaved;
	
	public function __construct($id, $isactive, $createdate, $livedate, $templatetekst, $issaved = false) {
		$this->_id = $id;
		$this->_isactive = (bool) $isactive;
		$this->_issaved = (bool) $issaved;
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
	
	public function goLive() {
		global $msdb;
		
		if (!$this->isSaved()) throw new Exception('Kun templates som ligger i databasen kan gjøres live.');
		if ($this->isActive()) throw new Exception('Template er allerede live.');
		
		$safetemplateid = $msdb->quote($this->_id);
		$sql = "UPDATE feilrap_raptpl SET tplisactive='1' WHERE raptplid=$safetemplateid LIMIT 1;";
		
		$resultat = $msdb->exec($sql);
		
		if ($resultat) {
			$this->_isactive = true;
			return true;
		} else {
			return false;
		}
	}
	
	public function slettTemplate() {
		global $msdb;
		
		if (!$this->isSaved()) throw new Exception('Kan ikke slette templates som ikke er lagret i database.');
		if ($this->isActive()) throw new Exception('Kan ikke slette live templates.');
		
		$safetemplateid = $msdb->quote($this->_id);
		$sql = "UPDATE feilrap_raptpl SET isdeleted='1' WHERE raptplid=$safetemplateid LIMIT 1;";
		
		$resultat = $msdb->exec($sql);
		
		if ($resultat) {
			return true;
		} else {
			return false;
		}	
	
	}
	
	public function saveTemplate($inputtekst) {
		global $msdb;
		
		if (!$this->isSaved()) throw new Exception('Kan ikke endre templates som ikke er lagret i database.');
		if ($this->isActive()) throw new Exception('Kan ikke endre live templates.');
		if (strlen($inputtekst) < 1) throw new Exception('Kan ikke lagre tomt template. Bruk slett-funksjon.');
		
		$safeinputtekst = $msdb->quote($inputtekst);
		$safetplid = $msdb->quote($this->_id);
		$sql = "UPDATE feilrap_raptpl SET templatetekst=$safeinputtekst WHERE raptplid=$safetplid;";	
		
		$result = $msdb->exec($sql);
		
		if ($result == 1) {
			return true;
		} else {
			return false;
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
	
	public function isSaved() {
		return $this->_issaved;
	}
	
	public function getTemplateOutput(Erstatter $objErstatter) {
		return $objErstatter->erstatt($this->_templatetkest);
	}
	
}
