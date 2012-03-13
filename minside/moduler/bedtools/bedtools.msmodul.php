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
        $inndata = trim($_POST['varsel_data']);
        $inputskille = ($_POST['type_inputskille'] == 'NEWLINE') ? "\n" : $_POST['inputskille_custom']; 
        $filterdata = trim($_POST['filter_data']);
        $filterskille = ($_POST['type_filterskille'] == 'NEWLINE') ? "\n" : $_POST['filterskille_custom']; 
        
        if (!in_array($type, array('TLF', 'KID', 'CUSTOM'))) {
            throw new Exception('Ukjent handling, velg valideringstype øverst');
        }
        
        if(!strlen($inndata)) {
            throw new Exception('Ingen input gitt!');
        }
        
        $arInndata = explode($inputskille, $inndata);
        
        if(strlen(trim($filterdata))) {
            $arRawFilter = explode($filterskille, $filterdata);
            $arFilterData = $this->valider($type, $arRawFilter);
        } else {
            $arFilterData = array();
        }
        
        $pre_string = ($_POST['custom_prestring']) ?: '';
        $post_string = ($_POST['custom_poststring']) ?: '';
        $pre_element = ($_POST['custom_preelement']) ?: '';
        $post_element = ($_POST['custom_postelement']) ?: '';
        $joinstring = ($_POST['custom_joinstring']) ?: '';
        
        msg('Input er p&aring; ' . count($arInndata) . ' linjer.');
        $arValidData = $this->valider($type, $arInndata);
        
        if(!count($arValidData)) throw new Exception('Ingen gyldig data i input!');
        
        // Filtrer hvis nødvendig
        if(count($arFilterData)) {
            $count_prefilter = count($arValidData);
            $arValidData = array_diff($arValidData, $arFilterData);
            $count_postfilter = count($arValidData);
            msg('Filtrert ut ' . ($count_prefilter - $count_postfilter) . ' elementer, grunnet treff i filterdata');
        }
        // Fjern duplikater
        $count_predupes = count($arValidData);
        $arValidData = array_unique($arValidData);
        $count_postdupes = count($arValidData);
        msg('Fjernet ' . ($count_predupes - $count_postdupes) . ' element(er), grunnet duplikater');
        
        msg(count($arValidData) . ' elementer i output.', 1);
        
        switch($type) {
            case 'KID':
                $pre_string = ($pre_string) ?: 'Customer Profile"."Account Nr" IN (';
                $post_string = ($post_string) ?: ')';
                $pre_element = ($pre_element) ?: "'";
                $post_element = ($post_element) ?: "'";
                $joinstring = ($joinstring) ?: ', ';
                break;
            case 'TLF':
                $joinstring = ($joinstring) ?: ';';
                break;
        }

        if ($pre_element || $post_element) {
            $addPrePost = function(&$value, $key, $prepost){
                $value = $prepost[0] . $value . $prepost[1];
            };
            
            array_walk($arValidData, $addPrePost, array($pre_element, $post_element));
        }
        
        $output = implode($joinstring, $arValidData);
        
        return BedToolsGen::genVarslingOutput($pre_string . $output . $post_string);
    }
    
    protected function valider($type, array $input) {        
        if($type == "KID") {
            $fnValidator = 'validerKID';
            msg('Validerer input som kundenummer');
        }
        elseif($type == "TLF") {
            $fnValidator = 'validerTLF';
            msg('Validerer input som mobilnummer');
        }
        else {
            msg('Ingen validering av inndata');
            return $input;
        }
        
        $output = array();
        foreach($input as $element) {
            $validation_output = self::$fnValidator($element);
            if($validation_output === false) continue;
            $output[] = $validation_output;
        }
        return $output;
    }
    
    public static function validerKID($input) {
        $input = (string) trim($input);
        $len = strlen($input);
        if($len != 8 || !ctype_digit($input)) {
            msg("Ugyldig KID: '" . hsc($input) . "'! Hopper over dette.", -1);
            return false;
        }
        return $input;        
    }
    
    public static function validerTLF($input) {
        $input = explode(' ', trim($input)); // ignorerer eventuelle tegn etter mellomrom
        $tlf = (string) (double) trim($input[0]);
        if(strlen($tlf) == 10) {
            $tlf = substr($tlf, 2);
        }
        $forste_siffer = substr($tlf, 0, 1);
        if(strlen($tlf) != 8 || ($forste_siffer != 4 && $forste_siffer != 9)) {
            msg("Ugyldig mobilnummer: '" . hsc($tlf) . "'! Hopper over dette.", -1);
            return false;
        }
        
        return $tlf;
    }
}
