<?php
if(!defined('MS_INC')) die();

class FritekstElement extends OppdragElement {

    protected $_value;
    
    public function __construct($navn) {
        parent::setVisningsNavn($navn);
    }
    
    public function genInput(){
        return parent::getVisningsNavn() . ': <input type="text" name="" /><br />' . "\n";
    
    }
    public function genOutput() {
        return parent::getVisningsNavn();
    }
    
}