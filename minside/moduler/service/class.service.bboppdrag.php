<?php
if(!defined('MS_INC')) die();

class BBOppdrag extends ServiceOppdrag {
        
    public function __construct() {
        parent::__construct();
    }
    
    public function setSaved($id) {
        $this->_id = $id;
        $this->_isSaved = true;
    }
    public function isSaved() {
        return (bool) $this->_isSaved;
    }
    
}