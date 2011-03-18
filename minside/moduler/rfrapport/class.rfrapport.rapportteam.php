<?php
if(!defined('MS_INC')) die();

class RapportTeam {
    
    protected $_id;
    protected $_navn;
    protected $_isdeleted;
    protected $_dbprefix;
    
    public $members;
    public $under_construction = false;
    
    protected $_issaved = false;
    protected $_hasunsavedchanges = false;
    
    
    public function __construct($dbprefix, $issaved=false, $id=null) {
        if ($issaved xor $id) {
            throw new Exception('Logic error: ID må angis når team er definert som lagret.');
        }
        
        $this->_issaved = $issaved;
        $this->_id = $id;
        $this->_dbprefix = $dbprefix;
        
        $this->members = new BrukerCollection();
        $this->members->setLoadCallback('loadMembers', $this);

    }
    
    public function __destruct() {
        if ($this->_hasunsavedchanges) {
            $id = $this->getId();
            if(MinSide::DEBUG) msg("Rapport-Team $id destructed with unsaved changes", -1);
        }
    }
    
    protected function setSaved($id) {
        if ($this->isSaved()) {
            throw new Exception('Kan ikke lagre rapport-team som allerede er lagret');
        }
        $this->_id = (int) $id;
        $this->_issaved = true;
    }
    public function isSaved() {
        return (bool) $this->_issaved;
    }
    public function getId() {
        return $this->_id;
    }
    
    public function setNavn($input) {
        $this->set_var($this->_navn, $input);
    }
    public function getNavn() {
        return $this->_navn;
    }
    
    public function setIsDeleted($input) {
        $input = (bool) $input;
        $this->set_var($this->_isdeleted, $input);
    }
    public function isDeleted() {
        return (bool) $this->_isdeleted;
    }
    
    protected function set_var(&$var, &$value) {
		
		if (!$this->under_construction && ($var != $value)) {
            
            if(MinSide::DEBUG) {
                $trace = debug_backtrace();
                $caller = $trace[1]['function'];
                msg('Endring av rapportteam fra funksjon: ' . $caller);
            }
            
            $this->_hasunsavedchanges = true;
		}
		
		$var = $value;
		return true;
	}
    
    public function updateDb() {
        if (!$this->_hasunsavedchanges) return false;
        
        global $msdb;
        
        $safenavn = $msdb->quote($this->getNavn());
        $safeid = $msdb->quote($this->getId());
        $safeactive = ($this->isDeleted()) ? '1' : '0';
        
        $midsql = " SET
                    teamnavn=$safenavn,
                    isactive=$safeactive ";
                    
        if($this->isSaved()) {
            $presql = 'UPDATE '. $this->_dbprefix .'_team ';
            $postsql = " WHERE teamid=$safeid LIMIT 1;";
        } else {
            $presql = 'INSERT INTO '. $this->_dbprefix .'_team ';
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
    
    public function slett() {
        if ($this->isDeleted()) throw new Exception('Kan ikke slette team som allerede er slettet.');
        if ($this->under_construction) throw new Exception('Kan ikke slette team som er under construction');
        
        $this->setIsDeleted(true);
        return $this->updateDb();
    }
    
    public static function compare_alpha_navn(RapportTeam $a, RapportTeam $b) {
        $navnA = strtoupper($a->getNavn());
        $navnB = strtoupper($b->getNavn());
        if($navnA == $navnB) return 0;
        return ($navnA > $navnB) ? +1 : -1;
    }
    
    public function add_members($arMembers) {
        global $msdb;
        $colBrukere = Bruker::GetAlleBrukere();
        $gyldige_ider = array();
        foreach($colBrukere as $objBruker) {
            $gyldige_ider[] = $objBruker->getId();
        }
        
        $values = array();
        foreach($arMembers as $brukerid) {
            $brukerid = (int) $brukerid;
            if(!in_array($brukerid, $gyldige_ider)) throw new Exception('Ugyldig brukerid: ' . $brukerid);
            $values[] = "('".$this->_id."', '".$brukerid."')";
        }
        if(!count($values)) throw new Exception('Ingen brukere valgt');
        
        $sql = 'INSERT INTO '.$this->_dbprefix.'_team_x_bruker (teamid, brukerid) VALUES '
            . implode(', ', $values);
            
        return $msdb->exec($sql);
    }
    
    public function remove_members($arMembers) {
        global $msdb;
        
        $gyldige_ider = array();
        foreach($this->members as $objBruker) {
            $gyldige_ider[] = $objBruker->getId();
        }
        
        $values = array();
        foreach($arMembers as $brukerid) {
            $brukerid = (int) $brukerid;
            if(!in_array($brukerid, $gyldige_ider)) throw new Exception('Ugyldig brukerid: ' . $brukerid);
            $values[] = $msdb->quote($brukerid);
        }
        if(!count($values)) throw new Exception('Ingen brukere valgt');
        
        $sql = 'DELETE FROM '.$this->_dbprefix.'_team_x_bruker
                WHERE teamid=\''.$this->_id.'\'
                AND brukerid IN('.implode(', ', $values).')
                ;';
            
        return $msdb->exec($sql);
    }
    
    public function loadMembers(BrukerCollection $col) {
        global $msdb;

        if(!$this->isSaved() && ($this->_id !== 0)) return;
        $safe_teamid = $msdb->quote($this->_id);
        $sql = 'SELECT
                    bruker.id AS brukerid,
                    bruker.wikiname AS navn,
                    bruker.wikifullname AS fullnavn,
                    bruker.wikiepost AS epost,
                    bruker.wikigroups AS groups,
                    bruker.createtime AS createtime,
                    bruker.isactive AS isactive
                FROM
                        '.$this->_dbprefix.'_team_x_bruker AS team
                    LEFT JOIN
                        internusers AS bruker
                    ON team.brukerid = bruker.id
                WHERE
                        team.teamid = '.$safe_teamid.'
                    AND
                        bruker.isactive = 1
                ORDER BY
                    navn ASC;';
        
        $data = $msdb->assoc($sql);
        
        if(is_array($data) && sizeof($data)) {
            foreach($data as $datum) {
                $objBruker = new Bruker(
                    $datum['brukerid'], $datum['navn'], $datum['fullnavn'], 
                    $datum['groups'], $datum['epost'], $datum['isactive'], $datum['createtime']);
                $col->addItem($objBruker, $datum['navn']);
            }
        }
    }
    
    public static function getAlleTeams($dbprefix, $getFelles=false) {
        global $msdb;
        $teamcollection = new RapportTeamCollection();
        if($getFelles) {
            $teamcollection->addItem(self::getFellesTeam($dbprefix));
        }
        
        $sql = "
            SELECT
                teamid,
                teamnavn,
                isactive 
            FROM 
                ".$dbprefix."_team
        ;";
        $data = $msdb->assoc($sql);
        
        if(is_array($data) && sizeof($data)) {
            foreach($data as $datum) {
                $objTeam = new RapportTeam($dbprefix, true, $datum['teamid']);
                
                $objTeam->under_construction = true;
                $objTeam->setNavn($datum['teamnavn']);
                $objTeam->setIsDeleted($datum['isactive']);
                $objTeam->under_construction = false;
                
                $teamcollection->addItem($objTeam, $datum['teamid']);
            }
        }
        
        return $teamcollection;
    }
    
    public static function getTeam($dbprefix, $teamid) {
        global $msdb;
        $safe_teamid = $msdb->quote($teamid);
        
        $sql = "
            SELECT
                teamid,
                teamnavn,
                isactive 
            FROM 
                ".$dbprefix."_team
            WHERE
                teamid = $safe_teamid
            LIMIT 1
        ;";
        $data = $msdb->assoc($sql);
        
        if(is_array($data) && sizeof($data[0])) {
            $objTeam = new RapportTeam($dbprefix, true, $data[0]['teamid']);
            
            $objTeam->under_construction = true;
            $objTeam->setNavn($data[0]['teamnavn']);
            $objTeam->setIsDeleted($data[0]['isactive']);
            $objTeam->under_construction = false;
            
            return $objTeam;
        } else {
            throw new Exception('Klarte ikke å hente team med id: ' . $teamid);
        }
    }
    
    public static function getFellesTeam($dbprefix) {
        $objTeam = new RapportTeam($dbprefix, false, 0);
        
        $objTeam->under_construction = true; // Lar denne stå på så ikke endringer blir forsøkt lagret uten videre
        $objTeam->setNavn('daglig oppsummering');
        $objTeam->setIsDeleted(false);
        
        return $objTeam;
    }

}
