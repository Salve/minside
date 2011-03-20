<?php
if(!defined('MS_INC')) die();

class RapportDataInputDesimalTall extends RapportData {
    
    public function genFinal() {
        $datavalue = $this->getDataValue();
        
        $output = ($this->isValid()) ? str_replace('.', ',', $datavalue) : 'Ukjent/ugyldig verdi';
        
        return $output;
    }

    public function genEdit() {
        $dataname = $this->getDataName();
        $datavalue = ($this->isValid()) ? str_replace('.', ',', $this->getDataValue()) : '';
        
        $output = '<input type="text" maxlength="9" size="8" name="rappinn[desimaltall][' 
            . $dataname .  ']" value="' . $datavalue . '" />';
        
        return $output;
    }
    
    protected function validate() {
        $value = $this->getDataValue();
        
        $result1 = preg_match('/^[0-9]{0,5}([,.][0-9]{1,3})?$/uAD', $input, $matches);
        $result2 = ($value === (string)(float) $value);
        
        if ($result1 && $result2) {
            $this->errortext = '';
            $this->isvalid = true;
        } else {
            $output = null;
            $this->errortext = 'Må være gyldig desimaltall. 0-99999 pluss evt. desimaltegn og opp til 3 siffer.';
            $this->isvalid = false;
        }
        
        $this->needsvalidation = false;
    }
    
    public function getDataType() {
        return 'desimaltall';
    }
    
    public function setDataValue($input) {
        if(!$this->under_construction) $input = str_replace(',', '.', $input);
        parent::setDataValue($input);
    }
}
