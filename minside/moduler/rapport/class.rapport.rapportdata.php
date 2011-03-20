<?php
if(!defined('MS_INC')) die();

abstract class RapportData {
    protected $rapportid;
    protected $rapportdataid;

    protected $datatype; // bool, litetall, tpldata, osv.
    protected $dataname; // variabelnavn fra template
    protected $datavalue;
    
    protected $isvalid = true;
    protected $needsvalidation = true;
    protected $issaved;
    protected $hasunsavedchanges = false;
    protected $errortext = '';
    
    protected $dbprefix;
    
    public $under_construction = false;
    
    public function __construct($dbprefix, $rapportid, $issaved=false, $rapportdataid=null) {
        $this->dbprefix = $dbprefix;
        $rapportid = (int) $rapportid;
        if(!$rapportid > 0) throw new Exception('Ugyldig rapportid gitt rapportdataobjekt!');
        $this->rapportid = $rapportid;
        
        if ($issaved xor $rapportdataid) throw new Exception('Rapportdataid må settes når rapportdataobjekt angis som lagret.');
        $this->issaved = (bool) $issaved;
        $this->needsvalidation = (!$issaved); // krever ikke validation dersom data er fra db
        $this->rapportdataid = ($rapportdataid) ? (int) $rapportdataid : null;
    }
    
    abstract public function genFinal();
    abstract public function genEdit();
    abstract protected function validate();
    abstract public function getDataType();
    
    public function getRapportId() {
        return $this->rapportid;
    }
    public function getRapportDataId() {
        return $this->rapportdataid ?: false;
    }
    
    public function setDataValue($input) {
        $this->set_var($this->datavalue, $input);
    }
    public function getDataValue() {
        return $this->datavalue;
    }
    
    public function getDataName() {
        return $this->dataname;
    }
    public function setDataName($input) {
        $this->set_var($this->dataname, $input);
    }
    
    public function isSaved() {
        return (bool) $this->issaved;
    }
    protected function setSaved($rapportdataid) {
        $this->issaved = true;
        $this->rapportdataid = $rapportdataid;
    }
    
    public function needsValidation() {
        return (bool) $this->needsvalidation;
    }
    public function isValid() {
        if($this->_needsvaliation) {
            $this->validate();
        }
        
        return $this->isvalid;
    }
    public function getErrorText() {
        return $this->errortext;
    }
    
    protected function set_var(&$var, &$value) {
		
		if (!$this->under_construction && ($var != $value)) {
            
            if(MinSide::DEBUG) {
                $trace = debug_backtrace();
                $caller = $trace[1]['function'];
                msg('Endring av rapportdata fra funksjon: ' . $caller);
            }
            
            $this->hasunsavedchanges = true;
            $this->needsvalidation = true;
		}
		
		$var = $value;
		return true;
	}
    
    public function updateDb() {
        if (!$this->_hasunsavedchanges) return false;
        if ($this->needsvalidation) throw new Exception('RapportData må valideres før lagring!');
        
        global $msdb;
        
        $safedataname = $msdb->quote($this->getDataName());
        $safedatavalue = $msdb->quote($this->getDataValue());
        $safedatatype = $msdb->quote($this->getDataType());
        $saferapportid = $msdb->quote($this->getRapportId());
        
        $midsql = " SET
                    dataname=$safedataname,
                    datavalue=$safedatavalue,
                    datatype=$safedatatype,
                    rapportid=$saferapportid ";
                    
        if($this->isSaved()) {
            $saferapportdataid = $msdb->quote($this->getRapportDataId());
            $presql = 'UPDATE '. $this->dbprefix .'_rapportdata ';
            $postsql = " WHERE rapportdataid=$saferapportdataid LIMIT 1;";
        } else {
            $presql = 'INSERT INTO '. $this->dbprefix .'_rapportdata ';
            $postsql = '';
        }
        $sql = $presql . $midsql . $postsql;
        $res = $msdb->exec($sql);
        
        if(!$this->isSaved()) {
            $this->setSaved($msdb->getLastInsertId());
        }
        
        if ($res) {
            $this->_hasunsavedchanges = false;
            return true;
        } else {
            return false;
        }
    }
    
    public static function getRapportDataObject($type) {
        $type = 'RapportData'.$type;
        if(class_exists($type) && is_subclass_of($type, 'RapportData')) {
            return new $type();
        }
    }

}
