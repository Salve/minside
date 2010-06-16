<?php
if(!defined('MS_INC')) die();

class Rapport {
	private $_id;
	private $_rapportCreatedTime;
	private $_rapportFromTime;
	private $_rapportToTime;
	private $_rapportOwnerId;
	private $_isSaved = false;
	public $rapportSent = false;
	
	public $skift;
	
	public function __construct($id, $createdtime, $ownerid, $issaved = false) {
		$this->_id = $id;
		$this->_rapportCreatedTime = $createdtime;
		$this->_rapportOwnerId = $ownerid;
		$this->_rapportFromTime = $fromtime;
		$this->_rapportToTime = $totime;
		$this->_isSaved = (bool) $issaved;
		
		$this->skift = new SkiftCollection();
		if ($issaved) $this->skift->setLoadCallback('_loadSkift', $this);
	}
	
	public function getId() {
		return $this->_id;
	}
	
	public function getRapportCreatedTime() {
		return $this->_rapportCreatedTime;
	}
	
	public function getRapportFromTime() {
		return $this->_rapportFromTime;
	}
	
	public function getRapportToTime() {
		return $this->_rapportToTime;
	}
	
	public function getRapportOwnerId() {
		return $this->_rapportOwnerId;
	}
	
	public function isSent() {
		return $this->rapportSent;
	}
	
	public function __toString() {
		return 'RapportID: ' . $this->_id . ', OwnerID: ' . $this->_rapportOwnerId . '.';
	}
	
	public function _loadSkift(Collection $col) {
		$arSkift = SkiftFactory::getSkiftForRapport($this->_id, $col);
	}

}
