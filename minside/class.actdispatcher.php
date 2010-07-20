<?php
if(!defined('MS_INC')) die();
class ActDispatcher {

	private $actlist;
	private $caller;
	private $adgang;
	
	function __construct(msmodul &$caller, $adgang){
		$this->caller = $caller;
		$this->adgang = (int) $adgang;
		$this->actlist = array();
	}
	
	function addAct($act, $handlers, $access, $param = null) {
		$arNewAct['adgang'] = $access;
		
		if (is_array($handler)) {
			foreach ($handlers as $handler) {
				$arNewAct['handlers'][] = (string) $handler;
			}
		} else {
			$arNewAct['handlers'][] = (string) $handlers;
		}
		
		if (is_array($param)) {
			$arNewAct['param'] = $param;
		} elseif (isset($param)) {
			$arNewAct['param'][] = $param;
		} else {
			$arNewAct['param'] = array();
		}
		
		$this->actlist["$act"] = $arNewAct;
	}
	
	function dispatch($inputact){
		if (!isset($this->actlist["$inputact"])) {
			throw new UnexpectedValueException("Ukjent handling: $inputact");
		}
		if ($this->actlist["$inputact"]['adgang'] > $this->adgang) {
			throw new AdgangsException("Bruker har ikke tilgang til handling: $inputact");
		}
		foreach ($this->actlist["$inputact"]['handlers'] as $handler) {
			if (!is_callable(array($this->caller, $handler))) {
				throw new BadMethodCallException("Kan ikke kalle metode: $handler");
			}
		}
		
		foreach ($this->actlist["$inputact"]['handlers'] as $handler) {
			$output .= call_user_func_array(array($this->caller, $handler),
				$this->actlist["$inputact"]['param']);
		}
		
		return $output;
		
	}

}
