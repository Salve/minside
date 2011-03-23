<?php
if(!defined('MS_INC')) die();

class RapportGen {
// Static html-relaterte utility functions for Rapport-moduler.

    public static function oldSkiftWarning($timer) {
        $tekst = 'Skiftet ditt er mer enn ' . $timer . ' timer gammelt!<br />'
        . 'Skift lukkes automatisk 14 timer etter de er opprettet.<br />';
        return self::genMsWarningbar($tekst, 'warnoldskift');
    }
    
    public static function genIngenTellereWarning() {
        $tekst = 'Ingen aktive tellere!<br /><br />'
            . 'En person med rette adgangsnivå må opprette/aktivere'
            . 'tellere for at de skal vises her.';
        return self::genMsWarningbar($tekst, 'warningentellere');
    }
    
    public static function genMsWarningbar($tekst, $id=null) {
        $html_id = ($id) ? ' id="'.$id.'"' : '';
        return '<div class="mswarningbar"'.$html_id.'>' .$tekst. '</div>';
    }
    
    public static function genTellerRow(Teller $objTeller, $url) {
        return '
        <tr>
            <form action="' . $url . '" method="POST">
                <input type="hidden" name="act" value="mod_teller" />
                <input type="hidden" name="tellerid" value="' . $objTeller->getId() . '" />
                <td class="feilmtablecols">
                    <div class="feilmtablecols">' . $objTeller->getTellerDesc() . '</div>
                </td>
                <td style="text-align:center;">
                    <input type="text" autocomplete="off" maxlength="2" value="1" id="rapverdi" class="msedit" name="modtellerverdi" />
                </td>
                <td>
                    <div class="inc_dec">
                        <input type="submit" class="msbutton msbuttonincdec" name="inc_teller" value="+" />
                        <input type="submit" class="msbutton msbuttonincdec" name="dec_teller" value="-" />
                    </div>
                </td>
            </form>
        </tr>';
    }
    
    public static function genDropdownTellerRow(TellerCollection $colTellere, $url, $overskrift) {
        $output = '
            <tr>
                <form action="' . $url . '" method="POST">
                    <input type="hidden" name="act" value="mod_teller" />
                    <td>
                        <select name="tellerid" class="msedit tellerdropdown">
                            <option value="NOSEL">'. $overskrift .' </option>
            ';
            
        foreach ($colTellere as $objTeller) {
            $output .= '<option value="' . $objTeller->getId() . '">' . $objTeller->getTellerDesc() . '</option>' . "\n";
        }
        
        $output .= '
                        </select>
                    </td>
                    <td style="text-align:center;">
                        <input type="text" autocomplete="off" maxlength="2" value="1" id="rapverdi" class="msedit" name="modtellerverdi" />
                    </td>
                    <td>
                        <div class="inc_dec">
                            <input type="submit" class="msbutton msbuttonincdec" name="inc_teller" value="+" />
                            <input type="submit" class="msbutton msbuttonincdec" name="dec_teller" value="-" />
                        </div>
                    </td>
                </form>
            </tr>
        ';
        
        return $output;
    }

    public static function genTellerStatus(TellerCollection $colTellere, $overskrift) {
        $output = '<p><strong>'. $overskrift .'</strong><br />' . "\n"; 
        foreach ($colTellere as $objTeller) {
            if ($objTeller->getTellerVerdi() > 0) $output .= $objTeller . '<br />' . "\n";
        }
        $output .= '</p>';
        
        return $output;
    }
    
    public static function genSisteEndringer(array $arSisteEndringer, $url) {
        if (!count($arSisteEndringer)) {
            return '';
        }

        $output = '
            <div class="sisteendringer msclearer">
                <strong>
                    <a href="javascript:;"  onClick="undoAct(\'viewAct\')">Siste endringer</a>
                </strong>
                <br />
                
                <div style="display:none;" id="viewAct">
        ';
        
        foreach ($arSisteEndringer as $arAkt) {
            $tidspunkt = date('H:i:s', strtotime($arAkt['tidspunkt']));
            $verdi = ($arAkt['verdi'] < 0) ? $arAkt['verdi'] : '+' . $arAkt['verdi'];
            $tellernavn = str_replace(' ', '&nbsp;', $arAkt['teller']);
            
            $output .= '
                    <div class="tellerakt msclearer">
                        <div class="tellerakttekst">
                            <em>Kl. ' . $tidspunkt . ':</em>&nbsp;&nbsp;
                            <strong>' . $verdi . '</strong><br />
                            '.$tellernavn.'
                        </div>
                        <div class="telleraktbilde">
                            <a href="' . $url . '&amp;act=undoakt&amp;aktid=' . $arAkt['id'] . '">
                                <img style="float:right;margin-top:3px;margin-right:3px;" src="' . MS_IMG_PATH . 'trash.png">
                            </a>
                        </div>
                    </div>';
        }
        
        $output .= '
                </div>
            </div>
        ';
        
        return $output;
    }
    
    public static function genTeamAdmin(RapportTeamCollection $colTeam, BrukerCollection $colBrukere, $url) {
        // Rediger team
        foreach($colTeam as $objTeam) {
            if(!$objTeam->getId()) continue;
            if ($objTeam->isActive()) {
                $html_aktiv_team_options .= '<option value="'.$objTeam->getId().'">'.$objTeam->getNavn().'</option>' . "\n";
            } else {
                $html_inaktiv_team_options .= '<option value="'.$objTeam->getId().'">'.$objTeam->getNavn().'</option>' . "\n";
            }
        }
        
        // Endre team navn
        if($html_aktiv_team_options) {
            $html_rename_team = '
                <form method="post" action="' . $url . '">
                    <input type="hidden" name="act" value="modteamnavn" />
                    <label for="teamselect">Nytt navn for team:</label>
                    <select name="teamid" id="teamselect">
                        '.$html_aktiv_team_options.'
                    </select>
                    <input type="text" id="teamnavnid" name="teamnavn" class="msedit" />
                    <input type="submit" class="msbutton" id="modteamnavn" value="Lagre" 
                        onClick="return heltSikker(\'endre navn på team\')" />
                </form><br />
            ';
        }
        // Deaktiver team
        if($html_aktiv_team_options) {
            $html_deaktiver_team = '
                <form method="post" action="' . $url . '">
                    <input type="hidden" name="act" value="flipteamactive" />
                    <label for="deaktiverselect">Aktive team: </label>
                    <select name="teamid" id="deaktiverselect">
                        '.$html_aktiv_team_options.'
                    </select>
                    <input type="submit" class="msbutton" id="subdeaktiverselect" value="Deaktiver" 
                        onClick="return heltSikker(\'deaktivere team\')" />
                </form><br />
            ';
        }
        //Aktiver team
        if($html_inaktiv_team_options) {
            $html_aktiver_team = '
                <form method="post" action="' . $url . '">
                    <input type="hidden" name="act" value="flipteamactive" />
                    <label for="aktiverselect">Inaktive team: </label>
                    <select name="teamid" id="aktiverselect">
                        '.$html_inaktiv_team_options.'
                    </select>
                    <input type="submit" class="msbutton" id="subaktiverselect" value="Aktiver" 
                        onClick="return heltSikker(\'aktivere team\')" />
                </form><br />
            ';
        }
        
        // Rediger rapportmottakere (team)
        foreach($colTeam as $objTeam) {
            if(!$objTeam->isActive()) continue;
            $mottakeroptions = '';
            $brukeroptions = '';
            foreach($objTeam->members as $objBruker) {
                $mottakeroptions .= '<option value="'.$objBruker->getId().'">' . $objBruker->getFullNavn() . '</option>';
            }
            foreach($colBrukere as $objBruker) {
                // Ikke list brukere som er på teamet fra før.
                if($objTeam->members->exists($objBruker->getId())) continue;
                $brukeroptions .= '<option value="'.$objBruker->getId().'">'.$objBruker->getFullNavn().'</option>';
            }
            $html_brukerselect = '<select multiple="multiple" size="15" name="brukerid[]">'.$brukeroptions.'</select>';
            
            $html_mottakere .= '
                <h3>Rapportmottakere '.$objTeam->getNavn().'</h3>
                <div class="level3">
                    <div class="teamadm_brukerliste">
                        <form method="post" action="' . $url . '">
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
                        <form method="post" action="' . $url . '">
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
                    <form method="post" action="' . $url . '">
                        <input type="hidden" name="act" value="nyttteam" />
                            <label for="teamnavnid">Team-navn:</label>
                        <input type="text" id="teamnavnid" name="teamnavn" class="msedit" />
                        <input type="submit" class="msbutton" id="subnyttteam" value="Opprett team" 
                            onClick="return heltSikker(\'opprette nytt team\')" />
                    </form>
                    <p>OBS! Team kan ikke slettes helt, kun deaktiveres! Ikke opprett unødvendige team.</p>
                </div>
                
                <h2>Rediger team</h2>
                <div class="level2">
                    '.$html_rename_team.$html_deaktiver_team.$html_aktiver_team.'
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
    

}
