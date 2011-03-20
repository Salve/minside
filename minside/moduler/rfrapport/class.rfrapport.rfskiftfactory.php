<?php
if(!defined('MS_INC')) die();

class RFSkiftFactory extends SkiftFactory {

    public function getSkift($id) {
        global $msdb;
        
        $sql = "SELECT " . $this->dbPrefix . "_skift.skiftcreated, " . $this->dbPrefix . "_skift.userid, " . $this->dbPrefix 
            . "_skift.skiftclosed, " . $this->dbPrefix . "_skift.israpportert, "
            . $this->dbPrefix . "_skift.rapportid, internusers.wikiname FROM " . $this->dbPrefix . "_skift LEFT JOIN internusers ON " 
            . $this->dbPrefix . "_skift.userid = internusers.id WHERE skiftid = " . $msdb->quote($id) . ";";
        $data = $msdb->assoc($sql);
        
        if(is_array($data) && sizeof($data)) {
            $objSkift = new RFSkift($id, $data[0]['skiftcreated'], $data[0]['userid'], $data[0]['skiftclosed'], $data[0]['israpportert'], $data[0]['rapportid']);
            $objSkift->dbPrefix = $this->dbPrefix;
            $objSkift->setSkiftOwnerName($data[0]['wikiname']);
            $objSkift->setSkiftFactory($this);
            return $objSkift;
        } else {
            throw new Exception("Skift med id: $id finnes ikke i database");
        }
        
    }
    
}
