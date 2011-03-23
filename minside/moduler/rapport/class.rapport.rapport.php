<?php
if(!defined('MS_INC')) die();

class Rapport {
    protected $_id;
    protected $_rapportCreatedTime;
    protected $_rapportOwnerId;
    protected $_rapportOwnerName;
    protected $_rapportTemplateId;
    protected $_isSaved = false;
    
    public $dbPrefix;
    public $skiftfactory;
    public $skift;
    public $rapportdata;
    public $rapportnotater = array();
    protected $_rapportdataloaded = false;
    
    public function __construct($ownerid, $id=null, $createdtime=null, $issaved=false, $templateid=null, $ownername=null, $dbprefix) {
        $this->_id = $id;
        $this->dbPrefix = $dbprefix;
        $this->skiftfactory = new SkiftFactory($dbprefix);
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
    
    public function getId() {
        return $this->_id;
    }
    
    public function getRapportCreatedTime() {
        return $this->_rapportCreatedTime;
    }
    
    public function getRapportOwnerId() {
        return $this->_rapportOwnerId;
    }
    
    public function getRapportOwnerName() {
        if (isset($this->_rapportOwnerName)) {
            return $this->_rapportOwnerName;
        } else {
            return 'OwnerID: ' . $this->_rapportOwnerId;
        }
    }
    
    public function isSent() {
        return $this->rapportSent;
    }
    
    public function setSkiftCol(SkiftCollection &$skiftcol) {
        if ($skiftcol->length() > 0) {
            $this->skift = $skiftcol;
            return true;
        } else {
            return false;
        }
    }
    
    public function __toString() {
        return 'RapportID: ' . $this->_id . ', OwnerID: ' . $this->_rapportOwnerId . '.';
    }
    
    public function _loadSkift(Collection $col) {
        $arSkift = $this->skiftfactory->getSkiftForRapport($this->_id, $col);
    }
    
    public function dataLoaded() {
        return $this->_rapportdataloaded;
    }
    
    public function loadRapportData() {
        if(!$this->_isSaved) throw new Exception('Kan ikke laste data for rapport som ikke er lagret.');
        $data = $this->skiftfactory->getDataForRapport($this->_id);
        $colData = new RapportDataCollection();
        foreach ($data as $type => $datum) {
            foreach ($datum as $navn => $info) {
                if($type == 'selnotat') {
                    $this->rapportnotater[] = $info['verdi'];
                } else {
                    $class = RapportData::getRapportDataClass('input'.$type);
                    if($class === false) {
                        msg('Ukjent data-type: ' . hsc($type), -1);
                        break 1;
                    } else {
                        $objRapportData = new $class($this->dbPrefix, $this->_id, true, $info['id']);
                        $objRapportData->under_construction = true;
                        $objRapportData->setDataName($navn);
                        $objRapportData->setDataValue($info['verdi']);
                        $objRapportData->under_construction = false;
                        $colData->addItem($objRapportData, $navn);
                    }
                }
            }
        }
        $this->rapportdata = $colData;
        $this->_rapportdataloaded = true;
    }
    
    public function genRapport() {
        if (!$this->_isSaved) die('Funksjonen genRapport() kan kun vise rapporter fra database');
        if (!$this->dataLoaded()) $this->loadRapportData();
        return $this->_genRapport();
    
    }
    
    public function genRapportTemplate() {
        return $this->_genRapport();
    }
    
    public function genRapportTemplateErrors($validinput, $invalidinput) {
        if (!isset($validinput)) $validinput = array();
        if (!isset($invalidinput)) $invalidinput = array();
        
        return $this->_genRapport($validinput, $invalidinput, false);
    }
    
    public function _genRapport() {
        $tellertotaler = array();
        $uloggettotaler = array();
                    
        if (!($this->skift->length() > 0)) throw new Exception('Kan ikke vise rapport - ingen skift er lastet inn.');
        
        foreach ($this->skift as $objSkift) {
            $hiddenSkiftider .= '<input type="hidden" name="selskift[]" value="' . $objSkift->getId() . "\" />\n";
        
            foreach ($objSkift->notater as $objNotat) {
                if (!$objNotat->isActive()) continue;
                
                // Sjekk om notat er valgt - vis med eller uten checkbox
                $notatsaveoutput .= '<li>' . $objNotat . ' (' . $objSkift->getSkiftOwnerName() . ")</li>\n";
                    
                $notatoutput .= '<input type="checkbox" ' . $checked . 'name="rappinn[selnotat][]" value="' . $objNotat->getId() . '" /> '
                    . $objNotat . ' (' . $objSkift->getSkiftOwnerName() . ")<br />\n";
                
            }
            
            foreach ($objSkift->tellere as $objTeller) {
                if ($objTeller->getTellerType() == 'ULOGGET') {
                    $uloggettotaler[$objTeller->getTellerName()]['verdi'] += $objTeller->getTellerVerdi();
                    $uloggettotaler[$objTeller->getTellerName()]['desc'] = $objTeller->getTellerDesc();
                } else {
                    $tellertotaler[$objTeller->getTellerName()] += $objTeller->getTellerVerdi();                        
                }
            }
        }
        
        $uloggetoutput = '';
        foreach ($uloggettotaler as $tellernavn => $data) {
            if($data['verdi'] <= 0) continue;
            $uloggetoutput .= $data['desc'] . ': ' . $data['verdi'] . "<br />\n";
        }
        
        if (!$notatoutput) $notatoutput = 'Ingen notater.';
        if (!$notatsaveoutput) {
            $notatsaveoutput = 'Ingen notater.';
        } else {
            $notatsaveoutput = '<ul class="msul">' . $notatsaveoutput . '</ul>';
        }
        if (!$uloggetoutput) $uloggetoutput = 'Ingen uloggede samtaler.';
        
        $self = $this;
        $funcReplacer = function ($matches) use ($tellertotaler, $uloggetoutput, $self) {
            $type = $matches[1];
            $name = $matches[2];
            switch ($type) {
                case 'teller':
                    return $tellertotaler[$name] ?: '0';
                    break;
                case 'ulogget':
                    return $uloggetoutput;
                    break;
                case 'notater':
                    return 'notater SALVE SALVE her';
                    break;
                default:
                    if($self->rapportdata->exists($name)) {
                        $objRapportData = $self->rapportdata->getItem($name);
                        return $objRapportData->genFinal();
                    } elseif ($class = RapportData::getRapportDataClass($type)) {
                        $objRapportData = new $class($this->dbPrefix, $this->_id);
                        $objRapportData->setDataName($name);
                        return $objRapportData->genEdit();
                    } else {
                        return '<strong>Ukjent/manglende data<strong>';
                    }
            }
        };
             
        $tplfactory = new RapportTemplateFactory($this->dbPrefix);
        $tmpOutput = $tplfactory->getRawTemplate($this->_rapportTemplateId);
        
        $tmpOutput = preg_replace_callback('/\[\[([A-Za-z]+):?([A-Za-z]+)?\]\]/u', $funcReplacer, $tmpOutput);
        
        $output .= '<div class="rapporttpl">' . $tmpOutput . '</div><div class="msclearer"></div>';
        
        return $output;
    }

    
    public function lagreRapport($validinput) {
        global $INFO;
        global $msdb;
        if ($this->_isSaved) throw new Exception('Denne rapporten er allerede lagret');
        if (!isset($this->_rapportOwnerId)) throw new Exception('Rapport-eier ikke angitt');
        if (!is_array($validinput)) throw new Exception('Ingen data gitt');
        if (!($this->skift->length() > 0)) throw new Exception('Ingen skift lastet i rapportobjekt');
        if (!isset($this->_rapportTemplateId)) throw new Exception('Intet aktivt rapport-template');
        
        $skiftcol = $this->skift;
        
        $this->_rapportCreatedTime = date("Y-m-d H:i:s");
        $saferapportcreated = $msdb->quote($this->_rapportCreatedTime);
        $saferapportowner = $msdb->quote($this->_rapportOwnerId);
        $safetemplateid = $msdb->quote($this->_rapportTemplateId);
        
        $sql = "INSERT INTO ". $this->dbPrefix ."_rapport (createtime, rapportowner, templateid) VALUES ($saferapportcreated, $saferapportowner, $safetemplateid);";
        $msdb->exec($sql);
        $this->_id = $msdb->getLastInsertId();
        $saferapportid = $msdb->quote($this->_id);
        
        $arSkift = array();
        foreach ($skiftcol as $objSkift) {
            $arSkift[] = $objSkift->getId();
        }
        $skiftidlist = implode(',', $arSkift);
        $sql = "UPDATE ". $this->dbPrefix ."_skift SET israpportert=1, rapportid=$saferapportid WHERE skiftid IN ($skiftidlist);";
        $msdb->exec($sql);
        
        // Skittent hack, disse må defineres to plasser atm :\    
         $validinput['tpldata']['fulltnavn'] = $INFO['userinfo']['name'];
         $validinput['tpldata']['datotid'] = date('dmy \&\n\d\a\s\h\; H:i');
            
                
        foreach ($validinput as $inputtype => $arValues)  {
            $safeinputtype = $msdb->quote($inputtype);
            foreach ($arValues as $inputnavn => $inputvalue) {
                $safeinputnavn = $msdb->quote($inputnavn);
                $safeinputvalue = $msdb->quote($inputvalue);
                $sql = "INSERT INTO ". $this->dbPrefix ."_rapportdata (rapportid, dataname, datavalue, datatype) VALUES ($saferapportid, $safeinputnavn, $safeinputvalue, $safeinputtype);";
                $msdb->exec($sql);
            }
        }
        
        $this->_isSaved = true;
        
    }

    public function genMailForm($baseurl) {
        global $msdb;
        
        if (!$this->_isSaved) return false;
        
        $sql = "
            SELECT 
                wikifullname, 
                wikiepost 
            FROM 
                internusers 
            WHERE 
                    INSTR(`wikigroups`, 'feilm') 
                OR 
                    INSTR(`wikigroups`, 'admin')
            ORDER BY
                wikifullname
                ASC
            ;";
        $data = $msdb->assoc($sql);
        
        $arEpost = array();
        $size = count($data);
        $size = ($size > 20) ? 20 : $size;
        
        if(is_array($data) && $size) {
            foreach($data as $datum) {
                $selectoptions .= '<option value="' . $datum['wikiepost'] . '">' . $datum['wikifullname'] . '</option>' . "\n";
            }
        }
        
        
        $output .= '<span class="subactheader">Send rapport:</span><br /><br />';
        $output .= 'Hold inne CTRL-knappen for å velge flere personer.<br />';
        $output .= '<form name="mailrapport" action="' . $baseurl . '" method="POST">' . "\n";
        $output .= '<input type="hidden" name="act" value="mailrapport" />' . "\n";
        $output .= '<input type="hidden" name="rapportid" value="' . $this->_id . '" />' . "\n";
        $output .= '<select multiple size="'. $size .'" name="mailmottakere[]" />' . "\n";
        $output .= $selectoptions;
        $output .= '</select><br /><br />' . "\n";
        $output .= '<input type="submit" class="msbutton" name="sendmail" value="Send rapport per e-post" />' . "\n";
        $output .= '</form>' . "\n\n";
        
        return $output;
        
    }
    
    public function estimateSkiftType() {
        $rapporttime = date('G', strtotime($this->_rapportCreatedTime)); // 0-23
        if ($rapporttime >= 6 && $rapporttime < 10) {
            $skifttype = 'N';
        } elseif ($rapporttime >= 14 && $rapporttime < 18) {
            $skifttype = 'D';
        } elseif ($rapporttime >= 22) {
            $skifttype = 'E';
        } else {
            $skifttype = 'U';
        }
        
        return $skifttype;    
    }
}
