<?php
if(!defined('MS_INC')) die();

require_once('class.sidebarfactory.php');
require_once('class.sidebargen.php');

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
        
		switch($act) {
			case 'show':
				return $this->getSidebar();
				break;
			case 'rem':
				if ($this->_adgangsNiva >= MSAUTH_2) {
					$this->_doRem();
					return $this->_genAdmin();
				}
				break;
			case 'InsOrMov':
				if ($this->_adgangsNiva >= MSAUTH_2) {
					if (isset($_REQUEST['addaction'])) {
						$this->_doAdd();
					} elseif (isset($_REQUEST['movblokkid'])) {
						$this->_doMov();
					}
					return $this->_genAdmin();
				}
				break;
			case 'admin':
				if ($this->_adgangsNiva >= MSAUTH_2) 
					return $this->_genAdmin();
				break;
		} 
    
    }
	
	private function _genAdmin() {
		$objSidebar = SidebarFactory::getSidebar();		
		return SidebarGen::genAdmin($objSidebar);
	}
	
	private function _doMov() {
		
		if (!isset($_REQUEST['targetblokkid'])) {
			msg('Du må velge ny posisjon for å flytte blokk.', -1);
			return false;
		}
		
		try {
			$objTarget = SidebarFactory::getBlokkById($_REQUEST['targetblokkid']);
			$objObject = SidebarFactory::getBlokkById($_REQUEST['movblokkid']);
			
			$objObject->changeOrder($objTarget->getOrder());
		} catch (Exception $e) {
			msg('Klarte ikke å flytte blokk: ' . $e->getMessage(), -1);
			return;
		}
		
	}
	
	private function _doAdd() {
		
		switch ($_REQUEST['addaction']) {
			case 'Lag overskrift':
				if (empty($_REQUEST['addtekst'])) {
					msg('Tekst er obligatorisk for overskrift', -1);
					return false;
				}
				$navn = htmlspecialchars($_REQUEST['addtekst']);
				$href = htmlspecialchars($_REQUEST['addhref']);
				$acl = cleanID($_REQUEST['addacl'], true);
				$type = Menyitem::TYPE_HEADER;
				break;
			case 'Lag vanlig lenke':
				if (empty($_REQUEST['addtekst'])) {
					msg('Tekst er obligatorisk for lenker', -1);
					return false;
				}
				if (empty($_REQUEST['addhref'])) {
					msg('URL er obligatorisk for lenker', -1);
					return false;
				}
				$navn = htmlspecialchars($_REQUEST['addtekst']);
				$href = htmlspecialchars($_REQUEST['addhref']);
				$acl = cleanID($_REQUEST['addacl'], true);
				$type = Menyitem::TYPE_NORMAL;
				break;
			case 'Spacer':
				$navn = 'Spacer';
				$type = Menyitem::TYPE_SPACER;
				$acl = cleanID($_REQUEST['addacl'], true);
				break;
			case 'MinSide meny':
				$navn = 'MinSide Meny';
				$type = Menyitem::TYPE_MSTOC;
				$href= 'doku.php?do=minside';
				$acl = cleanID($_REQUEST['addacl'], true);
				break;
		}
		
		$objMenyitem = new Menyitem($navn, $href, $acl, $type);
		$objMenyitem->updateDb();
		
	}
	private function _doRem() {
		try {
			$objMenyitem = SidebarFactory::getBlokkById($_REQUEST['blokkid']);
			$objMenyitem->delete();
		} catch (Exception $e) {
			msg('Klarte ikke å slette blokk: ' . $e->getMessage(), -1);
			return;
		}
		
		msg('Slettet sidebar-blokk.', 1);
		return true;
		
	}
    
    public function registrer_meny(MenyitemCollection &$meny){ 
        $lvl = $this->_adgangsNiva;
    
        if ($lvl > MSAUTH_NONE) {
            $toppmeny = new Menyitem('Sidebar','&page=sidebar&act=admin');
            $meny->addItem($toppmeny);
        }
    
    }

    private function getSidebar() {
	
		$objSidebar = SidebarFactory::getSidebar();		
		return SidebarGen::genSidebar($objSidebar);
	
    }

}
