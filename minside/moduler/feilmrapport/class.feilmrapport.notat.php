<?php
if(!defined('MS_INC')) die();

class Notat {
	private $_id;
	private $_skiftId;
	private $_isActive;
	private $_notatTekst;
	private $_notatType;
	private $_isSaved;
	private $_isRapportert;
	
	function __construct($id, $skiftid, $notatType, $notatTekst, $issaved, $isactive = true, $israpportert = false) {
		$this->_id = $id;
		$this->_skiftId = $skiftid;
		$this->_isActive = $isactive;
		$this->_notatTekst = $notatTekst;
		$this->_notatType = $notatType;
		$this->_isSaved = $issaved;
		$this->_isRapportert = $israpportert;
	}
	
	public function getId() {
		return $this->_id;
	}
	
	public function getSkiftId() {
		return $this->_skiftId;
	}
	
	public function getNotatTekst() {
		return $this->_notatTekst;
	}
	
	public function getNotatType() {
		return $this->_notatType;
	}
	
	public function isActive() {
		return $this->_isActive;
	}
	
	public function isSaved() {
		return $this->_isSaved;
	}
	
	public function isRapportert() {
		return $this->_isRapportert;
	}

	public function __toString() {
		return (string) $this->_notatTekst;
	}
	
	public function setNotatTekst($inputtekst) {
		global $msdb;
		
		$tekst = htmlspecialchars($inputtekst, ENT_QUOTES, 'UTF-8');
		
		if (!$this->_isActive) throw new Exception('Kan ikke endre notat som er slettet');
		if ($tekst == '') throw new Exception('Kan ikke lagre tomt notat, slett notatet om ønsket');
		
		$safeskiftid = $msdb->quote($this->_skiftId);
		$safenotatid = $msdb->quote($this->_id);
		$safenotattype = $msdb->quote($this->_notatType);
		$safenotattekst = $msdb->quote($tekst);
		$saferapportert = ($this->isRapportert()) ? 1 : 0;
		
		if ($this->_isSaved) {
			$result = $msdb->exec("UPDATE feilrap_notat SET notattekst=$safenotattekst WHERE notatid=$safenotatid;");
		} else {
			$result = $msdb->exec("INSERT INTO feilrap_notat (isactive, notattype, notattekst, skiftid, inrapport) VALUES ('1', $safenotattype, $safenotattekst, $safeskiftid, '$saferapportert');");
		}
		
		if ($result != 1) {
			throw new Exception('Klarte ikke å endre tellerverdi!');
			return false;
		} else {
			return true;
		}	
	}
	
}
