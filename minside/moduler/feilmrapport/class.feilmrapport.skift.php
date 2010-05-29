<?php
if(!defined('MS_INC')) die();

class Skift {
	private $_id;
	private $_skiftCreatedTime;
	private $_skiftClosedTime;
	private $_skiftOwnerId;
	private $_skiftLastUpdate;
	public $checkedLastUpdate = false;
	
	public $tellere;
	
	public function __construct($id, $createdtime, $ownerid, $closedtime = null) {
		$this->_id = $id;
		$this->_skiftCreatedTime = $createdtime;
		$this->_skiftOwnerId = $ownerid;
		$this->_skiftClosedTime = $closedtime;
		
		$this->tellere = new TellerCollection();
		$this->tellere->setLoadCallback('_loadTellere', $this);
	}
	
	public function getId() {
		return $this->_id;
	}
	
	public function getSkiftCreatedTime() {
		return $this->_skiftCreatedTime;
	}
	
	public function getSkiftClosedTime() {
		return $this->_skiftClosedTime;
	}
	
	public function getSkiftOwnerId() {
		return $this->_skiftOwnerId;
	}
	
	public function isClosed() {
		return ($this->_skiftClosedTime == null);
	}
	
	public function __toString() {
		return 'SkiftID: ' . $this->_id . ', OwnerID: ' . $this->_skiftOwnerId . '.';
	}
	
	public function _loadTellere(Collection $col) {
		$arTellere = SkiftFactory::getTellereForSkift($this->_id, $col);
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





}
