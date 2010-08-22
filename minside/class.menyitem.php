<?php
if(!defined('MS_INC')) die();

class Menyitem {
    protected $_id;
    protected $_tekst;
    protected $_href;
    protected $_acl;
    protected $_type;
    protected $_order;
    protected $_hasChildren = false;
    protected $_children;
    protected $_hasUnsavedChanges = false;
    
    // Typedefinisjoner for sidebar-modulen
    const TYPE_HEADER = 1;
    const TYPE_NORMAL = 2;
    const TYPE_SPACER = 3;
    
    // Custom-typer
    const TYPE_MSTOC = 10;
	
	public function __construct($tekst, $href = NULL, $acl = NULL, $type = NULL) {
		$this->_tekst = $tekst;
		$this->_href = $href;
        $this->_acl = $acl;
        $this->_type = $type;
	}
    
    public function setSaved($id, $order) {
        $this->_id = $id;
        $this->_order = $order;
    }
	
    public function isSaved() {
        return isset($this->_id);
    }
    
	public function getTekst() {
		return $this->_tekst;
	}
    public function setTekst($input) {
        $this->_setvar($this->_tekst, $input);
    }
	
	public function getHref() {
		return $this->_href;
	}
    public function setHref($input) {
        $this->_setvar($this->_href, $input);
    }
    
    public function getAcl() {
        return $this->_acl;
    }
    public function setAcl($input) {
        $this->_setvar($this->_acl, $input);
    }
    
    public function getType() {
        return $this->_type;
    }
    public function setType($input) {
        $this->_setvar($this->_type, $input);
    }
    
    public function getOrder() {
        return $this->_order;
    }
    public function setOrder($input) {
        $this->_setvar($this->_order, $input);
    }
    
    public function checkAcl() {
        if (!isset($this->_acl)) return true;
        return (auth_quickaclcheck($this->_acl) > MSAUTH_NONE);
    }
	
	public function addChildren(MenyitemCollection $col) {
		$this->_hasChildren = true;
		if(isset($this->_children)) {
			foreach ($col as $child) {
				$this->_children->addItem($child);
			}
		} else {
			$this->_children = $col;
		}
		
	}
	
	public function addChild(Menyitem $child) {
		$this->_hasChildren = true;
		if(isset($this->_children)) {
			$this->_children->addItem($child);
		} else {
			$this->_children = new MenyitemCollection();
			$this->_children->addItem($child);
		}
	}
	
	public function getChildren() {
		return $this->_children;
	}
	
	public function hasChildren() {
		if ($this->_hasChildren === true) {
			return true;
		} else {
			return false;
		}
	}
	
	public function __toString() {
		$output = '<a href ="' . $this->_href . '">' . $this->_tekst . '</a>';
		return $output;
	}
    
    private function _setvar(&$var, &$input) {
        if ($this->isSaved() && ($var != $input)) {
            $this->_hasUnsavedChanges = true;   
        }
        
        $var = $input;
        
    }
    
    public function updateDb() {
        global $msdb;
        
        $safeid = $msdb->quote($this->_id);
        $safetekst = $msdb->quote($this->_tekst);
        $safehref = $msdb->quote($this->_href);
        $safeacl = $msdb->quote($this->_acl);
        $safetype = $msdb->quote($this->_type); 

        
        if (!$this->isSaved()) {
            msg('Writing new sidebar blokk to db...', 2);
            $safeorder = $msdb->quote($this->getLastOrder());
            $sql = "INSERT INTO sidebar_blokk SET
                    blokknavn = $safetekst,
                    blokkurl = $safehref,
                    blokktype = $safetype,
                    blokkacl = $safeacl,
                    blokkorder = $safeorder;"; 
        } else {
            msg('Updating db with sidebar blokk');
            $sql = "UPDATE sidebar_blokk SET
                    blokknavn = $safetekst,
                    blokkurl = $safehref,
                    blokktype = $safetype,
                    blokkacl = $safeacl
                    WHERE blokkid = $safeid
                    LIMIT 1;";
        }
        
        $result = $msdb->exec($sql);
        
        if (!$this->isSaved()) {
            $this->_id = $msdb->getLastInsertId();
        }
        
    }
    
    public function modOrderOpp() {
        return $this->_modOrder(true);
    }
    
    public function modOrderNed() {
        return $this->_modOrder(false);
    }
    
    protected function _modOrder($modopp) {
        global $msdb;
        
		if (!$this->isSaved()) throw new Exception('Logic error: Kan ikke endre rekkefølge, menyblokk er ikke lagret');
		if (empty($this->_order)) throw new Exception('Logic error: Kan ikke endre rekkefølge, menyblokk-rekkefølge ikke satt');
	
		$oldorder = $this->_order;
		$neworder = $this->_order + (($modopp) ? -1 : 1);

		$safeoldorder = $msdb->quote($oldorder);
		$safeneworder = $msdb->quote($neworder);
		$safeblokkid = $msdb->quote($this->_id);
		
		$msdb->startTrans();
		$resultat = $msdb->exec("UPDATE sidebar_blokk SET blokkorder=$safeoldorder WHERE blokkorder=$safeneworder LIMIT 1;");
		if ($resultat == 0) {
			$msdb->rollBack();
			throw new Exception('Ingen menyblokk ' . (($modopp) ? 'over' : 'under') . ' blokken du forsøkte å flytte.');
		}
		
		$resultat = $msdb->exec("UPDATE feilrap_blokk SET blokkorder=$safeneworder WHERE blokkid=$safeblokkid LIMIT 1;");
		if ($resultat == 0) {
			$msdb->rollBack();
			throw new Exception('Databaseoppdatering feilet.');
		} else {
			$msdb->commit();
			$this->_order = $neworder;
			return true;
		}
		
    
    }
    
    public static function getLastOrder() {
        global $msdb;
        
        $sql = "SELECT blokkorder FROM sidebar_blokk ORDER BY blokkorder DESC LIMIT 1";
        $result = $msdb->num($sql);
        
        return $result[0][0] + 1;
        
    }
	
}
