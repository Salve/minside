<?php
if(!defined('MS_INC')) die();

class BedToolsGen {

    public static function genForside() {
        return '
            <h1>Bedriftsverktøy</h1>
            <div class="level1">
                <h2>Streng-generator</h2>
                <div class="level2">
                    '.self::genInputFormVarsling().'
                </div>
            </div>
        ';
    }
    
    protected static function genInputFormVarsling() {
        return '
            <p>Dette verktøyet tar inndata (f.eks. fra Oracle BI eller et Excel-ark) og evt. en liste med filterdata som skal filtreres ut av inputen og benytter
            dette til å generere en streng formatert etter forskjellige regler. Noen av reglene vil også validere og tilpasse inputen. Duplikater i inndata
            fjernes alltid, det vil si at de kun kommer med en gang i output. Dersom elementer i inndata finnes i filterdata, vil disse ikke komme med i output.</p>
           <form action="' . MS_BEDTOOLS_LINK . '&amp;act=genvarsling" method="POST">
                <input type="radio" id="radio_kid" name="type_input" value="KID" />
                <label for="radio_kid">Kundenummer til Oracle BI</label><br />
                <input type="radio" id="radio_obi" name="type_input" value="OBI" />
                <label for="radio_obi">Annen data til Oracle BI, uten validering</label><br />
                <input type="radio" id="radio_tlf" name="type_input" value="TLF" />
                <label for="radio_tlf">Telefonnummer til sms-utsendelse</label><br />
                <input type="radio" id="radio_custom" name="type_input" value="CUSTOM" />
                <label for="radio_custom">Ingen validering, output skilles med: </label>
                <input type="text" size="5" name="outputskille_custom" value="" />
                <br /><br />
                <table style="width:70%">
                    <tr>
                        <th>
                            <strong>Inndata</strong>
                        </th>
                        <th>
                            <strong>Filterdata</strong>
                        </th>
                    </tr>
                    <tr>
                        <td>
                            Elementer er skilt med:<br />
                            <input type="radio" id="radio_inputskille_newline" name="type_inputskille" value="NEWLINE" checked="checked" />
                            <label for="radio_inputskille_newline">Linjeskift</label><br />
                            <input type="radio" id="radio_inputskille_custom" name="type_inputskille" value="CUSTOM" />
                            <label for="radio_inputskille_custom">Annet:</label>
                            <input type="text" size="5" name="inputskille_custom" />
                        </td>
                        <td>
                            Elementer er skilt med:<br />
                            <input type="radio" id="radio_filterskille_newline" name="type_filterskille" value="NEWLINE" />
                            <label for="radio_filterskille_newline">Linjeskift</label><br />
                            <input type="radio" id="radio_filterskille_custom" name="type_filterskille" value="CUSTOM" checked="checked" />
                            <label for="radio_filterskille_custom">Annet:</label>
                            <input type="text" size="5" name="filterskille_custom" value=";" />
                        </td>
                    </tr>
                    <tr>
                        <td>
                            Inndata:<br />
                            <textarea name="varsel_data" cols="25" rows="10"></textarea><br />
                        </td>
                        <td>
                            Filterdata:<br />
                            <textarea name="filter_data" cols="25" rows="10"></textarea><br />
                        </td>
                    </tr>
                </table>
                <br /><br /><input type="submit" value="Generer streng" class="button" />
            </form>
        
        ';
    }

}
