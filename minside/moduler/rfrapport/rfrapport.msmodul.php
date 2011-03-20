<?php
if(!defined('MS_INC')) die();
require_once(DOKU_PLUGIN.'/minside/minside/moduler/rapport/rapport.msmodul.php');
require_once(DOKU_PLUGIN.'/minside/minside/moduler/rfrapport/class.rfrapport.rapportteam.php');
require_once(DOKU_PLUGIN.'/minside/minside/moduler/rfrapport/class.rfrapport.rapportteamcollection.php');
require_once(DOKU_PLUGIN.'/minside/minside/moduler/rfrapport/class.rfrapport.rfskift.php');
require_once(DOKU_PLUGIN.'/minside/minside/moduler/rfrapport/class.rfrapport.rfrapport.php');
require_once(DOKU_PLUGIN.'/minside/minside/moduler/rfrapport/class.rfrapport.rfskiftfactory.php');

class msmodul_rfrapport extends msmodul_rapport {

    public $url;
    
    public function __construct($UserID, $accesslvl) {
        parent::__construct($UserID, $accesslvl);
    }
    
    public function gen_msmodul($act, $vars){
        $vars = (array) $vars;
        $vars['dbprefix'] = 'rfrap';
        $this->url = MS_LINK . "&amp;page=rfrapport";
        $this->skiftfactory = new RFSkiftFactory('rfrap');
        
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
        $dispatcher->addActHandler('fjernteammedlem', 'fjern_team_medlem', MSAUTH_5);
        $dispatcher->addActHandler('fjernteammedlem', 'genTeamAdmin', MSAUTH_5);
        $dispatcher->addActHandler('flipteamactive', 'flip_team_active', MSAUTH_5);
        $dispatcher->addActHandler('flipteamactive', 'genTeamAdmin', MSAUTH_5);
        $dispatcher->addActHandler('modteamnavn', 'endre_team_navn', MSAUTH_5);
        $dispatcher->addActHandler('modteamnavn', 'genTeamAdmin', MSAUTH_5);
        $dispatcher->addActHandler('nyttteam', 'opprett_team', MSAUTH_5);
        $dispatcher->addActHandler('nyttteam', 'genTeamAdmin', MSAUTH_5);
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
                    case 'addteammedlem':
                    case 'fjernteammedlem':
                    case 'modteamnavn':
                    case 'flipteamactive':
                    case 'nyttteam':
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

        // Skifttype
        $arSkiftNavn = array(
            1 => 'Morgen',
            2 => 'Ettermiddag',
            3 => 'Kveld'
        );
        //$skiftnavn = $arSkiftNavn[$objSkift->getSkiftType()];

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
        <h1>RF-Rapport &ndash; Team XXX &ndash; '.$skiftnavn.'</h1>
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

        return RapportGen::genTeamAdmin($colTeam, $colBrukere, $this->url);
    }
    
    public function legg_til_team_medlem() {
        $teamid = (int) $_POST['teamid'];
        $brukerider = (array) $_POST['brukerid'];
    
        if(MinSide::DEBUG) {
            msg('Legger til brukere i team.');
            msg('Team: ' . $teamid);
            msg('Brukere: ' . implode(', ', $brukerider));
        }
        
        $colTeams = RapportTeam::getAlleTeams('rfrap', true);
        if($objTeam = $colTeams->getItem($teamid)) {
            $res = $objTeam->add_members($brukerider);
            msg($res . ' brukere lagt til.', 1);
        } else {
            throw new Exception('Ukjent team-id: ' . $teamid);
        }
    }
    
    public function fjern_team_medlem() {
        $teamid = (int) $_POST['teamid'];
        $brukerider = (array) $_POST['brukerid'];
    
        if(MinSide::DEBUG) {
            msg('Fjerner brukere fra team.');
            msg('Team: ' . $teamid);
            msg('Brukere: ' . implode(', ', $brukerider));
        }
        
        $colTeams = RapportTeam::getAlleTeams('rfrap', true);
        if($objTeam = $colTeams->getItem($teamid)) {
            $res = $objTeam->remove_members($brukerider);
            msg($res . ' brukere fjernet fra ' . $objTeam->getNavn(), 1);
        } else {
            throw new Exception('Ukjent team-id: ' . $teamid);
        }
    }
    
    public function flip_team_active() {
        $teamid = (int) $_POST['teamid'];
    
        if(MinSide::DEBUG) msg('Endrer status på team: '. $teamid);
        
        $colTeams = RapportTeam::getAlleTeams('rfrap', true);
        if($objTeam = $colTeams->getItem($teamid)) {
            $status = $objTeam->isActive();
            $objTeam->setIsActive(!$status);
            if ($objTeam->updateDb()) {
                msg($objTeam->getNavn() . ' er nå ' . (($status) ? 'inaktivt' : 'aktivt'), 1);
            } else {
                throw new Exception('Klarte ikke å endre status på team.');
            }
        } else {
            throw new Exception('Ukjent team-id: ' . $teamid);
        }
    }
    
    public function endre_team_navn() {
        $teamid = (int) $_POST['teamid'];
        $teamnavn = hsc(trim($_POST['teamnavn']));
    
        if(MinSide::DEBUG) msg('Endrer navn på team: '. $teamid);
        
        $colTeams = RapportTeam::getAlleTeams('rfrap');
        if($objTeam = $colTeams->getItem($teamid)) {
            $oldnavn = $objTeam->getNavn();
            $objTeam->setNavn($teamnavn);
            if ($objTeam->updateDb()) {
                msg('Endret teamnavn fra ' . $oldnavn . ' til ' . $teamnavn, 1);
            } else {
                msg('Team-navn ikke endret!', -1);
            }
        } else {
            throw new Exception('Ukjent team-id: ' . $teamid);
        }
    }
    
    public function opprett_team() {
        $teamnavn = hsc(trim($_POST['teamnavn']));
    
        if(MinSide::DEBUG) msg('Oppretter team: '. $teamnavn);
        
        if(strlen($teamnavn)) {
            $objTeam = new RapportTeam('rfrap');
            $objTeam->setNavn($teamnavn);
            $objTeam->setIsActive(true);
            if ($objTeam->updateDb()) {
                msg('Opprettet team: ' . $teamnavn, 1);
            } else {
                throw new Exception('Klarte ikke å opprette team.');
            }
        } else {
            throw new Exception('Klarte ikke å opprette team: Ugyldig teamnavn.');
        }
    }
    
    public function _createNyttSkift() {
        if (!$this->getCurrentSkiftId() === false) { 
            throw new Exception('Kan ikke opprette nytt skift når det allerede finnes et aktivt skift.');
        }
        
        // Validate skifttype
        $skifttype = (int) $_POST['skifttype'];
        if($skifttype !== RFSkift::TYPE_MORGEN
            && $skifttype !== RFSkift::TYPE_ETTERMIDDAG
            && $skifttype !== RFSkift::TYPE_KVELD) {
            msg('Du må velge skifttype for å fortsette!', -1);
            return;
        }
        
        // Validate team
        $teamid = (int) $_POST['team'];
        $colTeams = RapportTeam::getAlleTeams('rfrap', false, false); // alle aktive teams - ikke felles
        if(!$colTeams->exists($teamid)) {
            msg('Du må velge team for å fortsette!', -1);
            return;
        }
        
        $this->skiftfactory->nyttSkiftForBruker($this->_userId, $skifttype, $teamid);
        if (isset($this->_currentSkiftId)) { 
            unset($this->_currentSkiftId);
        }
    }
    
    public function _genNoCurrSkift() {
        $colTeam = RapportTeam::getAlleTeams('rfrap');
        
        $teamcounter = 0;
        foreach($colTeam as $objTeam) {
            if(!$objTeam->isActive()) continue;
            $html_team_options .= '<option value="'.$objTeam->getId().'">'.$objTeam->getNavn().'</option>';
            $teamcounter += 1;
        }
        
        if(!$teamcounter) throw new Exception('Ingen aktive team. En wiki-administrator '
            . 'må sette opp disse før RF-rapportmodulen kan benyttes.');
        
        return '
        <h1>RF-rapport</h1>
        <div class="level2">
            <div class="noskift">
                <ul class="msul">
                    <li>Du har ikke noe aktivt skift.</li>
                    <li>For å fortsette må du velge skift og team &ndash; og så klikke &quot;opprett skift&quot;.</li>
                </ul>
                <br/>
                <form method="post" action="' . $this->url . '">
                    <input type="hidden" name="act" value="nyttskift" />
                    <select name="skifttype" class="edit">
                        <option value="0">Velg skift...</option>
                        <option value="1">Morgen</option>
                        <option value="2">Ettermiddag</option>
                        <option value="3">Kveld</option>
                    </select>
                    <select name="team" class="edit">
                        <option value="0">Velg team...</option>
                        '.$html_team_options.'
                    </select>
                    <input type="submit" class="msbutton" value="Start nytt skift!" />
                </form>
            </div>
        </div>    
        ';
    }
}
