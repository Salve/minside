<?php

class mwdispatcher {

	private $mw;
	private $act;
	private $vars;
	private $mwmoduler;
	
	function __construct($event_handle, &$mwmoduler, $act = NULL, $vars = array()){
		$this->mw = $event_handle;
		$this->vars = $vars;
		$this->act = $act;
		$this->mwmoduler =& $mwmoduler;
	}
	
	function dispatch(){
		if (array_key_exists("$this->mw",$this->mwmoduler)){
			$output = $this->mwmoduler["$this->mw"]->gen_mwmodul($this->act, $this->vars);
			return $output;
		} else {
			return 'Klarte ikke å laste modulen "' . $this->mw . '".';		
		}
	
	
	}



















}