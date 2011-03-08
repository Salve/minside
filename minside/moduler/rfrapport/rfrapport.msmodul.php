<?php
if(!defined('MS_INC')) die();
define('MS_KSR_LINK', MS_LINK . "&amp;page=rfrapport");

class msmodul_rfrapport extends msmodul_rapport {
	
	public function __construct($UserID, $accesslvl) {
        parent::__construct($UserID, $accesslvl);
	}
	
	public function gen_msmodul($act, $vars){
        $vars = (array) $vars;
        $vars['dbprefix'] = 'rfrap';
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
    
}
