<?php
if(!defined('MS_INC')) die();

abstract class OppdragElement {

    const MANGLER_KRITISK_DATA = 10;
    const MANGLER_DATA = 20;
    const AKSEPTERER_MER_DATA = 30;
    const HAR_DATA_KAN_ENDRES = 40;
    const HAR_ENDELIGE_DATA = 50;

    protected $_value;
    protected $_visningsNavn;
    protected $_isObligatorisk;
    protected $_type;
    
    public function getVisningsNavn() {
        return $this->_visningsNavn;
    }
    public function setVisningsNavn($input) {
        $this->_visningsNavn = $input;
    }
    
    public function getStatus() {
        // Bør overrides
        if (isset($this->_value)) return self::HAR_DATA_KAN_ENDRES;
        if ($this->_isObligatorisk) return self::MANGLER_KRITISK_DATA;
        return self::MANGLER_DATA;
    }
    
    public abstract function genInput();
    public abstract function genOutput();
    
}