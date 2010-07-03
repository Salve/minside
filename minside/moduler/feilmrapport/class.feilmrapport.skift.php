<?php
if(!defined('MS_INC')) die();

class Skift {
	private $_id;
	private $_skiftCreatedTime;
	private $_skiftClosedTime;
	private $_skiftOwnerId;
	private $_skiftOwnerName;
	private $_skiftLastUpdate;
	private $_skiftIsRapportert;
	private $_skiftRapportId;
	public $checkedLastUpdate = false;
	
	public $tellere;
	public $notater;
	
	public function __construct($id, $createdtime, $ownerid, $closedtime = null, $rapportert = null, $rapportid = null) {
		$this->_id = $id;
		$this->_skiftCreatedTime = $createdtime;
		$this->_skiftOwnerId = $ownerid;
		$this->_skiftClosedTime = $closedtime;
		$this->_skiftIsRapportert = $rapportert;
		$this->_skiftRapportId = $rapportid;
		
		$this->tellere = new TellerCollection();
		$this->tellere->setLoadCallback('_loadTellere', $this);
		
		$this->notater = new NotatCollection();
		$this->notater->setLoadCallback('_loadNotater', $this);
	}
	
	public function getId() {
		return $this->_id;
	}
	
	public function getNumActiveTellere() {
		if ($this->tellere->length() == 0) return 0; // sørger for at tellere er lastet via callback
		
		$tellercounter = 0;
		
		foreach ($this->tellere as $objTeller) {
			if ($objTeller->isActive()) $tellercounter++;
		}
		
		return $tellercounter;
		
	}
	
	public function getSkiftCreatedTime() {
		return $this->_skiftCreatedTime;
	}
	
	public function getSkiftOwnerName() {
		return $this->_skiftOwnerName;
	}
	
	public function setSkiftOwnerName($ownername) {
		$this->_skiftOwnerName = (string) $ownername;
	}
	
	public function getSkiftClosedTime() {
		return $this->_skiftClosedTime;
	}
	
	public function getSkiftOwnerId() {
		return $this->_skiftOwnerId;
	}
	
	public function isClosed() {
		return (bool)($this->_skiftClosedTime != null);
	}
	
	public function isRapportert() {
		return (bool)($this->_skiftIsRapportert);
	}
	
	public function getSkiftRapportId() {
		return $this->_skiftRapportId;
	}
	
	public function __toString() {
		return 'SkiftID: ' . $this->_id . ', OwnerID: ' . $this->_skiftOwnerId . '.';
	}
	
	public function _loadTellere(Collection $col) {
		$arTellere = SkiftFactory::getTellereForSkift($this->_id, $col);
	}
	
	public function _loadNotater(Collection $col) {
		$arNotater = SkiftFactory::getNotaterForSkift($this->_id, $col);
	}
	
	public function getSkiftLastUpdate() {
		if ($this->checkedLastUpdate) {
			return $this->_skiftLastUpdate;
		} else {
			global $msdb;
			$safeskiftid = $msdb->quote($this->_id);
			$result = $msdb->num("SELECT tidspunkt FROM feilrap_tellerakt WHERE skiftid=$safeskiftid ORDER BY telleraktid DESC LIMIT 1;");
			$this->_skiftLastUpdate = $result[0][0];
			$this->checkedLastUpdate = true;
			return $this->getSkiftLastUpdate();
		}
	
	
	}
	
	public function closeSkift() {
		global $msdb;
		
		if ($this->isClosed()) return false;
				
		$safeskiftid = $msdb->quote($this->_id);
		$result = $msdb->exec("UPDATE feilrap_skift SET skiftclosed=now() WHERE skiftid=$safeskiftid;");
		if ($result != 1) {
			return false;
		} else {
			return true;
		}
	
	}





}
