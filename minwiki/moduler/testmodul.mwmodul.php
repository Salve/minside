<?php

class mwmodul_testmodul extends mwmodul_base {

	private $mwmodulact;
	private $mwmodulvars;
	private $testmodout;
	
	public function __construct() {
	

		
	}
	
	public function gen_mwmodul($act, $vars){
		$this->mwmodulact = $act;
		$this->mwmodulvars = $vars;
		$this->testmodout = 'Dette er output fra testmodul!<br />';
		return $this->testmodout;
	
	}
	
}