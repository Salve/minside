<?php
if(!defined('MS_INC')) die();
class MsAct {

	private $_act;
	private $_adgang; // Adgang som trengs for å kjøre
	private $_handlers;
	
	function __construct($act, $adgang){
		$this->_adgang = (int) $adgang;
		$this->_act = $act;
	}
	
	function addHandler($handler, $params = null) {
		$arNewAct['handler'] = (string) $handler;		
		
		if (is_array($params)) {
			$arNewAct['param'] = $params;
		} elseif (isset($params)) {
			$arNewAct['param'][] = $params;
		} else {
			$arNewAct['param'] = array();
		}
		
		$this->_handlers[] = $arNewAct;
	}
	
	function dispatch(msmodul &$caller, $brukeradgang){
		if ($this->_adgang > $brukeradgang) {
			throw new AdgangsException("Bruker har ikke tilgang til handling: $inputact");
		}
		foreach ($this->_handlers as $handler) {
			$output .= call_user_func_array(array($caller, $handler['handler']),
				$handler['param']);
		}
		
		return $output;
		
	}

}
