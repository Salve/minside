<?php
if(!defined('MS_INC')) die();
define('MS_SERVICE_LINK', MS_LINK . "&page=service");
require_once('class.service.oppdragcollection.php');
require_once('class.service.serviceoppdrag.php');
require_once('class.service.bboppdrag.php');
require_once('class.service.servicefactory.php');
require_once('class.service.servicegen.php');

class msmodul_service implements msmodul {

	static $dispatcher;
	
	private $_msmodulact;
	private $_msmodulvars;
	private $_userID;
	private $_adgangsNiva; // int som angir innlogget brukers rettigheter for denne modulen, se toppen av minside.php for mulige verdier.
	
	public function __construct($UserID, $AdgangsNiva) {
		$this->_userID = $UserID;
		$this->_adgangsNiva = $AdgangsNiva;
	}
	
	public function gen_msmodul($act, $vars){
		$this->_msmodulact = $act;
		$this->_msmodulvars = $vars;

		// Opprett ny dispatcher
		self::$dispatcher = new ActDispatcher($this, $this->_adgangsNiva);
		// Funksjon som definerer handles for act-values
		$this->_setHandlers(self::$dispatcher);
		
		// Dispatch $act, dispatcher returnerer output
		return self::$dispatcher->dispatch($act);

	}
	
	private function _setHandlers(&$dispatcher) {
		$dispatcher->addActHandler('show', 'gen_oppdrag_create', MSAUTH_1);
	}
	
	public function registrer_meny(MenyitemCollection &$meny) {
		$lvl = $this->_adgangsNiva;
	
		if ($lvl > MSAUTH_NONE) { 
			$toppmeny = new Menyitem('Serviceoppdrag','&page=service');
			if (isset($this->_msmodulact)) { // Modul er lastet/vises
				$toppmeny->addChild(new Menyitem('Mine oppdrag','&page=service&act=arkiv'));
				if ($lvl >= MSAUTH_3) {
					$toppmeny->addChild(new Menyitem('Oppdragstyper','&page=service&act=admoppdrag'));
				}
			}
			$meny->addItem($toppmeny);
		}	
	}
	
/********************************\
 *           HANDLERS           *
\********************************/

	public function gen_oppdrag_create() {
		
        return ServiceGen::genIngenOppdrag('<br /> TEST OUTPUT');
		
	}

}
