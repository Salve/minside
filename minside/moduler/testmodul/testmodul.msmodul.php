<?php
if(!defined('MS_INC')) die();
class msmodul_testmodul implements msmodul{

	private $msmodulact;
	private $msmodulvars;
	private $testmodout;
	private $UserID;
	private $_adgangsNiva;
	
	public function __construct($UserID, $AdgangsNiva) {
	
		$this->UserID = $UserID;
		$this->_adgangsNiva = $AdgangsNiva;
	}
	
	public function gen_msmodul($act, $vars){
		$this->msmodulact = $act;
		$this->msmodulvars = $vars;
		$this->testmodout = 'Dette er output fra testmodul! UserId er: '. $this->UserID . ' act er: ' . $this->msmodulact . '<br />';
		return $this->testmodout;
	
	}
	
	public function registrer_meny(MenyitemCollection &$meny){
		$lvl = $this->_adgangsNiva;
	
		if ($lvl > MSAUTH_NONE) {
			$toppmeny = new Menyitem('Testmodul','&page=testmodul');
			
			if (isset($this->msmodulact)) { // Uncomment for å bare vise undermenyer når testmodul faktisk har blitt lastet
			
				if ($lvl == MSAUTH_ADMIN) {
					$adminmeny = new Menyitem('TestAdmin','&page=testmodul&act=admin');
					$adminmeny->addChild(new Menyitem('Slett alt!','&page=testmodul&act=adminsub1'));
					$adminmeny->addChild(new Menyitem('Spis snop!','&page=testmodul&act=adminsub2'));
					$toppmeny->addChild($adminmeny);
				}
				
				if ($lvl >= MSAUTH_2) {
					$toppmeny->addChild(new Menyitem('TestSub1','&page=testmodul&act=sub1'));
					$toppmeny->addChild(new Menyitem('TestSub2','&page=testmodul&act=sub2'));
				}
				
			}
		
			$meny->addItem($toppmeny);
		
		}
		
	}
	
}
