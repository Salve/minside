<?php
if(!defined('MS_INC')) die();
class msmodul_testmodul implements msmodul{

	private $_msmodulact;
	private $_msmodulvars;
	private $_userID;
	private $_adgangsNiva;
	
	public function __construct($UserID, $AdgangsNiva) {
	
		$this->_userID = $UserID;
		$this->_adgangsNiva = $AdgangsNiva;
	}
	
	public function gen_msmodul($act, $vars){
		$this->_msmodulact = $act;
		$this->_msmodulvars = $vars;
		
        $output .= 'her er sidebar';
        
		return $output; 
	
	}
	
	public function registrer_meny(MenyitemCollection &$meny){ 
		$lvl = $this->_adgangsNiva;
	
		if ($lvl > MSAUTH_NONE) {
			$toppmeny = new Menyitem('Sidebar','&page=sidebar');
			$meny->addItem($toppmeny);
		}
	
	}
	
}
