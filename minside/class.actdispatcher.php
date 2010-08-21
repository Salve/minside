<?php
if(!defined('MS_INC')) die();

require_once('class.msact.php');
require_once('class.actcollection.php');

class ActDispatcher {

	private $actcol;
	private $caller;
	private $adgang;
	
	function __construct(msmodul &$caller, $adgang){
		$this->caller = $caller;
		$this->adgang = (int) $adgang;
		$this->actcol = new ActCollection();
	}
	
	function addActHandler($act, $handler, $adgang, $param = null) {
		
		if ($this->actcol->exists($act)) {
			$objAct = $this->actcol->getItem($act);	
			$objAct->addHandler($handler, $param);
		} else {
			$objAct = new MsAct($act, $adgang);
			$objAct->addHandler($handler, $param);
			$this->actcol->addItem($objAct, $act);
		}
		
		return true;
		
	}
	
	function dispatch($inputact){
		if (!$this->actcol->exists($inputact)) {
			throw new UnexpectedValueException("Ukjent handling: $inputact");
		}
		$objAct = $this->actcol->getItem($inputact);
				
		return $objAct->dispatch($this->caller, $this->adgang);
		
	}

}
