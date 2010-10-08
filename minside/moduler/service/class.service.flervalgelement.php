<?php
if(!defined('MS_INC')) die();

class FlervalgElement extends OppdragElement {
    
    public function __construct($navn) {
        $this->setVisningsNavn($navn);
    }
    
    public function genInput(){
        return parent::getVisningsNavn() . ': <input type="text" name="" /><br />' . "\n";
    
    }
    public function genOutput() {
        $output .= $this->getVisningsNavn();
        $output .= ': ' . $this->_value;
        return $output;
    }
    
}