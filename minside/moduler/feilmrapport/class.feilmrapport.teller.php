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
		$this->_isActive = (bool) $isactive;
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
		if ($this->_tellerVerdi < 0) {
			return 0;
		} else {
			return (int) $this->_tellerVerdi;
		}
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
	
	public function modIsActive() {
	
		if ($this->isActive()) {
			return $this->_setInactive();
		} else {
			return $this->_setActive();
		}	
	}
	
	private function _setInactive() {
		global $msdb;
		
		if ($this->_tellerOrder == false) throw new Exception('Tellerorder ikke satt.');
		
		$safetellerid = $msdb->quote($this->_id);
		$safetellerorder = $msdb->quote($this->_tellerOrder);
		
		$msdb->startTrans();
		
		$sql = "UPDATE feilrap_teller SET tellerorder = tellerorder - 1 WHERE tellerorder >= $safetellerorder";
		$resultat2 = $msdb->exec($sql);
		
		$sql = "UPDATE feilrap_teller SET isactive='0', tellerorder=NULL WHERE tellerid=$safetellerid LIMIT 1;";
		$resultat1 = $msdb->exec($sql);
		
		if ($resultat1 && $resultat2) {
			$msdb->commit();
			return true;
		} else {
			$msdb->rollBack();
			return false;
		}

	}
	
	private function _setActive() {
		global $msdb;
		
		$datum = $msdb->num('SELECT tellerorder FROM feilrap_teller ORDER BY tellerorder DESC LIMIT 1');
		$nyorder = $datum[0] + 1;
		
		$safetellerid = $msdb->quote($this->_id);
		$safenyorder = $msdb->quote($nyorder);
		
		$sql = "UPDATE feilrap_teller SET isactive='1', tellerorder=$safenyorder WHERE tellerid=$safetellerid LIMIT 1;";
		$resultat = $msdb->exec($sql);
		
		return (bool) $resultat;
	}	
	
	public function modOrderOpp() {
		return $this->_modOrder(true);
	}
	
	public function modOrderNed() {
		return $this->_modOrder(false);
	}
	
	private function _modOrder($modopp) {
		global $msdb;
		if (!$this->isActive()) throw new Exception('Teller er ikke aktiv.');
		if ($this->_tellerOrder == false) throw new Exception('Tellerorder ikke satt.');
	
		$oldorder = $this->_tellerOrder;
		$neworder = $this->_tellerOrder + (($modopp) ? -1 : 1);

		$safeoldorder = $msdb->quote($oldorder);
		$safeneworder = $msdb->quote($neworder);
		$safetellerid = $msdb->quote($this->_id);
		
		$msdb->startTrans();
		$resultat = $msdb->exec("UPDATE feilrap_teller SET tellerorder=$safeoldorder WHERE tellerorder=$safeneworder LIMIT 1;");
		if ($resultat == 0) {
			$msdb->rollBack();
			throw new Exception('Ingen teller ' . (($modopp) ? 'over' : 'under') . ' telleren du forsøkte å flytte.');
		}
		
		$resultat = $msdb->exec("UPDATE feilrap_teller SET tellerorder=$safeneworder WHERE tellerorder=$safeoldorder AND tellerid=$safetellerid LIMIT 1;");
		if ($resultat == 0) {
			$msdb->rollBack();
			throw new Exception('Databaseoppdatering feilet.');
		} else {
			$msdb->commit();
			$this->_tellerOrder = $neworder;
			return true;
		}
		
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
