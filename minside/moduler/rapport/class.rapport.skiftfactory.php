<?php
if(!defined('MS_INC')) die();

class SkiftFactory {
    
    protected $dbPrefix;
    
    public function __construct($dbprefix) {
        $this->dbPrefix = $dbprefix;
    }
    
    public function getSkift($id) {
        global $msdb;
        
        $sql = "SELECT " . $this->dbPrefix . "_skift.skiftcreated, " . $this->dbPrefix . "_skift.userid, " . $this->dbPrefix . "_skift.skiftclosed, " . $this->dbPrefix . "_skift.israpportert, " . $this->dbPrefix . "_skift.rapportid, internusers.wikiname FROM " . $this->dbPrefix . "_skift LEFT JOIN internusers ON " . $this->dbPrefix . "_skift.userid = internusers.id WHERE skiftid = " . $msdb->quote($id) . ";";
        $data = $msdb->assoc($sql);
        
        if(is_array($data) && sizeof($data)) {
            $objSkift = new Skift($id, $data[0]['skiftcreated'], $data[0]['userid'], $data[0]['skiftclosed'], $data[0]['israpportert'], $data[0]['rapportid']);
            $objSkift->dbPrefix = $this->dbPrefix;
            $objSkift->setSkiftOwnerName($data[0]['wikiname']);
            $objSkift->setSkiftFactory($this);
            return $objSkift;
        } else {
            throw new Exception("Skift med id: $id finnes ikke i database");
        }
        
    }
    
    public function getRapport($rapportid) {
        global $msdb;
        
        $saferapportid = $msdb->quote($rapportid);
        
        $sql = "SELECT rapportid, createtime, rapportowner, templateid FROM " . $this->dbPrefix . "_rapport WHERE rapportid=$saferapportid LIMIT 1;";
        $data = $msdb->assoc($sql);
        
        if(is_array($data) && sizeof($data)) {
            $objRapport = new Rapport($data[0]['rapportowner'], $data[0]['rapportid'], $data[0]['createtime'], true, $data[0]['templateid'], null, $this->dbPrefix);
            return $objRapport;
        } else {
            throw new Exception("Rapport med id: $saferapportid finnes ikke i database");
        }
        
    }
    
    public function getRapporter($fromtime = null, $totime = null) {
        global $msdb;
        
        if ($fromtime && $totime) {
            $safefromtime = $msdb->quote($fromtime);
            $safetotime = $msdb->quote($totime);
            $where = " WHERE " . $this->dbPrefix . "_rapport.createtime >= $safefromtime AND " . $this->dbPrefix . "_rapport.createtime <= $safetotime";
        } elseif ($fromtime) {
            $safefromtime = $msdb->quote($fromtime);
            $where = " WHERE " . $this->dbPrefix . "_rapport.createtime >= $safefromtime";
        } elseif ($totime) {
            $safetotime = $msdb->quote($totime);
            $where = " WHERE " . $this->dbPrefix . "_rapport.createtime <= $safetotime";
        } else {
            $where = '';
        }
        
        $sql = "SELECT " . $this->dbPrefix . "_rapport.rapportid, " . $this->dbPrefix . "_rapport.createtime, " . $this->dbPrefix . "_rapport.rapportowner, " . $this->dbPrefix . "_rapport.templateid, internusers.wikiname FROM " . $this->dbPrefix . "_rapport LEFT JOIN internusers ON " . $this->dbPrefix . "_rapport.rapportowner = internusers.id" . $where . ";";
        
        
        $data = $msdb->assoc($sql);
        
        $col = new RapportCollection();
        
        if(is_array($data) && sizeof($data)) {
            foreach ($data as $datum) {
                $objRapport = new Rapport($datum['rapportowner'], $datum['rapportid'], $datum['createtime'], true, $datum['templateid'], $datum['wikiname'], $this->dbPrefix);
                $col->addItem($objRapport, $datum['rapportid']);
            }
        }
        
        return $col;
    
    }
    
    public function getRapporterByMonth($inputmonth, $inputyear = null) {
        
        if (!$inputyear) $inputyear = date('Y');
        
        $fromtime = mktime(0, 0, 0, $inputmonth, 1, $inputyear); // returnerer første dag i gitt måned
        $totime = mktime(23, 59, 59, $inputmonth + 1, 0, $inputyear); // returnerer siste dag i gitt måned (nullte dag i neste måned). måned 13 er ok input...
        
        $sqlfromtime = date('Y-m-d H:i:s', $fromtime);
        $sqltotime = date('Y-m-d H:i:s', $totime);
        
        
        return self::getRapporter($sqlfromtime, $sqltotime);
    
    }
    
    public function getNyligeRapporter() {
        global $msdb;

        $fromtime = time() - (24 * 60 * 60); // Definer hvor langt tilbake "nylig" er her (i sekunder).
        $sqlfromtime = date('Y-m-d H:i:s', $fromtime);
        $sqltotime = date('Y-m-d H:i:s');
        
        return self::getRapporter($sqlfromtime, $sqltotime);
    
    }
    
    public function getDataForRapport($rapportid) {
        global $msdb;
        
        $saferapportid = $msdb->quote($rapportid);
        $outputarray = array();
        
        $sql = "SELECT datatype, dataname, datavalue FROM " . $this->dbPrefix . "_rapportdata WHERE rapportid=$saferapportid;";
        $data = $msdb->assoc($sql);
    
        if(is_array($data) && sizeof($data)) {
            foreach ($data as $row) {
                $type = $row['datatype'];
                $name = $row['dataname'];
                $value = $row['datavalue'];
                $outputarray["$type"]["$name"] = $value;
            }
        }
        
        return $outputarray;
    
    }
    
    public function getTellerForSkift($tellerid, $skiftid) {
        global $msdb;
        $safeskiftid = $msdb->quote($skiftid);
        $safetellerid = $msdb->quote($tellerid);
        
        $sql = "SELECT " . $this->dbPrefix . "_teller.tellertype, " . $this->dbPrefix . "_teller.tellernavn, " . $this->dbPrefix . "_teller.tellerdesc, SUM(IF(" . $this->dbPrefix . "_tellerakt.skiftid=$safeskiftid," . $this->dbPrefix . "_tellerakt.verdi,0)) AS 'tellerverdi', " . $this->dbPrefix . "_teller.isactive FROM " . $this->dbPrefix . "_teller LEFT JOIN " . $this->dbPrefix . "_tellerakt ON " . $this->dbPrefix . "_teller.tellerid = " . $this->dbPrefix . "_tellerakt.tellerid WHERE " . $this->dbPrefix . "_teller.tellerid=$safetellerid GROUP BY " . $this->dbPrefix . "_teller.tellerid LIMIT 1;";
        
        $data = $msdb->assoc($sql);
        
        if(is_array($data) && sizeof($data)) {
            $objTeller = new Teller($tellerid, $skiftid, $data[0]['tellernavn'], $data[0]['tellerdesc'], $data[0]['tellertype'], $data[0]['tellerverdi'], $data[0]['isactive']);
            $objTeller->dbPrefix = $this->dbPrefix;
            return $objTeller;
        } else {
            throw new Exception("Klarte ikke å laste tellerid: $safetellerid for skift: $safeskiftid.");
        }
    
    
    }
    
    public function getTeller($tellerid) {
        global $msdb;
        $safetellerid = $msdb->quote($tellerid);
        
        $sql = "SELECT tellertype, tellernavn, tellerdesc, isactive, tellerorder FROM " . $this->dbPrefix . "_teller WHERE tellerid=$safetellerid LIMIT 1;";
        
        $data = $msdb->assoc($sql);
        
        if(is_array($data) && sizeof($data)) {
            $objTeller = new Teller($tellerid, 0, $data[0]['tellernavn'], $data[0]['tellerdesc'], $data[0]['tellertype'], 0, $data[0]['isactive']);
            $objTeller->setOrder($data[0]['tellerorder']);
            $objTeller->dbPrefix = $this->dbPrefix;
            return $objTeller;
        } else {
            throw new Exception("Klarte ikke å laste tellerid: $safetellerid");
        }
    
    
    }
    
    public function getSkiftForRapport($rapportid, &$col) {
        global $msdb;
        
        $saferapportid = $msdb->quote($rapportid);
        
        $sql = "SELECT skiftid FROM " . $this->dbPrefix . "_skift WHERE rapportid=$saferapportid;";
        $data = $msdb->assoc($sql);
        
        if(is_array($data) && sizeof($data)) {
            foreach ($data as $datum) {
                $objSkift = self::getSkift($datum['skiftid']);
                $col->addItem($objSkift);
            }
        }
    }
    
    public function getMuligeSkiftForRapport() {
        global $msdb;
        
        $sql = "SELECT skiftid FROM " . $this->dbPrefix . "_skift WHERE (skiftcreated > (now() - INTERVAL 48 HOUR)) AND (israpportert = 0)";
        $data = $msdb->assoc($sql);
        
        $col = new SkiftCollection();
        
        if(is_array($data) && sizeof($data)) {
            foreach ($data as $datum) {
                $objSkift = self::getSkift($datum['skiftid']);
                $col->addItem($objSkift);
            }
        }
        
        return $col;
        
    }

    public function getTellereForSkift($id, &$col) {
        global $msdb;
        $id = $msdb->quote($id);
        $sql = "SELECT " . $this->dbPrefix . "_teller.tellerid, " . $this->dbPrefix . "_teller.tellertype, " . $this->dbPrefix . "_teller.isactive, " . $this->dbPrefix . "_teller.tellernavn, " . $this->dbPrefix . "_teller.tellerdesc, SUM(IF(" . $this->dbPrefix . "_tellerakt.skiftid=$id," . $this->dbPrefix . "_tellerakt.verdi,0)) AS 'tellerverdi' FROM " . $this->dbPrefix . "_teller LEFT JOIN " . $this->dbPrefix . "_tellerakt ON " . $this->dbPrefix . "_teller.tellerid = " . $this->dbPrefix . "_tellerakt.tellerid GROUP BY " . $this->dbPrefix . "_teller.tellerid ORDER BY " . $this->dbPrefix . "_teller.tellerorder ASC;";
        
        $data = $msdb->assoc($sql);
        
        if(is_array($data) && sizeof($data)) {
            foreach($data as $datum) {
                $isactive = (bool) $datum['isactive'];
                $objTeller = new Teller($datum['tellerid'], $id, $datum['tellernavn'], $datum['tellerdesc'], $datum['tellertype'], $datum['tellerverdi'], $isactive);
                $objTeller->dbPrefix = $this->dbPrefix;
                $col->addItem($objTeller, $datum['tellernavn']);
            }
        }
    
    }
    
    public function getAlleTellere() {
        global $msdb;
        
        $col = new TellerCollection;
        
        $sql = "SELECT tellerid, tellertype, tellernavn, tellerdesc, isactive, tellerorder FROM " . $this->dbPrefix . "_teller ORDER BY tellerorder ASC;";
        $data = $msdb->assoc($sql);
        
        if(is_array($data) && sizeof($data)) {
            foreach($data as $datum) {
                $objTeller = new Teller($datum['tellerid'], 0, $datum['tellernavn'], $datum['tellerdesc'], $datum['tellertype'], 0, (bool) $datum['isactive']);
                $objTeller->setOrder($datum['tellerorder']);
                $objTeller->dbPrefix = $this->dbPrefix;
                $col->addItem($objTeller);
            }
        }
        
        return $col;
    
    }
    
    public function getNotat($notatid) {
        global $msdb;
        $safenotatid = $msdb->quote($notatid);
        
        $sql = "SELECT skiftid, isactive, notattype, notattekst, inrapport FROM " . $this->dbPrefix . "_notat WHERE notatid=$safenotatid;";
        
        $data = $msdb->assoc($sql);
        
        if(is_array($data) && sizeof($data)) {
            foreach($data as $datum) {        
                $rapportert = ($datum['inrapport'] == 1) ? true : false;
                $active = ($datum['isactive'] == 1) ? true : false;
                                
                $objNotat = new Notat($notatid, $datum['skiftid'], $datum['notattype'], $datum['notattekst'], true, $active, $rapportert);
                
                return $objNotat;
            }
        }    
    }
    
    public function getNotaterForSkift($skiftid, &$col) {
        global $msdb;
        $safeskiftid = $msdb->quote($skiftid);
        $sql = "SELECT notatid, isactive, notattype, notattekst, inrapport FROM " . $this->dbPrefix . "_notat WHERE skiftid=$safeskiftid;";
        
        $data = $msdb->assoc($sql);
        
        if(is_array($data) && sizeof($data)) {
            foreach($data as $datum) {
                $rapportert = ($datum['inrapport'] == 1) ? true : false;
                $active = ($datum['isactive'] == 1) ? true : false;

                $objNotat = new Notat($datum['notatid'], $skiftid, $datum['notattype'], $datum['notattekst'], true, $active, $rapportert);
                $col->addItem($objNotat, $datum['notatid']);
            }
        }
    }
    
    public function nyttSkiftForBruker($brukerid) {
         global $msdb;
        $safe_brukerid = $msdb->quote($brukerid);
        
        $result = $msdb->exec("INSERT INTO " . $this->dbPrefix 
            . "_skift (skiftcreated, israpportert, userid, skiftlastupdate) VALUES (now(), '0', " 
            . $safe_brukerid . ", now());");
            
        if ($result != 1) {
            throw new Exception('Klarte ikke å opprette skift!');
        } else {
            return true;
        }
    }
    
}
