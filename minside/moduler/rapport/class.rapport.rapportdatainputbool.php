<?php
if(!defined('MS_INC')) die();

class RapportDataInputBool extends RapportData {
    
    static $validValues = array (1, 0, '1', '0');
    static $trueValues = array (1, '1');
    static $falseValues = array (0, '0');
    
    public function genFinal() {
        $datavalue = $this->getDataValue();
        
        if(in_array($datavalue, self::$trueValues, true)) {
            $output = 'Ja';
        }
        elseif (in_array($datavalue, self::$falseValues, true)) {
            $output = 'Nei';
        }
        else {
            $output = 'Ukjent verdi';
        }
        
        return $output;
    }
    
    
    public function genEdit() {
        $dataname = $this->getDataName();
        $datavalue = $this->getDataValue();
        
        if(in_array($datavalue, self::$trueValues, true)) {
            $output = '<select name="rappinn[bool][' . $dataname . ']">
                <option value="NOSEL">Velg:</option>
                <option value="1" selected="selected">Ja</option>
                <option value="0">Nei</option>
            </select>';
        }
        elseif (in_array($datavalue, self::$falseValues, true)) {
            $output = '<select name="rappinn[bool][' . $dataname . ']">
                <option value="NOSEL">Velg:</option>
                <option value="1">Ja</option>
                <option value="0" selected="selected">Nei</option>
            </select>';
        }
        else {
            $output = '<select name="rappinn[bool][' . $dataname . ']">
                <option value="NOSEL" selected="selected">Velg:</option>
                <option value="1">Ja</option>
                <option value="0">Nei</option>
            </select>';
        }
        
        return $output;
    }
    
    protected function validate() {
        $value = $this->getDataValue();
        if (in_array($value, self::$validValues, true)) {
            $this->isvalid = true;
            $this->errortext = '';
        } else {
            $this->isvalid = false;
            $this->errortext = 'Velg ja eller nei.';
        }
        $this->needsvalidation = false;
    }
    
    public function getDataType() {
        return 'bool';
    }
}
