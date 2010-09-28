<?php
if(!defined('MS_INC')) die();

class FritekstElement extends OppdragElement {
    
    public function __construct($navn) {
        $this->setVisningsNavn($navn);
    }
    
    public function genInput(){
        return $this->getVisningsNavn() . ': <input type="text" name="" /><br />' . "\n";
    
    }
    public function genOutput() {
        return $this->getVisningsNavn();
    }
    
}