<?php
if(!defined('MS_INC')) die();

class Teller {
	private $_id;
	private $_tellerVerdi;
	private $_tellerDesc;
	private $_tellerType;
	
	function __construct($id, $tellerDesc, $tellerType, $tellerVerdi = 0) {
		$this->_id = $id;
		$this->_tellerNavn = $tellerNavn;
		$this->_tellerDesc = $tellerDesc;
		$this->_tellerVerdi = $tellerVerdi;
		$this->_tellerType = $tellerType;
	}
	
	public function getId() {
		return $this->_id;
	}
		
	public function getTellerDesc() {
		return $this->_tellerDesc;
	}
	
	public function getTellerVerdi() {
		return $this->_tellerVerdi;
	}
	
	public function getTellerType() {
		return $this->_tellerType;
	}

	public function __toString() {
		return $this->_tellerDesc . ': ' . $this->_tellerVerdi;
	}
	
}
