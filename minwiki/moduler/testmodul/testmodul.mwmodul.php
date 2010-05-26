<?php
if(!defined('MW_INC')) die();
class mwmodul_testmodul implements mwmodul{

	private $mwmodulact;
	private $mwmodulvars;
	private $testmodout;
	private $UserID;
	private $_adgangsNiva;
	
	public function __construct($UserID, $AdgangsNiva) {
	
		$this->UserID = $UserID;
		$this->_adgangsNiva = $AdgangsNiva;
	}
	
	public function gen_mwmodul($act, $vars){
		$this->mwmodulact = $act;
		$this->mwmodulvars = $vars;
		$this->testmodout = 'Dette er output fra testmodul! UserId er: '. $this->UserID . ' act er: ' . $this->mwmodulact . '<br />';
		return $this->testmodout;
	
	}
	
	public function registrer_meny(MenyitemCollection &$meny){
		$lvl = $this->_adgangsNiva;
	
		if ($lvl > MWAUTH_NONE) {
			$toppmeny = new Menyitem('Testmodul','&page=testmodul');
			
			if ($lvl == MWAUTH_ADMIN) {
				$adminmeny = new Menyitem('TestAdmin','&page=testmodul&act=admin');
				$adminmeny->addChild(new Menyitem('Slett alt!','&page=testmodul&act=adminsub1'));
				$adminmeny->addChild(new Menyitem('Spis snop!','&page=testmodul&act=adminsub2'));
				$toppmeny->addChild($adminmeny);
			}
			
			if ($lvl >= MWAUTH_2) {
				$toppmeny->addChild(new Menyitem('TestSub1','&page=testmodul&act=sub1'));
				$toppmeny->addChild(new Menyitem('TestSub2','&page=testmodul&act=sub2'));
			}
		
		$meny->addItem($toppmeny);
		
		}
		
	}
	
}
