<?php
if(!defined('MS_INC')) die();

class NyhetOmrade {

    private static $_omrader = array();
	
	private $_parent_ns;
	private $_omrade_full;
	private $_omrade;
    private $_acl;
	
	public function __construct($omrade) {
		$this->_omrade_full = $omrade;
		$this->_omrade = noNS($omrade);
		$this->_parent_ns = getNS($omrade);
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
	
	public function checkAcl($lvl) {
		return ($this->getAcl() >= $lvl);
	}
	
	public function getAcl() {
        if (!isset($this->_acl)) {
            $this->_acl = auth_quickaclcheck($this->_omrade_full.':*');
        }
		return $this->_acl;
	}
	
	public static function getOmrader($parent_ns, $acl = 0) {
        if (!array_key_exists($parent_ns, self::$_omrader)) {
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
		
		self::$_omrader[$parent_ns] = $OmradeCol;
	}

}
