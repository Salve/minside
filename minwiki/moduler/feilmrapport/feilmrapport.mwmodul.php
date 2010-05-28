<?php
if(!defined('MW_INC')) die();
define(MW_FMR_LINK, MW_LINK . "&page=feilmrapport");
require_once('class.feilmrapport.skift.php');
require_once('class.feilmrapport.teller.php');
require_once('class.feilmrapport.skiftfactory.php');
require_once('class.feilmrapport.tellercollection.php');

class mwmodul_feilmrapport implements mwmodul{

	private $mwmodulact;
	private $mwmodulvars;
	private $frapout;
	private $UserID;
	private $_accessLvl;
	private $_currentSkiftId;
	
	public function __construct($UserID, $accesslvl) {
		$this->UserID = $UserID;
		$this->_accessLvl = $accesslvl;
	}
	
	public function getMwmodulact(){
		return $this->mwmodulact;
	}
	
	public function gen_mwmodul($act, $vars){
		$this->mwmodulact = $act;
		$this->mwmodulvars = $vars;
		
		$this->frapout .= 'Output fra feilmrapport: act er: '. $this->mwmodulact . ', userid er: ' . $this->UserID . '<br />';
		
		switch($this->mwmodulact) {
			case "closeskift":
				$this->_closeCurrSkift();
				$this->frapout .= $this->genSkift();
				break;
			case "nyttskift":
				$this->_createNyttSkift();
				$this->frapout .= $this->genSkift();
				break;
			case "mod_teller":
				if(array_key_exists('inc_teller', $_REQUEST)) {
					$this->_changeTeller($_REQUEST['tellerid'], false);
				} elseif(array_key_exists('dec_teller', $_REQUEST)) {
					if($this->_checkTellerValue($_REQUEST['tellerid'], $this->getCurrentSkiftId()) > 0) {
						$this->_changeTeller($_REQUEST['tellerid'], true);
					} else {
						msg("Tellerverdi er 0, kan ikke redusere teller",-1);
					}
				}
			case "show":
			default:
				$this->frapout .= $this->genSkift();
		}
		
		//$this->frapout .= '<br /><img width="600" height="200" src="lib/plugins/minwiki/minwiki/moduler/diff/chartimg.php">';
						
		return $this->frapout;
	
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
		
		$skiftout .= '<table>';	
		foreach($objSkift->tellere as $objTeller) {
			$skiftout .= '<tr>';	
			$skiftout .= '<form action="' . MW_FMR_LINK . '" method="POST">';
			$skiftout .= '<td>' . $objTeller->getTellerDesc() . ':</td><td>' . $objTeller->getTellerVerdi() . '</td>';	
			$skiftout .= '<input type="hidden" name="act" value="mod_teller" />';
			$skiftout .= '<input type="hidden" name="tellerid" value="' . $objTeller->getId() . '" />';
			$skiftout .= '<td><div class="inc_dec"><input type="submit" class="button" name="inc_teller" value="+" /><input type="submit" class="button" name="dec_teller" value="-" /></div></td>';
			$skiftout .= '</form>';
			$skiftout .= "</tr>\n\n";
		}
		$skiftout .= '</table>';	
		
		$skiftout .= '<form method="post" action="' . MW_FMR_LINK . '">';
		$skiftout .= '<input type="hidden" name="act" value="closeskift" />';
		$skiftout .= '<input type="submit" value="Avslutt skift" />';
		$skiftout .= '</form>';
		
		$skiftout .= '</div>'; // skift_full
		
		
		return $skiftout;
	
	}
	
	public function getCurrentSkiftId($userid = false) {
		if ($userid === false) $userid = $this->UserID;
		if (isset($this->_currentSkiftId) && $userid == $this->UserID) return $this->_currentSkiftId;
		
		global $mwdb;
		$userid = $mwdb->quote($userid);

		$result = $mwdb->num("SELECT skiftid FROM feilrap_skift WHERE israpportert='0' AND skiftclosed IS NULL AND userid=$userid ORDER BY skiftid DESC LIMIT 1;");
	
		if (is_numeric($result[0][0])) {
			$this->_currentSkiftId = $result[0][0];
			return $result[0][0];
		} else {
			return false;
		}
	}
	
	public function getParamValue($param, &$fromrequest = false){
		if (array_key_exists($param, $this->mwmodulvars)) {
			$fromrequest = false;
			return $this->mwmodulvars[$param];
		} else if (array_key_exists($param, $_REQUEST)) {
			$fromrequest = true;
			return $_REQUEST[$param];
		} else {
			return false;
		}
	
	}
	
	private function _changeTeller($id, $decrease = false) {
	
		global $mwdb;
		
		$id = $mwdb->quote($id);
		$skiftid = $this->getCurrentSkiftId();
		if ($skiftid === false) die('Forsøk på å endre teller uten å ha et aktivt skift!');
		$verdi = ($decrease === false) ? 1 : -1;
		$result = $mwdb->exec("INSERT INTO feilrap_tellerakt (tidspunkt, skiftid, tellerid, verdi) VALUES (now(), '$skiftid', $id, '$verdi');");

		if ($result != 1) {
			die('Klarte ikke å endre tellerverdi!');
		} else {
			$this->_updateCurrSkift();
			return true;
		}	
		
	}
	
	private function _updateCurrSkift(){
	
		global $mwdb;
		
		if ($this->getCurrentSkiftId() === false) { die('Kan ikke oppdatere skift når du ikke har et aktivt skift.'); }
		
		$result = $mwdb->exec("UPDATE feilrap_skift SET skiftlastupdate=now() WHERE skiftid=" . $mwdb->quote($this->getCurrentSkiftId()) . ";");
		
		if ($result != 1) {
			die('Klarte ikke å oppdatere skiftlastupdate!');
		} else {
			return true;
		}
		
	}
	
	private function _genNoCurrSkift() {
	
		$output .= '<div class="noskift">';
		$output .= '<p>Du har ikke noe aktivt skift. Det kan være flere årsaker til dette:</p>';
		$output .= '<ul>';
		$output .= '<li>Du har avsluttet skiftet ditt</li>';
		$output .= '<li>Skiftet ditt har blitt inkludert i en rapport</li>';
		//$output .= '<li>Skiftet ditt har ikke blitt oppdatert på over 9 timer og er utløpt</li>'; // Ikke implementert
		$output .= '</ul><br/>';
		$output .= '<form method="post" action="' . MW_FMR_LINK . '">';
		$output .= '<input type="hidden" name="act" value="nyttskift" />';
		$output .= '<input type="submit" value="Start nytt skift!" />';
		$output .= '</form>';
		$output .= '</div>';
		
	
		return $output;
	
	}
	
	private function _createNyttSkift() {
		global $mwdb;
		if (!$this->getCurrentSkiftId() === false) { die('Kan ikke opprette nytt skift når det allerede finnes et aktivt skift.'); }
		
		$result = $mwdb->exec("INSERT INTO feilrap_skift (skiftcreated, israpportert, userid, skiftlastupdate) VALUES (now(), '0', " . $mwdb->quote($this->UserID) . ", now());");
		if ($result != 1) {
			die('Klarte ikke å opprette skift!');
		} else {
			if (isset($this->_currentSkiftId)) { unset($this->_currentSkiftId); }
			return true;
		}


		
	}
	
	private function _closeCurrSkift() {
		global $mwdb;
		if ($this->getCurrentSkiftId() === false) { die('Kan ikke avslutte skift: Finner ikke aktivt skift.'); }
		
		$result = $mwdb->exec("UPDATE feilrap_skift SET skiftclosed=now() WHERE skiftid=" . $mwdb->quote($this->getCurrentSkiftId()) . ";");
		if ($result != 1) {
			die('Klarte ikke å lukke skift med id: ' . $this->getCurrentSkiftId());
		} else {
			if (isset($this->_currentSkiftId)) { unset($this->_currentSkiftId); }
			return true;
		}
	
	}
	
	
	private function _checkTellerValue($tellerid, $skiftid) {
		
		global $mwdb;
		
		$tellerid = $mwdb->quote($tellerid);
		$skiftid = $mwdb->quote($skiftid);
		
		$sql = "SELECT SUM(IF(feilrap_tellerakt.skiftid=$skiftid,feilrap_tellerakt.verdi,0)) AS 'tellerverdi' FROM feilrap_teller LEFT JOIN feilrap_tellerakt ON feilrap_teller.tellerid = feilrap_tellerakt.tellerid WHERE feilrap_teller.tellerid=$tellerid;";
				
		$result = $mwdb->num($sql);
		
		if (is_numeric($result[0][0])) {
			return $result[0][0];
		} else {
			return false;
		}
		
	
	}
	
	public function registrer_meny(MenyitemCollection &$meny){
		
		if ($this->_accessLvl > MWAUTH_NONE) { $meny->addItem(new Menyitem('FeilM Rapport','&page=feilmrapport')); }
		
	}
		
	
}


