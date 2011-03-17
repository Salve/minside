<?php
if(!defined('MS_INC')) die();
require_once(DOKU_PLUGIN.'/minside/minside/moduler/rapport/rapport.msmodul.php');

class msmodul_rfrapport extends msmodul_rapport {

    public $url;
    
    public function __construct($UserID, $accesslvl) {
        parent::__construct($UserID, $accesslvl);
    }
    
    public function gen_msmodul($act, $vars){
        $vars = (array) $vars;
        $vars['dbprefix'] = 'rfrap';
        $this->url = MS_LINK . "&amp;page=rfrapport";
        return parent::gen_msmodul($act, $vars);
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
                $genrapport = new Menyitem('Lag rapport','&amp;page=rfrapport&amp;act=genrapportsel');
                $rapportarkiv = new Menyitem('Rapportarkiv','&amp;page=rfrapport&amp;act=rapportarkiv');
                $tpladmin = new Menyitem('Rapporttemplates','&amp;page=rfrapport&amp;act=genmodraptpl');
                
                switch($act) {
                    case 'stengskift':
                    case 'genrapportsel':
                    case 'gensaverapport':
                    case 'genrapportmod':
                        $objSelected = $genrapport;
                        break;
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
                    $toppmeny->addChild($genrapport);
                    $toppmeny->addChild($rapportarkiv);
                }
                if ($lvl >= MSAUTH_5) {
                    $toppmeny->addChild($telleradmin);
                    $toppmeny->addChild($tpladmin);
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
    
}
