<?php
if(!defined('MS_INC')) die();
define('MS_BEDTOOLS_LINK', MS_LINK . "&amp;page=bedtools");
require_once('class.bedtools.bedtoolsgen.php');

class msmodul_bedtools implements msmodul{

	protected $_userID;
	protected $_adgang;
    protected $_act;
    protected $_vars;
    
    protected $dispatcher;

	
	public function __construct($UserID, $adgang) {
		$this->_userID = $UserID;
		$this->_adgang = $adgang;
	}
	
	public function gen_msmodul($act, $vars){
		$this->_act = $act;
        $this->_vars = $vars;
        
        // Opprett ny dispatcher
		$this->dispatcher = new ActDispatcher($this, $this->_adgang);
		// Funksjon som definerer handles for act-values
		$this->_setHandlers($this->dispatcher);
		
		// Dispatch $act, dispatcher returnerer output
		return $this->dispatcher->dispatch($act);
	}
    
    private function _setHandlers(&$dispatcher) {
        $dispatcher->addActHandler('show', 'gen_forside', MSAUTH_1);
        $dispatcher->addActHandler('genvarsling', 'gen_varsling', MSAUTH_1);
    }
	
	public function registrer_meny(MenyitemCollection &$meny){
		$lvl = $this->_adgang;
        $menynavn = 'Bedriftsverkt&oslash;y';

        if(isset($this->_act)) {
            $menynavn = '<span class="selected">'.$menynavn.'</span>';
        }
        
		$toppmeny = new Menyitem($menynavn,'&amp;page=bedtools');

		if ($lvl > MSAUTH_NONE) { 
			$meny->addItem($toppmeny); 
		}
	}
    
    // HANDLERS
    
    public function gen_forside() {
        return BedToolsGen::genForside();
    }
    
    public function gen_varsling() {
        $type = $_POST['type_input'];
        $data = trim($_POST['varsel_data']);
        
        if ($type != "KID" && $type != "TLF") {
            throw new Exception('Ukjent handling, velg kundenummer eller telefonnummer');
        }
        
        if(!strlen($data)) {
            throw new Exception('Ingen input gitt!');
        }
        
        if ($type == "KID") {
            return $this->genKID($data);
        } elseif ($type == "TLF") {
            return $this->genTLF($data);
        }
                
    }
    
    protected function genKID($inn_data) {
        $data = explode("\n", $inn_data);
        $ut_data = array();
        $antall_blanke = 0;
        msg('Input er p&aring; ' . count($data) . ' linjer.');
        foreach($data as $datum) {
            $kid = (string) trim($datum);
            $len = strlen($kid);
            if($len != 8 || !ctype_digit($kid)) {
                if(empty($kid)) {
                    $antall_blanke += 1;
                    continue;
                }
                msg("Ugyldig KID: " . hsc($kid) . "! Hopper over dette.", -1);
                continue;
            }
            
            // Gyldig KID
            $ut_data[] = '\'' . $kid . '\'';
        }
        msg($antall_blanke . ' blanke linjer ignorert.');
        msg('Sp&oslash;rring med ' . count($ut_data) . ' kundenummer generert!', 1);
        $kid_streng = implode(', ', $ut_data);
        $output = '"Customer Profile"."Account Nr" IN (' . $kid_streng . ')'; 
        
        return '<br /><br /><br />' . $output;
    }
    
    protected function genTLF($inn_data) {
        $data = explode("\n", $inn_data);
        $ut_data = array();
        $antall_blanke = 0;
        msg('Input er p&aring; ' . count($data) . ' linjer.');
        foreach($data as $datum) {
            $tlf = (string) (double) trim($datum);
            if(strlen($tlf) == 10) {
                $tlf = substr($tlf, 2);
            }
            $forste_siffer = substr($tlf, 0, 1);
            if(strlen($tlf) != 8 || ($forste_siffer != 4 && $forste_siffer != 9)) {
                if($datum == 0) {
                    $antall_blanke += 1;
                    continue;
                }
                msg("Ugyldig mobilnummer: " . hsc($tlf) . "! Hopper over dette.", -1);
                continue;
            }
            
            // Gyldig TLF
            $ut_data[] = $tlf;
        }
        sort($ut_data, SORT_NUMERIC);
        msg($antall_blanke . ' blanke linjer ignorert.');
        msg('Liste med ' . count($ut_data) . ' mobilnummer generert!', 1);
        $tlf_streng = implode(';', $ut_data);
        
        return '<br /><br /><br />' . $tlf_streng;
        
    }
}
