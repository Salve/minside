<?php
if(!defined('MW_INC')) die();
require_once('class.feilmrapport.skift.php');
require_once('class.feilmrapport.teller.php');
require_once('class.feilmrapport.skiftfactory.php');
require_once('class.feilmrapport.tellercollection.php');

class mwmodul_feilmrapport implements mwmodul{

	private $mwmodulact;
	private $mwmodulvars;
	private $frapout;
	private $UserID;
	
	public function __construct($UserID) {
		$this->UserID = $UserID;
	}
	
	public function getMwmodulact(){
		return $this->mwmodulact;
	}
	
	public function gen_mwmodul($act, $vars){
		$this->mwmodulact = $act;
		$this->mwmodulvars = $vars;
		
		$this->frapout .= 'Dette er output fra feilmrapport! act er: '. $this->mwmodulact .', vars er: ' . $this->mwmodulvars . ', userid er: ' . $this->UserID . '<br />';
		
		$skiftID = 2;
		try{
			$objSkift = SkiftFactory::getSkift($skiftID);
		} catch(Exception $e) {
			die($e->getMessage());
		}
		
		$this->frapout .= $objSkift . '<br />';
		
		foreach($objSkift->tellere as $objTeller) {
			$this->frapout .= $objTeller->getTellerDesc() . ': ' . $objTeller->getTellerVerdi() . '<br />';		
		}
		
		
		return $this->frapout;
	
	}
	
}
