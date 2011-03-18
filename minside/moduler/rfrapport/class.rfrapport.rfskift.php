<?php
if(!defined('MS_INC')) die();

class RFSkift extends Skift {
    
    const TYPE_MORGEN = 1;
    const TYPE_ETTERMIDDAG = 2;
    const TYPE_KVELD = 3;
    
    protected $_skiftType;
    
    public function getSkiftType() {
        return $this->_skiftType;
    }
    
    public function setSkiftType($input) {
        $input = (int) $input;
        if ($input == self::TYPE_MORGEN
            || $input == self::TYPE_ETTERMIDDAG
            || $input == self::TYPE_KVELD) {
            $this->_skiftType = $input;
        } else {
            throw new Exception('Ugyldig skift-type gitt RFSkift.');
        }
    }


}
