<?php
if(!defined('MS_INC')) die();

class Skift {
	private $_id;
	private $_skiftCreatedTime;
	private $_skiftClosedTime;
	private $_skiftOwnerId;
	
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





}
