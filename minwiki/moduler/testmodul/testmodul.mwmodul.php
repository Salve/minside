<?php
if(!defined('MW_INC')) die();
class mwmodul_testmodul implements mwmodul{

	private $mwmodulact;
	private $mwmodulvars;
	private $testmodout;
	private $UserID;
	private $mwdb;
	
	public function __construct($UserID, &$dbHandle) {
	
		$this->UserID = $UserID;
		$this->mwdb &= $dbHandle;
	}
	
	public function gen_mwmodul($act, $vars){
		$this->mwmodulact = $act;
		$this->mwmodulvars = $vars;
		$this->testmodout = 'Dette er output fra testmodul! UserId er: '. $this->UserID .'<br />';
		return $this->testmodout;
	
	}
	
}