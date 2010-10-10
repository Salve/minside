<?php
if(!defined('MS_INC')) die();

class NyhetTag {
    
    const TYPE_KATEGORI = 2;
    const TYPE_TAG = 3;
    
    protected $_type;
    protected $_id;
    protected $_navn;
    protected $_noselect;
    protected $_noview;
    
    protected $_issaved = false;
    protected $_hasunsavedchanges = false;
    
    public $under_construction = false;
    public $baseurl;
    
    public function __construct($type, $issaved=false, $id=null, $baseurl='') {
        if ($type != self::TYPE_KATEGORI && $type != self::TYPE_TAG) {
            throw new Exception('Ugyldig tag/kategori-type: ' . htmlspecialchars($type));
        }
        if ($issaved && ($id == false)) {
            throw new Exception('Logic error: ID må angis når nyhet er definert som lagret.');
        }
        
        $this->_issaved = $issaved;
        $this->_id = $id;
        $this->_type = (int) $type;
        $this->baseurl = $baseurl;
    }
    
    public function __destruct() {
        if ($this->_hasunsavedchanges) {
            $id = $this->getId();
            msg("NyhetTag $id destructed with unsaved changes", -1);
        }
    }
    
    public function getType() {
        return $this->_type;
    }
    
    protected function setSaved($id) {
        if ($this->isSaved()) {
            throw new Exception('Kan ikke lagre nyhettag som allerede er lagret');
        }
        $this->_id = (int) $id;
        $this->_issaved = true;
    }
    public function isSaved() {
        return (bool) $this->_issaved;
    }
    public function getId() {
        if (!$this->isSaved()) return false;
        return $this->_id;
    }
    
    public function setNavn($input) {
        $this->set_var($this->_navn, $input);
    }
    public function getNavn() {
        return $this->_navn;
    }
    
    public function setNoSelect($input) {
        $input = (bool) $input;
        $this->set_var($this->_noselect, $input);
    }
    public function noSelect() {
        return $this->_noselect;
    }
    
    public function setNoView($input) {
        $input = (bool) $input;
        $this->set_var($this->_noview, $input);
    }
    public function noView() {
        return $this->_noview;
    }
    
    protected function set_var(&$var, &$value) {
		
		if (!$this->under_construction && ($var != $value)) {
            
            $trace = debug_backtrace();
            $caller = $trace[1]['function'];
            msg('Endring av nyhet_tag fra funksjon: ' . $caller);
            
            $this->_hasunsavedchanges = true;
		}
		
		$var = $value;
		return true;
	}
    
    public function updateDb() {
        if (!$this->_hasunsavedchanges) return false;
        
        global $msdb;
        
        $safenavn = $msdb->quote($this->getNavn());
        $safetype = $msdb->quote($this->getType());
        $safeid = $msdb->quote($this->getId());
        $safenoview = ($this->noView()) ? '1' : '0';
        $safenoselect = ($this->noSelect()) ? '1' : '0';
        
        $midsql = " SET
                    tagnavn=$safenavn,
                    tagtype=$safetype,
                    no_view=$safenoview,
                    no_select=$safenoselect ";
                    
        if($this->isSaved()) {
            $presql = "UPDATE nyheter_tag SET ";
            $postsql = " WHERE tagid=$safeid LIMIT 1;";
        } else {
            $presql = "INSERT INTO nyheter_tag ";
            $postsql = '';
        }
        $sql = $presql . $midsql . $postsql;
        $res = $msdb->exec($sql);
        
        if(!$this->isSaved()) {
            $this->setSaved($msdb->getLastInsertId());
        }
        $this->_hasunsavedchanges = false;
        
        return (bool) $res;
    }
    
}
