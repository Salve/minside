<?php
if(!defined('MS_INC')) die();
class msmodul_sidebar implements msmodul{

    private $_msmodulact;
    private $_msmodulvars;
    private $_userID;
    private $_adgangsNiva;

    private $static_sidebar;
    
    public function __construct($UserID, $AdgangsNiva) {
    
        $this->_userID = $UserID;
        $this->_adgangsNiva = $AdgangsNiva;
    }
    
    public function gen_msmodul($act, $vars){
        $this->_msmodulact = $act;
        $this->_msmodulvars = $vars;
        
        $output = $this->getSidebar();
        
        return $output; 
    
    }
    
    public function registrer_meny(MenyitemCollection &$meny){ 
        $lvl = $this->_adgangsNiva;
    
        if ($lvl > MSAUTH_NONE) {
            $toppmeny = new Menyitem('Sidebar','&page=sidebar');
            $meny->addItem($toppmeny);
        }
    
    }

    private function getSidebar() {
        if(auth_quickaclcheck('msauth:vismeny:info') >= AUTH_READ){
            $minside = '<tr><td class="menu_title" align="left"><a href="doku.php?do=minside" class="menu_item">Min Side</a> </td></tr>';
        }
        if(auth_quickaclcheck('feilbed:index') >= AUTH_READ) {
            $feilbed = '<tr><td class="menu_title" align="left"><a href="doku.php?id=feilbed:index" class="menu_item">FeilBed</a></td></tr>';
        }
    
        $output ='
        <div class="left_sidebar">
        <TABLE cellspacing=4>
        <tr><td>&nbsp;</td>
        <tr><td class="menu_title" align="left"><a href="doku.php?id=altibox" class="menu_item">Hovedside</a> </td></tr>
        ' . $minside . '
        ' . $feilbed . '
        <tr><td class="menu_title" align="left"><a href="doku.php?id=internett:faq" class="menu_item">FAQ Internett</a> </td></tr>
        <tr><td class="menu_title" align="left"><a href="doku.php?id=hjelp:dokumentasjon:indeks" class="menu_item">Hjelp</a> </td></tr>
        <tr><td class="menu_title" align="left"><a href="doku.php?id=nyhetsbrev:hoved" class="menu_item">Nyhetsbrev</a> </td></tr>
        <tr><td>&nbsp;</td>
         
        <tr><td class="menu_title" align="left"><a href="doku.php?id=rutiner:index" class="menu_item">Rutiner</a></td>
        <tr><td><a href="doku.php?id=rutiner:retningslinjer" class="menu_item">Retningslinjer</a></td></tr>
        <tr><td><a href="doku.php?id=iscu:iscu" class="menu_item">IS Customer</a></td></tr>
        <tr><td><a href="doku.php?id=siebel:siebel" class="menu_item">Siebel</a></td></tr>
        <tr><td><a href="doku.php?id=tv:TV-pakker" class="menu_item">TV-pakker</a></td></tr>
        <tr><td><a href="doku.php?id=rutiner:tildelingsregler" class="menu_item">Tildelingsregler</a></td></tr>
        <tr><td><a href="doku.php?id=km:km" class="menu_item">Kundemottaket</a></td></tr>
        <tr><td><a href="doku.php?id=rutiner:mailmaler" class="menu_item">Mailmaler</a></td></tr>
        <tr><td><a href="doku.php?id=rutiner:byggestrom" class="menu_item">Byggestrøm</a></td></tr>
        <tr><td><a href="doku.php?id=rutiner:bedrift" class="menu_item">Bedrift</a></td></tr>
         
        <tr><td>&nbsp;</td> 
        <tr><td class="menu_title" align="left"><a href="doku.php?id=teknisk:index">Teknisk</a></td>
        <tr><td><a href="doku.php?id=teknisk:hjemmesentral" class="menu_item">Hjemmesentral</a></td></tr>
        <tr><td><a href="doku.php?id=tv:dekoder" class="menu_item">Dekoder</a></td></tr>
        <tr><td><a href="doku.php?id=internett:index" class="menu_item">Internett</a> </td></tr>
        <tr><td><a href="doku.php?id=telefoni:tlf" class="menu_item">Telefoni</a> </td></tr>
        <tr><td><a href="doku.php?id=alarm:alarm" class="menu_item">Alarm</a> </td></tr>
        <tr><td><a href="doku.php?id=tv:tv" class="menu_item">Generelt om TV</a> </td></tr>
        <tr><td><a href="doku.php?id=hjelp:selfcare" class="menu_item">Selfcare</a> </td></tr>
        <tr><td><A HREF="http://79.161.213.78/ping.php" class="menu_item" TARGET="_blank">Pingverktøy</A> </td></tr>
         
         
        <tr><td>&nbsp;</td> 
        <tr><td class="menu_title" align="left"><a href="doku.php?id=partner:index">Partnere</a></td>
        <tr><td><a href="doku.php?id=partner:viken" class="menu_item">Viken</a></td></tr>
        <tr><td><a href="doku.php?id=partner:ostfold" class="menu_item">Østfold </a> </td></tr>
        <tr><td><a href="doku.php?id=partner:vesteral" class="menu_item">Vesterålskraft </a> </td></tr>
        <tr><td><a href="doku.php?id=partner:klepp" class="menu_item">Klepp</a> </td></tr>
         
        <tr><td>&nbsp;</td>
        <tr><td class="menu_title" align="left"><a href="doku.php?id=energi:index">Energi</a></td>
        <tr><td><a href="doku.php?id=energi:strom" class="menu_item">Strøm</a></td></tr>
        <tr><td><a href="doku.php?id=energi:gass" class="menu_item">Gass</a> </td></tr>
        <tr><td><a href="doku.php?id=energi:fjernvarme" class="menu_item">Fjernvarme</a> </td></tr>
        <tr><td><a href="doku.php?id=rutiner:byggestrom" class="menu_item">Byggestrøm</a></td></tr>
         
        <tr><td>&nbsp;</td> 
        <tr><td class="menu_title" align="left">Linker</td>
        <tr><td><a href="doku.php?id=linker:intern-link" class="menu_item">Interne</a></td></tr>
        <tr><td><a href="doku.php?id=linker:ekstern-link" class="menu_item">Eksterne</a> </td></tr>
        <tr><td><a href="doku.php?id=linker:kontakter" class="menu_item">Kontaktpersoner</a> </td></tr>
         
        <tr><td>&nbsp;</td>
        <tr><td class="menu_title" align="left"><a href="http://sharepoint/kundeservice/Intern%20informasjon/Forms/AllItems.aspx?RootFolder=%2fkundeservice%2fIntern%20informasjon%2fFerie%20og%20vaktplaner%2fVaktplaner&View=%7b8EB45905%2dB953%2d4ADB%2d9170%2d8500198C7123%7d">Vaktplaner</a></td>
        <tr><td><A HREF="http://sharepoint/kundeservice/Intern%20informasjon/Ferie%20og%20vaktplaner/Vaktplaner/Vaktliste%20Feilmeldingstjenesten%20uke%2017%20-%2023.xls" class="menu_item" TARGET="_blank">Feilm</A> </td></tr>
        <tr><td><A HREF="http://sharepoint/kundeservice/Intern%20informasjon/Ferie%20og%20vaktplaner/Vaktplaner/Mai_2010.xls" class="menu_item" TARGET="_blank">KS Mai</A> </td></tr>
        <tr><td><A HREF="http://sharepoint/kundeservice/Intern%20informasjon/Ferie%20og%20vaktplaner/Vaktplaner/Turnus%20butikk%20Uke%2020%20til%20uke%2025.xls" class="menu_item" TARGET="_blank">Butikk</A> </td></tr>
         
        <tr><td>
         <a href="mailto:&#x74;&#x6f;&#x72;&#x62;&#x6a;&#x6f;&#x72;&#x6e;&#x2e;&#x64;&#x61;&#x6c;&#x6c;&#x61;&#x6e;&#x64;&#x40;&#x6c;&#x79;&#x73;&#x65;&#x2e;&#x6e;&#x6f;&#x3b;&#x6d;&#x61;&#x72;&#x74;&#x69;&#x6e;&#x2e;&#x66;&#x65;&#x65;&#x64;&#x40;&#x6c;&#x79;&#x73;&#x65;&#x2e;&#x6e;&#x6f;&#x3b;&#x65;&#x69;&#x72;&#x69;&#x6b;&#x2e;&#x6f;&#x73;&#x74;&#x72;&#x65;&#x6d;&#x40;&#x6c;&#x79;&#x73;&#x65;&#x2e;&#x6e;&#x6f;" class="menu_item" style="font-weight:bold;">Kontakt Admin</a></td>

        </TABLE>
        </div>  <!-- end left_sidebar-->
        ';
        
        return $output;
    }
}
