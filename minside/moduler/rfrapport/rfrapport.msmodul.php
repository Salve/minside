<?php
if(!defined('MS_INC')) die();
require_once(DOKU_PLUGIN.'/minside/minside/moduler/rapport/rapport.msmodul.php');
require_once(DOKU_PLUGIN.'/minside/minside/moduler/rfrapport/class.rfrapport.rapportteam.php');
require_once(DOKU_PLUGIN.'/minside/minside/moduler/rfrapport/class.rfrapport.rapportteamcollection.php');

class msmodul_rfrapport extends msmodul_rapport {

    public $url;
    
    public function __construct($UserID, $accesslvl) {
        parent::__construct($UserID, $accesslvl);
    }
    
    public function gen_msmodul($act, $vars){
        $vars = (array) $vars;
        $vars['dbprefix'] = 'rfrap';
        $this->url = MS_LINK . "&amp;page=rfrapport";
        
        $objDispatcher = new ActDispatcher($this, $this->_accessLvl);
        $this->setHandlers($objDispatcher); // by ref - loader inn i objekt
        $this->dispatcher = $objDispatcher;
        
        return parent::gen_msmodul($act, $vars);
    }
    
    protected function setHandlers(ActDispatcher &$dispatcher) {
        parent::setHandlers($dispatcher);
        
        $dispatcher->addActHandler('teamadm', 'genTeamAdmin', MSAUTH_5);
        $dispatcher->addActHandler('addteammedlem', 'legg_til_team_medlem', MSAUTH_5);
        $dispatcher->addActHandler('addteammedlem', 'genTeamAdmin', MSAUTH_5);
    }
    
    public function registrer_meny(MenyitemCollection &$meny){
        $lvl = $this->_accessLvl;
        $act = $this->getMsmodulact();
        
        if ($lvl > MSAUTH_NONE) { 
        
            $toppmeny = new Menyitem('RF-Rapport','&amp;page=rfrapport');
            
            // "notoc" kan passes inn via msmodulvars når moduler lastes i en wiki-side, dette indikerer at modulen 
            // IKKE skal vise utvidet meny i sidebaren, selv om en feilmrapport-handlig utføres.
            if (isset($act) && array_search('notoc', (array) $this->_msmodulvars) === false) {
                $telleradmin = new Menyitem('Rediger tellere','&amp;page=rfrapport&amp;act=telleradm');
                $rapportarkiv = new Menyitem('Rapportarkiv','&amp;page=rfrapport&amp;act=rapportarkiv');
                $tpladmin = new Menyitem('Rapporttemplates','&amp;page=rfrapport&amp;act=genmodraptpl');
                $teamadmin = new Menyitem('Administrer team','&amp;page=rfrapport&amp;act=teamadm');
                
                switch($act) {
                    case 'rapportarkiv':
                    case 'visrapport':
                        $objSelected = $rapportarkiv;
                        break;
                    case 'nyteller':
                    case 'flipteller':
                    case 'modtellerorderned':
                    case 'modtellerorderopp':
                    case 'telleradm':
                        $objSelected = $telleradmin;
                        break;
                    case 'modtpllive':
                    case 'sletttpl':
                    case 'showtplmarkup':
                    case 'showtplpreview':
                    case 'genmodtpl':
                    case 'nyraptpl':
                    case 'genmodraptpl':
                    case 'modraptpl':
                        $objSelected = $tpladmin;
                        break;
                    case 'teamadm':
                        $objSelected = $teamadmin;
                        break;
                    case 'stengegetskift':
                    case 'nyttskift':
                    case 'savenotat':
                    case 'delnotat':
                    case 'undoakt':
                    case 'mod_teller':
                    case 'show':
                    default:                    
                        $objSelected = $toppmeny;
                        break;
                }
                if($objSelected instanceof Menyitem) {
                    $selectedtekst = '<span class="selected">' . $objSelected->getTekst() . '</span>';
                    $objSelected->setTekst($selectedtekst);
                }
            
                if ($lvl >= MSAUTH_2) {
                    $toppmeny->addChild($rapportarkiv);
                }
                if ($lvl >= MSAUTH_5) {
                    $toppmeny->addChild($telleradmin);
                    $toppmeny->addChild($tpladmin);
                    $toppmeny->addChild($teamadmin);
                }
            }      
            $meny->addItem($toppmeny); 
        }
    
    }
    
    public function genSkift() {        
        $skiftID = $this->getCurrentSkiftId();
        if ($skiftID === false) return $this->_genNoCurrSkift();
        
        try{
            $objSkift = $this->skiftfactory->getSkift($skiftID);
        } catch(Exception $e) {
            die($e->getMessage());
        }
        
        // Nylig aktivitet
        $arSisteEndringer = (array) $objSkift->getLastAct(6);
        $html_siste_endringer = RapportGen::genSisteEndringer($arSisteEndringer, $this->url);
        
        // Advarsel, gammelt skift
        $skift_alder_hele_timer = $objSkift->getSkiftAgeHours();
        $html_old_skift_warning = ($skift_alder_hele_timer >= 9)
            ? RapportGen::oldSkiftWarning(9)
            : '';

        // Notat - editbox
        if (($this->_msmodulact == 'modnotat') && isset($_REQUEST['notatid'])) {
            $objNotat = $objSkift->notater->getItem($_REQUEST['notatid']);
            $html_notat_edit = $this->genNotat($objNotat, true);
        } else {
            $html_notat_edit = $this->genNotat(null, true);
        }
        
        // Notatliste
        foreach($objSkift->notater as $objNotat) {
            if ($objNotat->isActive()) {
                $html_notat_liste .= $this->genNotat($objNotat);
            }
        }

        // Tellere
        if ($objSkift->getNumActiveTellere() == 0) {
            $html_ingen_tellere_warning = RapportGen::genIngenTellereWarning();
        }
        
        $colTellerNotNull = new TellerCollection();
        $colSecTeller = new TellerCollection();
        $colSecTellerNotNull = new TellerCollection();
        $colUlogget = new TellerCollection();
        $colUloggetNotNull = new TellerCollection();
        
        foreach($objSkift->tellere as $objTeller) {
            if (!$objTeller->isActive()) continue;
            
            switch ($objTeller->getTellerType()) {
                case 'TELLER':
                    $html_tellerinput_rows .= RapportGen::genTellerRow($objTeller, $this->url);
                    if ($objTeller->getTellerVerdi() > 0) {
                        $colTellerNotNull->addItem(clone($objTeller));
                    }
                    break;
                case 'ULOGGET':
                    $colUlogget->addItem(clone($objTeller));
                    if ($objTeller->getTellerVerdi() > 0) {
                        $colUloggetNotNull->addItem(clone($objTeller));
                    }
                    break;
                case 'SECTELLER':
                    $colSecTeller->addItem(clone($objTeller));
                    if ($objTeller->getTellerVerdi() > 0) {
                        $colSecTellerNotNull->addItem(clone($objTeller));
                    }
                    break;
            }
        }

        $html_sectellerinput_row = ($colSecTeller->length() > 0)
            ? RapportGen::genDropdownTellerRow($colSecTeller, $this->url, 'Flere tellere:')
            : '';
        $html_uloggettellerinput_row = ($colUlogget->length() > 0)
            ? RapportGen::genDropdownTellerRow($colUlogget, $this->url, 'Uloggede samtaler:')
            : '';
        
        // Tellerstatus / verdi
        $html_tellerstatus = ($colTellerNotNull->length() > 0)
            ? RapportGen::genTellerStatus($colTellerNotNull, 'Tellere:')
            : '';
        $html_tellerstatus .= ($colSecTellerNotNull->length() > 0)
            ? RapportGen::genTellerStatus($colSecTellerNotNull, 'Annet:')
            : '';
        $html_tellerstatus .= ($colUloggetNotNull->length() > 0)
            ? RapportGen::genTellerStatus($colUloggetNotNull, 'Ulogget:')
            : '';
        
        // "Template"
        $template = '
        <h1>RF-Rapport</h1>
        <div class="level2">
            <div class="skift_full">
                '.$html_old_skift_warning.'
                
                <div class="notater">
                    <fieldset id="notatfield" class="msfieldset">
                        <legend>Notater</legend>
                        '.$html_notat_edit.'
                    </fieldset>
                    <ul class="msul">
                        '.$html_notat_liste.'
                    </ul>
                </div>
                
                <div class="tellertable">
                    '.(($html_ingen_tellere_warning) ?: 
                    '<fieldset id="tellerfieldset" class="msfieldset">
                        <table class="feilmtable">
                            <th class="top">Beskrivelse</th>
                            <th class="top" colspan="2">Endre verdi</th>
                            '.$html_tellerinput_rows.'
                            '.$html_sectellerinput_row.'
                            '.$html_uloggettellerinput_row.'
                        </table>
                        '.$html_siste_endringer.'
                    </fieldset>
                    <br /><br />'
                ).'
                    <div class="antalltall">
                        '.$html_tellerstatus.'
                    </div>
                </div>
                
                <form method="post" action="' . $this->url . '">
                    <input type="hidden" name="act" value="stengegetskift" />
                    <input type="submit" class="msbutton" id="avsluttskift" value="Avslutt skift" />
                </form>

            </div>
        </div>       
        ';
        
        return $template;
    }
    
    public function genTeamAdmin() {
        $colTeam = RapportTeam::getAlleTeams('rfrap', true);
        $colBrukere = Bruker::getAlleBrukere();

        
        // Rediger teamnavn
        foreach($colTeam as $objTeam) {
            if(!$objTeam->getId()) continue;
            $html_team_options .= '<option value="'.$objTeam->getId().'">'.$objTeam->getNavn().'</option>' . "\n";
        }
        
        // Rediger rapportmottakere (team)
        foreach($colBrukere as $objBruker) {
            $brukeroptions .= '<option value="'.$objBruker->getId().'">'.$objBruker->getFullNavn().'</option>';
        }
        $html_brukerselect = '<select multiple="multiple" size="15" name="brukerid[]">'.$brukeroptions.'</select>';
        
        foreach($colTeam as $objTeam) {
            $mottakeroptions = '';
            foreach($objTeam->members as $objBruker) {
                $mottakeroptions .= '<option value="'.$objBruker->getId().'">' . $objBruker->getFullNavn() . '</option>';
            }
            
            $html_mottakere .= '
                <h3>Rapportmottakere '.$objTeam->getNavn().'</h3>
                <div class="level3">
                    <div class="teamadm_brukerliste">
                        <form method="post" action="' . $this->url . '">
                            <input type="hidden" name="act" value="fjernteammedlem" />
                            <input type="hidden" name="teamid" value="'.$objTeam->getId().'" />
                            <select multiple="multiple" size="15" name="brukerid[]">
                                '.$mottakeroptions.'
                            </select>
                            <br />
                            <input type="submit" class="msbutton" id="subtabortmedlem" value="Ta bort ----->" />
                        </form>
                    </div>
                    
                    <div class="teamadm_brukerliste">
                        <form method="post" action="' . $this->url . '">
                            <input type="hidden" name="act" value="addteammedlem" />
                            <input type="hidden" name="teamid" value="'.$objTeam->getId().'" />
                            '.$html_brukerselect.'
                            <br />
                            <input type="submit" class="msbutton" id="subleggtilmedlem" value="<----- Legg til" />
                        </form>
                    </div>
                </div>
            ';
        }
        
        $template = '
        <div class="teamadm">
            <h1>Team-administrasjon</h1>
            <div class="level1">
                <h2>Nytt team</h2>
                <div class="level2">
                    <form method="post" action="' . $this->url . '">
                        <input type="hidden" name="act" value="nyttteam" />
                            <label for="teamnavnid">Team-navn:</label>
                        <input type="text" id="teamnavnid" name="teamnavn" class="msedit" />
                        <input type="submit" class="msbutton" id="subnyttteam" value="Opprett team" 
                            onClick="return heltSikker(\'opprette nytt team\')" />
                    </form>
                </div>
                
                <h2>Rediger team-navn</h2>
                <div class="level2">
                    <form method="post" action="' . $this->url . '">
                        <input type="hidden" name="act" value="modteamnavn" />
                        <label for="teamselect">Nytt navn for team:</label>
                        <select name="teamid" id="teamselect">
                            '.$html_team_options.'
                        </select>
                        <input type="text" id="teamnavnid" name="teamnavn" class="msedit" />
                        <input type="submit" class="msbutton" id="modteamnavn" value="Lagre" 
                            onClick="return heltSikker(\'endre navn på team\')" />
                    </form>
                </div>
                
                <h2>Rapportmottakere per team</h2>
                <div class="level2">
                    '.$html_mottakere.'
                </form>
                </div>
            </div>
        </div>
        ';
        
        return $template;
    }
    
    public function legg_til_team_medlem() {
        msg('Team: ' . $_POST['teamid']);
        msg('Brukere: ' . implode(', ', $_POST['brukerid']));
    }
}
