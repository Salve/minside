<?php
if(!defined('MW_INC')) die();
define(MW_FMR_LINK, "?do=minwiki&page=feilmrapport");
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
		}
		
		$this->frapout .= $this->genSkift();
						
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
			$skiftout .= '<td><div class="inc_dec"><input type="submit" name="inc_teller" value="+" style="width: 1.5em" /><input type="submit" name="dec_teller" value="-" style="width: 1.5em" /></div></td>';
			$skiftout .= '</form>';
			$skiftout .= "</tr>\n\n";
		}
		$skiftout .= '</table>';	
		
		$skiftout .= '</div>'; // skift_full
		
		
		return $skiftout;
	
	}
	
	public function getCurrentSkiftId($userid = false) {
		if ($userid === false) $userid = $this->UserID;
		if (isset($this->_currentSkiftId) && $userid = $this->UserID) return $this->_currentSkiftId;
		
		global $mwdb;
		$userid = $mwdb->quote($userid);

		$result = $mwdb->num("SELECT skiftid FROM feilrap_skift WHERE israpportert=0 AND skiftclosed IS NULL AND userid=$userid ORDER BY skiftid DESC LIMIT 1;");
	
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
		$result = $mwdb->exec("INSERT INTO feilrap_tellerakt (tidspunkt, skiftid, tellerid, verdi) VALUES (now(), $skiftid, $id, $verdi);");

	}
	
	private function _genNoCurrSkift() {
	
		return "<p>Du har ikke et aktivt skift, ønsker du å lage et nytt? **IKKE IMPLEMENTERT**</p>";
	
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


