<?php
if(!defined('MS_INC')) die();

class RFRapport extends Rapport {
    const TYPE_MORGEN = 1;
    const TYPE_ETTERMIDDAG = 2;
    const TYPE_KVELD = 3;
    
    protected $_skiftType;
    protected $_team;
    
    public function __construct($ownerid, $id=null, $createdtime=null, $issaved=false, $templateid=null, $ownername=null, $dbprefix) {
        $this->_id = $id;
        $this->dbPrefix = $dbprefix;
        $this->skiftfactory = new RFSkiftFactory($dbprefix);
        $this->_rapportCreatedTime = $createdtime;
        $this->_rapportOwnerId = $ownerid;
        $this->_rapportOwnerName = $ownername;
        if ($templateid) {
            $this->_rapportTemplateId = $templateid;
        } else {
            $tplfactory = new RapportTemplateFactory($this->dbPrefix); 
            $templateid = $tplfactory->getCurrentTplId();
            if (!$templateid === false) $this->_rapportTemplateId = $templateid;
        }
        $this->_isSaved = (bool) $issaved;
        
        $this->skift = new SkiftCollection();
        if ($issaved) $this->skift->setLoadCallback('_loadSkift', $this);
    }
    
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
            throw new Exception('Ugyldig skift-type gitt RFRapport.');
        }
    }
    
    public function getTeam() {
        return $this->_team ?: false;
    }
    public function setTeam(RapportTeam $objTeam) {
        $this->_team = $objTeam;
    }
}
