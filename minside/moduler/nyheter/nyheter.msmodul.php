<?php
if(!defined('MS_INC')) die();
class msmodul_nyheter implements msmodul{

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
		
		$output .= 'Nyheter her! UserId er: '. $this->_userID . ' act er: ' . $this->_msmodulact . '<br />';

		return $output;
	
	}
	
	public function registrer_meny(MenyitemCollection &$meny) {
		$lvl = $this->_adgangsNiva;
	
		if ($lvl > MSAUTH_NONE) { 
			$toppmeny = new Menyitem('Nyheter','&page=nyheter');
			/*if (isset($this->_msmodulact)) { // Modul er lastet/vises
				if ($lvl == MSAUTH_ADMIN) {
					$toppmeny->addChild(new Menyitem('Nyhetsadmin','&page=nyheter&act=admin'));
				}
			}*/
			$meny->addItem($toppmeny);
		}
			
	}
	
}
