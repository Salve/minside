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

}
