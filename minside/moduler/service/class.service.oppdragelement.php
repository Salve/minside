<?php
if(!defined('MS_INC')) die();

abstract class OppdragElement {
        
    private $_visningsNavn;
    private $_isObligatorisk;
    private $_type;
    
    public function getVisningsNavn() {
        return $this->_visningsNavn;
    }
    public function setVisningsNavn($input) {
        $this->_visningsNavn = $input;
    }
    
    public abstract function genInput();
    public abstract function genOutput();
    
}