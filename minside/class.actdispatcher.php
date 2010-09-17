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
			$objAct->addHandler($handler, $adgang, $param);
		} else {
			$objAct = new MsAct($act);
			$objAct->addHandler($handler, $adgang, $param);
			$this->actcol->addItem($objAct, $act);
		}
		
		return true;
		
	}
	
	function dispatch($inputact){
		if (!$this->actcol->exists($inputact)) {
			throw new UnexpectedValueException("Ukjent handling: $inputact");
		}
		$objAct = $this->actcol->getItem($inputact);
        
        try {
            return $objAct->dispatch($this->caller, $this->adgang);
        } catch (AdgangsException $e) {
            return '<div class="mswarningbar"><strong>Ingen adgang</strong><br /><br />'. $e->getMessage() .'</div>';
        } catch (Exception $e) {
            return '<div class="mswarningbar"><strong>En feil har oppstått:</strong>' .
                    '<br /><br /><em>'. $e->getMessage() . '</em>' .
                    '<br /><br />Feil oppstod under behandling av: ' . $inputact . '</div>';
        }
		
	}

}
