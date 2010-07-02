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
	private $_tellerOrder;
	
	function __construct($id, $skiftid, $tellerName, $tellerDesc, $tellerType, $tellerVerdi = 0, $isactive = true) {
		$this->_id = $id;
		$this->_skiftId = $skiftid;
		$this->_isActive = $isactive;
		$this->_tellerDesc = $tellerDesc;
		$this->_tellerName = $tellerName;
		$this->_tellerVerdi = $tellerVerdi;
		$this->_tellerType = $tellerType;
		
	}
	
	public function setOrder($order) {
		$this->_tellerOrder = (int) $order;
	}
	
	public function getOrder() {
		if (!isset($this->_tellerOrder)) {
			return 0;
		}
		else {
			return $this->_tellerOrder;
		}
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
	
	public function modTeller($inputverdi, $decrease) {
		global $msdb;

		$resultat = 0;
		if ($this->_validInputVerdi($inputverdi, $resultat)) {
			$verdi = $resultat;
		} else {
			throw new Exception($resultat);
			return false;
		}
		
		$verdi = ($decrease === false) ? $verdi : -$verdi;
		$antattnyverdi = $this->_tellerVerdi + $verdi;

		if (!$this->_isActive) throw new Exception('Kan ikke endre teller som ikke er aktiv');
		if ($antattnyverdi > 1000) throw new Exception('Kan ikke øke teller over maksimalverdi (1000)');
		if ($antattnyverdi < 0) throw new Exception('Kan ikke redusere teller under null');
		
		
		$safeskiftid = $msdb->quote($this->_skiftId);
		$safetellerid = $msdb->quote($this->_id);
		$safeverdi = $msdb->quote($verdi);

		$result = $msdb->exec("INSERT INTO feilrap_tellerakt (tidspunkt, skiftid, tellerid, verdi) VALUES (now(), $safeskiftid, $safetellerid, $safeverdi);");
		if ($result != 1) {
			throw new Exception('Klarte ikke lagre tellerendring i database');
			return false;
		} else {
			return true;
		}	
	}
	
	private function _validInputVerdi($inputverdi, &$output) {
	
		$input = trim($inputverdi);
		$result = preg_match('/^[0-9]{1,2}$/uAD', $input, $matches);
		
		if ($result) {
			$output = (int) $matches[0];
			if ($output === 0) {
				$output = 'Null er ikke en gyldig verdi.';
				return false;
			} else {
				return true;
			}
			return true;
		} else {
			$output = 'Må være gyldig tall, 1-2 siffer. Desimaler og prefiks ikke tillatt.';
			return false;
		}
	
	}
	
}
