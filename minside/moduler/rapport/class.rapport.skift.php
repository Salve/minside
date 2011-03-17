<?php
if(!defined('MS_INC')) die();

class Skift {
    protected $_id;
    protected $_skiftCreatedTime;
    protected $_skiftClosedTime;
    protected $_skiftOwnerId;
    protected $_skiftOwnerName;
    protected $_skiftLastUpdate;
    protected $_skiftIsRapportert;
    protected $_skiftRapportId;
    public $checkedLastUpdate = false;
    
    public $tellere;
    public $notater;
    public $dbPrefix;
    
    public $skiftfactory;
    
    public function __construct($id, $createdtime, $ownerid, $closedtime = null, $rapportert = null, $rapportid = null) {
        $this->_id = $id;
        $this->_skiftCreatedTime = $createdtime;
        $this->_skiftOwnerId = $ownerid;
        $this->_skiftClosedTime = $closedtime;
        $this->_skiftIsRapportert = $rapportert;
        $this->_skiftRapportId = $rapportid;
        
        $this->tellere = new TellerCollection();
        $this->tellere->setLoadCallback('_loadTellere', $this);
        
        $this->notater = new NotatCollection();
        $this->notater->setLoadCallback('_loadNotater', $this);
    }
    
    public function getId() {
        return $this->_id;
    }
    
    public function setSkiftFactory(SkiftFactory $objSkiftFactory) {
        $this->skiftfactory = $objSkiftFactory;
    }
    
    public function getNumActiveTellere() {
        $tellercounter = 0;
        foreach ($this->tellere as $objTeller) {
            if ($objTeller->isActive()) $tellercounter++;
        }
        return $tellercounter;
    }
    
    public function getSkiftCreatedTime() {
        return $this->_skiftCreatedTime;
    }
    
    public function getSkiftAgeHours() {
        $skiftcreate = strtotime($this->getSkiftCreatedTime());
        $skiftage = time() - $skiftcreate;
        $skifthours = $skiftage / 60 / 60; // Sekund til timer
        return floor($skifthours);
    }
    
    public function getSkiftOwnerName() {
        return $this->_skiftOwnerName;
    }
    
    public function setSkiftOwnerName($ownername) {
        $this->_skiftOwnerName = (string) $ownername;
    }
    
    public function getSkiftClosedTime() {
        return $this->_skiftClosedTime;
    }
    
    public function getSkiftOwnerId() {
        return $this->_skiftOwnerId;
    }
    
    public function isClosed() {
        return (bool)($this->_skiftClosedTime != null);
    }
    
    public function isRapportert() {
        return (bool)($this->_skiftIsRapportert);
    }
    
    public function getSkiftRapportId() {
        return $this->_skiftRapportId;
    }
    
    public function __toString() {
        return 'SkiftID: ' . $this->_id . ', OwnerID: ' . $this->_skiftOwnerId . '.';
    }
    
    public function _loadTellere(Collection $col) {
        $arTellere = $this->skiftfactory->getTellereForSkift($this->_id, $col);
    }
    
    public function _loadNotater(Collection $col) {
        $arNotater = $this->skiftfactory->getNotaterForSkift($this->_id, $col);
    }
    
    public function getLastAct($antall_akt = 1) {
        global $msdb;
        
        $safeskiftid = $msdb->quote($this->getId());
        $safeantall = (int) $antall_akt;
        
        $sql = "SELECT 
                    akt.telleraktid AS id, 
                    akt.tidspunkt AS tidspunkt, 
                    akt.verdi AS verdi, 
                    teller.tellerdesc AS teller 
                FROM 
                        ". $this->dbPrefix ."_tellerakt AS akt 
                    LEFT JOIN 
                        ". $this->dbPrefix ."_teller AS teller 
                    ON 
                        akt.tellerid = teller.tellerid 
                WHERE 
                    akt.skiftid=$safeskiftid
                ORDER BY 
                    akt.tidspunkt 
                    DESC 
                LIMIT 
                    $safeantall;";
                    
        $data = $msdb->assoc($sql);
        
        if (!empty($data) && is_array($data)) {
            return $data;
        } else {
            return array();
        }
        
    }
    
    public function getSkiftLastUpdate() {
        if ($this->checkedLastUpdate) {
            return $this->_skiftLastUpdate;
        } else {
            global $msdb;
            $safeskiftid = $msdb->quote($this->_id);
            $result = $msdb->num("SELECT tidspunkt FROM ". $this->dbPrefix ."_tellerakt WHERE skiftid=$safeskiftid ORDER BY telleraktid DESC LIMIT 1;");
            $this->_skiftLastUpdate = $result[0][0];
            $this->checkedLastUpdate = true;
            return $this->getSkiftLastUpdate();
        }
    
    
    }
    
    public function closeSkift() {
        global $msdb;
        
        if ($this->isClosed()) return false;
                
        $safeskiftid = $msdb->quote($this->_id);
        $result = $msdb->exec("UPDATE ". $this->dbPrefix ."_skift SET skiftclosed=now() WHERE skiftid=$safeskiftid;");
        if ($result != 1) {
            return false;
        } else {
            return true;
        }
    
    }





}
