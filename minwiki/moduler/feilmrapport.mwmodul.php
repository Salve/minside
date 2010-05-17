<?php

class mwmodul_feilmrapport extends mwmodul_base{

	private $mwmodulact;
	private $mwmodulvars;
	private $frapout;
	
	public function __construct() {
		
	}
	
	public function getMwmodulact(){
		return $this->mwmodulact;
	}
	
	public function gen_mwmodul($act, $vars){
		$this->mwmodulact = $act;
		$this->mwmodulvars = $vars;
		$this->frapout = 'Dette er output fra feilmrapport! act er: '. $this->mwmodulact .', vars er: ' . $this->mwmodulvars . '<br />';
		return $this->frapout;
	
	}
	
}