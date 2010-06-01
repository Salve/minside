<?php
if(!defined('MS_INC')) die();
define(MS_FMR_LINK, MS_LINK . "&page=feilmrapport");
require_once('class.feilmrapport.skift.php');
require_once('class.feilmrapport.teller.php');
require_once('class.feilmrapport.skiftfactory.php');
require_once('class.feilmrapport.tellercollection.php');

class msmodul_feilmrapport implements msmodul{

	private $_msmodulact;
	private $_msmodulvars;
	private $_frapout;
	private $_userId;
	private $_accessLvl;
	private $_currentSkiftId;
	
	public function __construct($UserID, $accesslvl) {
		$this->_userId = $UserID;
		$this->_accessLvl = $accesslvl;
	}
	
	public function getMsmodulact(){
		return $this->_msmodulact;
	}
	
	public function gen_msmodul($act, $vars){
		$this->_msmodulact = $act;
		$this->_msmodulvars = $vars;
		
		$this->_frapout .= 'Output fra feilmrapport: act er: '. $this->_msmodulact . ', userid er: ' . $this->_userId . '<br />';
		
		switch($this->_msmodulact) {
			case "telleradm":
				$this->_frapout .= $this->_genTellerAdm();
				break;
			case "closeskift":
				$this->_closeCurrSkift();
				$this->_frapout .= $this->genSkift();
				break;
			case "nyttskift":
				$this->_createNyttSkift();
				$this->_frapout .= $this->genSkift();
				break;
			case "mod_teller":
				try {
					if(array_key_exists('inc_teller', $_REQUEST)) {
						$this->_changeTeller($_REQUEST['tellerid'], false);
					} elseif(array_key_exists('dec_teller', $_REQUEST)) {
						$this->_changeTeller($_REQUEST['tellerid'], true);
					}
				}
				catch (Exception $e) {
					msg($e->getMessage(), -1);
				}
			case "show":
			default:
				$this->_frapout .= $this->genSkift();
		}
		
		//$this->_frapout .= '<br /><img width="600" height="200" src="lib/plugins/minside/minside/moduler/diff/chartimg.php">';
						
		return $this->_frapout;
	
	}
	
	public function genSkift(){
	
		$skiftID = $this->getCurrentSkiftId();
		if ($skiftID === false) return $this->_genNoCurrSkift();
		
		$skiftout .= '<div class="skift_full">';
		
		try{
			$objSkift = SkiftFactory::getSkift($skiftID);
		} catch(Exception $e) {
			die($e->getMessage());
		}
		
		$skiftout .= 'Output fra genSkift: ' . $objSkift . '<br />';
		
		$skiftout .= '<p>Skift opprettet: ' . $objSkift->getSkiftCreatedTime() . '</p>';
		
		$colUlogget = new TellerCollection();
		$arUlogget = array();
		
		$colSecTeller = new TellerCollection();
		$arSecTeller = array();
		
		$skiftout .= '<table>';	
		foreach($objSkift->tellere as $objTeller) {
			switch ($objTeller->getTellerType()) {
				case 'TELLER':
					$skiftout .= '<tr>';	
					$skiftout .= '<form action="' . MS_FMR_LINK . '" method="POST">';
					$skiftout .= '<td>' . $objTeller->getTellerDesc() . ':</td><td>' . $objTeller->getTellerVerdi() . '</td>';	
					$skiftout .= '<input type="hidden" name="act" value="mod_teller" />';
					$skiftout .= '<input type="hidden" name="tellerid" value="' . $objTeller->getId() . '" />';
					$skiftout .= '<td><div class="inc_dec"><input type="submit" class="button" name="inc_teller" value="+" /><input type="submit" class="button" name="dec_teller" value="-" /></div></td>';
					$skiftout .= '</form>';
					$skiftout .= "</tr>\n\n";
					break;
				case 'ULOGGET':
					if ($objTeller->getTellerVerdi() > 0) $colUlogget->addItem(clone($objTeller));
					$arUlogget[$objTeller->getId()] = $objTeller->getTellerDesc();
					break;
				case 'SECTELLER':
					if ($objTeller->getTellerVerdi() > 0) $colSecTeller->addItem(clone($objTeller));
					$arSecTeller[$objTeller->getId()] = $objTeller->getTellerDesc();
					break;
			}
		}
		
		if (!empty($arSecTeller)){
			$skiftout .= '<tr>';
			$skiftout .= '<form action="' . MS_FMR_LINK . '" method="POST">';
			$skiftout .= '<input type="hidden" name="act" value="mod_teller" />';
			$skiftout .= '<td><select name="tellerid">';
			$skiftout .= '<option value="NOSEL">Annet: </option>';
			foreach ($arSecTeller as $tellerid => $tellerdesc) {
				$skiftout .= '<option value="' . $tellerid . '">' . $tellerdesc . '</option>';
			}
			$skiftout .= '</select></td><td></td>';
			$skiftout .= '<td><div class="inc_dec"><input type="submit" class="button" name="inc_teller" value="+" /><input type="submit" class="button" name="dec_teller" value="-" /></div></td>';
			$skiftout .= '</form>';
			$skiftout .= "</tr>\n\n";
		}		
		
		if (!empty($arUlogget)){
			$skiftout .= '<tr>';
			$skiftout .= '<form action="' . MS_FMR_LINK . '" method="POST">';
			$skiftout .= '<input type="hidden" name="act" value="mod_teller" />';
			$skiftout .= '<td><select name="tellerid">';
			$skiftout .= '<option value="NOSEL">Ulogget: </option>';
			foreach ($arUlogget as $tellerid => $tellerdesc) {
				$skiftout .= '<option value="' . $tellerid . '">' . $tellerdesc . '</option>';
			}
			$skiftout .= '</select></td><td></td>';
			$skiftout .= '<td><div class="inc_dec"><input type="submit" class="button" name="inc_teller" value="+" /><input type="submit" class="button" name="dec_teller" value="-" /></div></td>';
			$skiftout .= '</form>';
			$skiftout .= "</tr>\n\n";
		}
		
		$skiftout .= '</table><br /><br />';

		if ($colSecTeller->length() > 0) {
			$skiftout .= '<p>';
			$skiftout .= '<strong>Annet:</strong><br />';
			
			foreach ($colSecTeller as $objTeller) {
				$skiftout .= $objTeller . '<br />';
			}
			$skiftout .= '</p>';
		}
		
		if ($colUlogget->length() > 0) {
			$skiftout .= '<p>';
			$skiftout .= '<strong>Uloggede samtaler:</strong><br />';
			
			foreach ($colUlogget as $objTeller) {
				$skiftout .= $objTeller . '<br />';
			}
			$skiftout .= '</p>';
		}

		// Close skift knapp
		$skiftout .= '<form method="post" action="' . MS_FMR_LINK . '">';
		$skiftout .= '<input type="hidden" name="act" value="closeskift" />';
		$skiftout .= '<input type="submit" value="Avslutt skift" />';
		$skiftout .= '</form>';
		
		$skiftout .= '</div>'; // skift_full
		
		
		return $skiftout;
	
	}
	
	private function _genTellerAdm() {
		$output .= 'TELLERADMIN';
	
		return $output;
	}
	
	public function getCurrentSkiftId($userid = false) {
		if ($userid === false) $userid = $this->_userId;
		if (isset($this->_currentSkiftId) && $userid == $this->_userId) return $this->_currentSkiftId;
		
		global $msdb;
		$userid = $msdb->quote($userid);

		$result = $msdb->num("SELECT skiftid FROM feilrap_skift WHERE israpportert='0' AND skiftclosed IS NULL AND userid=$userid ORDER BY skiftid DESC LIMIT 1;");
	
		if (is_numeric($result[0][0])) {
			$this->_currentSkiftId = $result[0][0];
			return $result[0][0];
		} else {
			return false;
		}
	}
	
	public function getParamValue($param, &$fromrequest = false){
		if (array_key_exists($param, $this->_msmodulvars)) {
			$fromrequest = false;
			return $this->_msmodulvars[$param];
		} else if (array_key_exists($param, $_REQUEST)) {
			$fromrequest = true;
			return $_REQUEST[$param];
		} else {
			return false;
		}
	
	}
	
	private function _changeTeller($id, $decrease = false) {
		
		$tellerid = $id;
		$skiftid = $this->getCurrentSkiftId();
		
		if ($skiftid === false) die('Forsøk på å endre teller uten å ha et aktivt skift!');
		
		if ($id == 'NOSEL') {
			throw new Exception('Du må gjøre et valg i listen!');
			return false;
		}
		
		try {
			$objTeller = SkiftFactory::getTeller($tellerid, $skiftid);
			$objTeller->modTeller($decrease);
		} 
		catch (Exception $e){
			throw new Exception($e->getMessage());
			return false;
		}
		
		return true;
	}
		
	private function _genNoCurrSkift() {
	
		$output .= '<div class="noskift">';
		$output .= '<p>Du har ikke noe aktivt skift. Det kan være flere årsaker til dette:</p>';
		$output .= '<ul>';
		$output .= '<li>Du har avsluttet skiftet ditt</li>';
		$output .= '<li>Skiftet ditt har blitt inkludert i en rapport</li>';
		//$output .= '<li>Skiftet ditt har ikke blitt oppdatert på over 9 timer og er utløpt</li>'; // Ikke implementert
		$output .= '</ul><br/>';
		$output .= '<form method="post" action="' . MS_FMR_LINK . '">';
		$output .= '<input type="hidden" name="act" value="nyttskift" />';
		$output .= '<input type="submit" value="Start nytt skift!" />';
		$output .= '</form>';
		$output .= '</div>';
		
	
		return $output;
	
	}
	
	private function _createNyttSkift() {
		global $msdb;
		if (!$this->getCurrentSkiftId() === false) { die('Kan ikke opprette nytt skift når det allerede finnes et aktivt skift.'); }
		
		$result = $msdb->exec("INSERT INTO feilrap_skift (skiftcreated, israpportert, userid, skiftlastupdate) VALUES (now(), '0', " . $msdb->quote($this->_userId) . ", now());");
		if ($result != 1) {
			die('Klarte ikke å opprette skift!');
		} else {
			if (isset($this->_currentSkiftId)) { unset($this->_currentSkiftId); }
			return true;
		}


		
	}
	
	private function _closeCurrSkift() {
		global $msdb;
		if ($this->getCurrentSkiftId() === false) { die('Kan ikke avslutte skift: Finner ikke aktivt skift.'); }
		
		$result = $msdb->exec("UPDATE feilrap_skift SET skiftclosed=now() WHERE skiftid=" . $msdb->quote($this->getCurrentSkiftId()) . ";");
		if ($result != 1) {
			die('Klarte ikke å lukke skift med id: ' . $this->getCurrentSkiftId());
		} else {
			if (isset($this->_currentSkiftId)) { unset($this->_currentSkiftId); }
			return true;
		}
	
	}
			
	public function registrer_meny(MenyitemCollection &$meny){
		$lvl = $this->_accessLvl;
		
		$toppmeny = new Menyitem('FeilM Rapport','&page=feilmrapport');
		$telleradmin = new Menyitem('Rediger tellere','&page=feilmrapport&act=telleradm');
		
		if ($lvl > MSAUTH_NONE) { 
			if (($lvl == MSAUTH_ADMIN) && isset($this->_msmodulact)) {
				$toppmeny->addChild($telleradmin);
			}
			$meny->addItem($toppmeny); 
		}
		
	}
		
	
}


