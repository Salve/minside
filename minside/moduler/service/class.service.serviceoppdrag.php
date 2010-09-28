<?php
if(!defined('MS_INC')) die();

abstract class ServiceOppdrag {
    
    public $elements;
    
    public function __construct() {
        
    }
    
    public function setSaved($id) {
        $this->_id = $id;
        $this->_isSaved = true;
    }
    public function isSaved() {
        return (bool) $this->_isSaved;
    }
    
    abstract function genXhtml() {}
    
}