<?php
if(!defined('MW_INC')) die();
class mwmodul_testmodul implements mwmodul{

	private $mwmodulact;
	private $mwmodulvars;
	private $testmodout;
	private $UserID;
	
	public function __construct($UserID) {
	
		$this->UserID = $UserID;
	}
	
	public function gen_mwmodul($act, $vars){
		$this->mwmodulact = $act;
		$this->mwmodulvars = $vars;
		$this->testmodout = 'Dette er output fra testmodul! UserId er: '. $this->UserID .'<br />';
		return $this->testmodout;
	
	}
	
}
