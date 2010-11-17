<?php
if(!defined('MS_INC')) die();
class MsAct {

	private $_act;
	private $_handlers;
	
	function __construct($act){
		$this->_act = $act;
	}
	
	function addHandler($handler, $adgang, $params = null) {
		$arNewAct['handler'] = (string) $handler;		
		$arNewAct['adgang'] = (int) $adgang;		
		
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
		foreach ($this->_handlers as $handler) {
            if ($handler['adgang'] > $brukeradgang) {
                $inputact = htmlspecialchars($this->_act);
                throw new AdgangsException("Bruker har ikke tilgang til handling: $inputact");
            }
			$output .= call_user_func_array(array($caller, $handler['handler']),
				$handler['param']);
		}
		
		return $output;
		
	}

}
