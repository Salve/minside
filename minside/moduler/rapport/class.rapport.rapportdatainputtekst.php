<?php
if(!defined('MS_INC')) die();

class RapportDataInputTekst extends RapportData {
    
    public function genFinal() {
        $datavalue = $this->getDataValue();
        
        $output = ($this->isValid()) ? $datavalue : 'Ukjent/ugyldig verdi';
        
        return $output;
    }

    public function genEdit() {
        $dataname = $this->getDataName();
        $datavalue = ($this->isValid()) ? $this->getDataValue() : '';
        
        $output = '<input type="text" maxlength="250" name="rappinn[tekst][' . $dataname 
            . ']" value="' . $datavalue . '" />';
        
        return $output;
    }
    
    protected function validate() {
        $value = $this->getDataValue();

        if (strlen($value) > 250) {
            $this->errortext = 'Tekst er for lang, maksimalt 250 tegn.';
            $this->isvalid = false;
        } elseif(strlen($value) < 1) {
            $this->errortext = 'Felt kan ikke være tomt';
            $this->isvalid = false;
        } else {
            $this->errortext = '';
            $this->isvalid = true;
        }
        $this->needsvalidation = false;
    }
    
    public function getDataType() {
        return 'tekst';
    }
    
    public function setDataValue($input) {
        if(!$this->under_construction) $input = htmlspecialchars(trim($input));
        parent::setDataValue($input);
    }
}
