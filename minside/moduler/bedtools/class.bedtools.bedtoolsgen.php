<?php
if(!defined('MS_INC')) die();

class BedToolsGen {

    public static function genForside() {
        return '
            <h1>Bedriftsverktøy</h1>
            <div class="level1">
                <h2>SMS-varsling</h2>
                <div class="level2">
                    '.self::genInputFormVarsling().'
                </div>
            </div>
        ';
    }
    
    protected static function genInputFormVarsling() {
        return '
           <form action="' . MS_BEDTOOLS_LINK . '&amp;act=genvarsling" method="POST">
                <input type="radio" id="radio_kid" name="type_input" value="KID" />
                <label for="radio_kid">Kundenummer til Siebel Answers</label><br />
                <input type="radio" id="radio_tlf" name="type_input" value="TLF" />
                <label for="radio_tlf">Telefonnummer til sms-utsendelse</label><br /><br />
                <table>
                    <tr>
                        <td>
                            Inndata:<br />
                            <textarea name="varsel_data" cols="14" rows="10"></textarea><br />
                        </td>
                        <td>
                            Filterdata:<br />
                            <textarea name="filter_data" cols="14" rows="10"></textarea><br />
                        </td>
                    </tr>
                </table>
                <br /><br /><input type="submit" value="Generer streng" class="button" />
            </form>
        
        ';
    }

}
