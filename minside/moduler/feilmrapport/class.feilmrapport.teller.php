<?php
if(!defined('MS_INC')) die();

class Teller {
	private $_id;
	private $_skiftId;
	private $_isActive;
	private $_tellerVerdi;
	private $_tellerName;
	private $_tellerDesc;
	private $_tellerType;
	
	function __construct($id, $skiftid, $tellerName, $tellerDesc, $tellerType, $tellerVerdi = 0, $isactive = true) {
		$this->_id = $id;
		$this->_skiftId = $skiftid;
		$this->_isActive = $isactive;
		$this->_tellerDesc = $tellerDesc;
		$this->_tellerName = $tellerName;
		$this->_tellerVerdi = $tellerVerdi;
		$this->_tellerType = $tellerType;
	}
	
	public function getId() {
		return $this->_id;
	}
	
	public function getSkiftId() {
		return $this->_skiftId;
	}
		
	public function getTellerDesc() {
		return $this->_tellerDesc;
	}
	
	public function getTellerName() {
		return $this->_tellerName;
	}
	
	public function getTellerVerdi() {
		return $this->_tellerVerdi;
	}
	
	public function setTellerVerdi($input) {
		$this->_tellerVerdi = $input;
	}
	
	public function getTellerType() {
		return $this->_tellerType;
	}
	
	public function isActive() {
		return $this->_isActive;
	}

	public function __toString() {
		return $this->_tellerDesc . ': ' . $this->_tellerVerdi;
	}
	
	public function modTeller($decrease = false) {
		global $msdb;
		
		$verdi = ($decrease === false) ? 1 : -1;
		
		if (!$this->_isActive) throw new Exception('Kan ikke endre teller som ikke er aktiv');
		if ($decrease && !($this->_tellerVerdi > 0)) throw new Exception('Tellerverdi er 0, kan ikke redusere teller');
		
		$skiftid = $msdb->quote($this->_skiftId);
		$tellerid = $msdb->quote($this->_id);
		
		$result = $msdb->exec("INSERT INTO feilrap_tellerakt (tidspunkt, skiftid, tellerid, verdi) VALUES (now(), $skiftid, $tellerid, '$verdi');");
		if ($result != 1) {
			throw new Exception('Klarte ikke å endre tellerverdi!');
			return false;
		} else {
			return true;
		}	
	}
	
}
