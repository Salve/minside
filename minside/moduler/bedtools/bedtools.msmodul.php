<?php
if(!defined('MS_INC')) die();

class msmodul_bedtools implements msmodul{

	protected $_userID;
	protected $_adgang;
    protected $_act;
    protected $_vars;

	
	public function __construct($UserID, $adgang) {
		$this->_userID = $UserID;
		$this->_adgang = $adgang;
	}
	
	public function gen_msmodul($act, $vars){
		$this->_act = $act;
        $this->_vars = $vars;
        
        return 'test';
	}
	
	public function registrer_meny(MenyitemCollection &$meny){
		$lvl = $this->_adgang;
        $menynavn = 'Bedriftsverktøy';

        if(isset($this->_act)) {
            $menynavn = '<span class="selected">'.$menynavn.'</span>';
        }
        
		$toppmeny = new Menyitem($menynavn,'&amp;page=bedtools');

		if ($lvl > MSAUTH_NONE) { 
			$meny->addItem($toppmeny); 
		}
	}
}
