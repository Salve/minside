<?php
if(!defined('MS_INC')) die();

class OppdragElementFactory {
    
    public static function getNewBBCollection() {
        $col = new ElementCollection();
        
        self::_addEmptyFritekstElement('Telefonnummer', $col);
        self::_addEmptyFritekstElement('Montør', $col);
        self::_addEmptyFritekstElement('Spisebolle', $col);
        
        return $col;
    }
    
    protected static function _addEmptyFritekstElement($navn, &$col) {
        $objElement = new FritekstElement($navn);
        $col->addItem($objElement, $navn);
    }
	
}
