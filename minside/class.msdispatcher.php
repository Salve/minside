<?php
if(!defined('MW_INC')) die();
class mwdispatcher {

	private $mw;
	private $act;
	private $vars;
	private $mwmoduler;
	private $adgang;
	
	function __construct($event_handle, &$mwmoduler, &$adgang, $act = NULL, $vars = array()){
		$this->mw = $event_handle;
		$this->vars = $vars;
		$this->act = $act;
		$this->mwmoduler =& $mwmoduler;
		$this->adgang =& $adgang;
	}
	
	function dispatch(){
		if (array_key_exists("$this->mw",$this->mwmoduler)){
			if ($this->adgang->sjekkAdgang($this->mw) > 0) {
				return $this->mwmoduler["$this->mw"]->gen_mwmodul($this->act, $this->vars);
			} else {
				return '<p>Dispatcher: Innlogget bruker har ikke adgang til å vise modulen "' . $this->mw . '".</p>';
			}
		} else {
			return '<p>Dispatcher: Klarte ikke å laste modulen "' . $this->mw . '".</p>';		
		}
	
	
	}

}
