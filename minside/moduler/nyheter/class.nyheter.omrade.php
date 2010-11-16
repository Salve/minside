<?php
if(!defined('MS_INC')) die();

class NyhetOmrade {

    private static $_omrader = array();
	
	private $_parent_ns;
	private $_omrade_full;
	private $_omrade;
    private $_acl;
    
    private $_dblinked = false;
    private $_farge;
    private $_visningsnavn;
    private $_isdefault;
	
	public function __construct($omrade) {
		$this->_omrade_full = $omrade;
		$this->_omrade = noNS($omrade);
		$this->_parent_ns = getNS($omrade);
	}
    
    // Db properties
    public function getFarge() {
        return $this->_farge;
    }
    public function setFarge($input) {
        $this->_farge = $input;
    }
	public function getVisningsnavn() {
        if(!empty($this->_visningsnavn)) {
            return $this->_visningsnavn;
        } else {
            return $this->_omrade;
        }
    }
    public function setVisningsnavn($input) {
        $this->_visningsnavn = $input;
    }
    public function isDefault() {
        return (bool) $this->_isdefault;
    }
    public function setDefault($input) {
        $this->_isdefault = (bool) $input;
    }
    public function isDbLinked() {
        return (bool) $this->_dblinked;
    }
    public function setDbLinked($input) {
        $this->_dblinked = (bool) $input;
    }
    
	public function getOmrade() {
		return $this->_omrade;
	}
	
	public function getParent() {
		return $this->_parent_ns;
	}
	
	public function getOmradeID() {
		return $this->_omrade_full;
	}
	
	public function checkAcl($lvl, $user=null) {
		return ($this->getAcl($user) >= $lvl);
	}
	
	public function getAcl($user=null) {
        // $user, hvis satt skal være array med keys name og groups
        // name skal være streng med wiki-brukernavn whoms access skal sjekkes
        // groups skal være array med alle brukergruppene denne brukeren er i
        if(is_array($user)) {
            if (!isset($this->_acl[$user['name']])) {
                $this->_acl[$user['name']] = auth_aclcheck($this->_omrade_full.':*', $user['name'], (array) $user['groups']);
            }
            return $this->_acl[$user['name']];
        } else {
            if (!isset($this->_acl[0])) {
                $this->_acl[0] = auth_quickaclcheck($this->_omrade_full.':*');
            }
            return $this->_acl[0];
        }
	}
	
	public static function getOmrader($parent_ns, $acl=0, $force_reload=false) {
        if ($force_reload || !array_key_exists($parent_ns, self::$_omrader)) {
            self::_loadOmrader($parent_ns);
        }
        
        if ($acl > MSAUTH_NONE) {
            $ResultatCol = new Collection();
            foreach (self::$_omrader[$parent_ns] as $objOmrade) {
                if ($objOmrade->checkAcl($acl)) {
                    $ResultatCol->additem($objOmrade, $objOmrade->getOmrade());
                }
            }
            return $ResultatCol;
        } else {
            return self::$_omrader[$parent_ns];
        }
    
    }
    
    public static function OmradeFactory($parent_ns, $omrade) {
        if (!array_key_exists($parent_ns, self::$_omrader)) {
            self::_loadOmrader($parent_ns);
        }
        
        return self::$_omrader[$parent_ns]->getItem($omrade);
    }
    
	private static function _loadOmrader($parent_ns) {
        
		global $conf; // for å få adgang til $conf['savedir']
		
		$parent_ns = cleanID($parent_ns, true);
		$parent_ns = trim($parent_ns, ':');
		$parent_ns = str_replace(':','/',$parent_ns);
		
		// Sjekk at oppgitt parent namespace eksisterer
		if ( @opendir($conf['savedir'].'/pages/'.$parent_ns) === false ) {
			throw new Exception('Hovednamespace for nyheter, ' . $parent_ns .
				' eksisterer ikke. Dette er enten definert feil, eller må opprettes.');
		}
		
		$opts = array(
			'listdirs' => true,
			'skipacl' => true,
			'depth' => 1,
			'keeptxt' => false,
			'listfiles' => false,
			'hash' => false,
			'meta' => false,
			'showmsg' => false,
			'showhidden' => false,
			'firsthead' => false
		);
		$filerfunnet = array();

		search($filerfunnet, $conf['savedir'].'/pages/', 'search_universal', $opts, $parent_ns);
		
		$OmradeCol = new Collection();
		foreach ($filerfunnet as $fil) {
			$objOmrade = new NyhetOmrade($fil['id']);
			$OmradeCol->additem($objOmrade, $objOmrade->getOmrade());
		}
		self::_dbLink($OmradeCol, $parent_ns);
		self::$_omrader[$parent_ns] = $OmradeCol;
	}
    
    protected static function _dbLink(Collection &$colOmrade, $parent_ns, $recursive = false) {
        global $msdb;

        $safeparentns = $msdb->quote($parent_ns);
        $sql= "SELECT omradenavn, visningsnavn, farge, isdefault
                FROM nyheter_omrade
                WHERE parentns = $safeparentns;";
        $res = $msdb->assoc($sql);
        
        foreach($res as $row) {
            if($colOmrade->exists($row['omradenavn'])) {
                $objOmrade = $colOmrade->getItem($row['omradenavn']);
                $objOmrade->setFarge($row['farge']);
                $objOmrade->setVisningsnavn($row['visningsnavn']);
                $objOmrade->setDefault($row['isdefault']);
                $objOmrade->setDbLinked(true);
            }
        }
        
        $any_unsaved = false;
        foreach($colOmrade as $objOmrade) {
            if (!$objOmrade->isDbLinked()) {
                if($recursive) throw new Exeption('Klarte ikke å legge nytt område til i database!');
                $any_unsaved = true;
                
                $safeomrade = $msdb->quote($objOmrade->getOmrade());
                $sql = "INSERT INTO nyheter_omrade SET omradenavn=$safeomrade, parentns = $safeparentns;";
                $msdb->exec($sql);
            }
        }
        
        if ($any_unsaved) return self::_dbLink($colOmrade, $parent_ns, true);
        return true;
        
    }
    
    public static function getVisningsinfoForNyhet(MsNyhet $objNyhet, $parent_ns) {
        if (!array_key_exists($parent_ns, self::$_omrader)) {
            self::_loadOmrader($parent_ns);
        }
        
        $objOmrade = self::$_omrader[$parent_ns]->getItem($objNyhet->getOmrade());
        
        $info['visningsnavn'] = $objOmrade->getVisningsnavn();
        $info['farge'] = $objOmrade->getFarge();
        
        return $info;
    }

}
