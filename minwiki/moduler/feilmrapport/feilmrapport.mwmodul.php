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
	private $mwdb;
	
	public function __construct($UserID, &$dbHandle) {
		$this->UserID = $UserID;
		$this->mwdb &= $dbHandle;
	}
	
	public function getMwmodulact(){
		return $this->mwmodulact;
	}
	
	public function gen_mwmodul($act, $vars){
		$this->mwmodulact = $act;
		$this->mwmodulvars = $vars;
		
		$this->frapout = 'Dette er output fra feilmrapport! act er: '. $this->mwmodulact .', vars er: ' . $this->mwmodulvars . ', userid er: ' . $this->UserID . '<br />';
		return $this->frapout;
	
	}
	
}