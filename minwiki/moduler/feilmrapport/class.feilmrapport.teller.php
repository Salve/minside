<?php
if(!defined('MW_INC')) die();

class Teller {
	private $_id;
	private $_tellerNavn;
	private $_tellerVerdi;
	private $_tellerDesc;
	
	function __construct($id, $tellerNavn, $tellerDesc, $tellerVerdi = 0) {
		$this->_id = $id;
		$this->_tellerNavn = $tellerNavn;
		$this->_tellerType = $tellerType;
		$this->_tellerDesc = $tellerDesc;
		$this->_tellerVerdi = $tellerVerdi;
	}
	
	public function getId() {
		return $this->_id;
	}
	
	public function getTellerNavn() {
		return $this->_tellerNavn;
	}
	
	public function getTellerDesc() {
		return $this->_tellerDesc;
	}
	
	public function getTellerVerdi() {
		return $this->_tellerVerdi;
	}

	public function __toString() {
		return $this->_tellerDesc;
	}
	
}
