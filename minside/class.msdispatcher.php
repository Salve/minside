<?php
if(!defined('MS_INC')) die();
class msdispatcher {

	private $ms;
	private $act;
	private $vars;
	private $msmoduler;
	private $adgang;
	
	function __construct($event_handle, &$msmoduler, &$adgang, $act = NULL, $vars = array()){
		$this->ms = $event_handle;
		$this->vars = $vars;
		$this->act = $act;
		$this->msmoduler =& $msmoduler;
		$this->adgang =& $adgang;
	}
	
	function dispatch(){
		if (array_key_exists("$this->ms",$this->msmoduler)){
			if ($this->adgang->sjekkAdgang($this->ms) > 0) {
				return $this->msmoduler["$this->ms"]->gen_msmodul($this->act, $this->vars);
			} else {// Sjekker om 'stille' variabel er satt i modulvisning, og viser ingen output dersom denne er satt, viser feilmelding dersom den ikke er satt.
			    if (!array_search('stille', $this->vars, true)) throw new AdgangsException('Dispatcher: Innlogget bruker har ikke adgang til å vise modulen "' . $this->ms . '".');
			}
		} else {
			return '<p>Dispatcher: Klarte ikke å laste modulen "' . $this->ms . '".</p>';		
		}
	
	
	}

}
