<?php
if(!defined('MS_INC')) die();

class RapportDataInputLiteTall extends RapportData {
    
    public function genFinal() {
        $datavalue = $this->getDataValue();
        
        $output = ($this->isValid()) ? $datavalue : 'Ukjent/ugyldig verdi';
        
        return $output;
    }

    public function genEdit() {
        $dataname = $this->getDataName();
        $datavalue = ($this->isValid()) ? $this->getDataValue() : '';
        
        $output = '<input type="text" maxlength="3" size="2" name="rappinn[litetall][' 
            . $dataname .  ']" value="' . $datavalue . '" />'
        
        return $output;
    }
    
    protected function validate() {
        $value = $this->getDataValue();

        $result = (string)(int) $value;
        
        if ($value === $result && $result >= 0 && $result <= 999) {
            $this->errortext = '';
            $this->isvalid = true;
        } else {
            $output = null;
            $this->errortext = 'Må være gyldig tall, 1-3 siffer. Desimaler ikke tillatt.';
            $this->isvalid = false;
        }
        
        $this->needsvalidation = false;
    }
    
    public function getDataType() {
        return 'litetall';
    }
    
    public function setDataValue($input) {
        if(!$this->under_construction) $input = trim($input);
        parent::setDataValue($input);
    }
}
