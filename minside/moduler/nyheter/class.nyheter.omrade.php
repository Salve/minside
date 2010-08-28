<?php
if(!defined('MS_INC')) die();

class NyhetOmrade {
	
	private $_parent_ns;
	private $_omrade_full;
	private $_omrade;
	
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
		return (auth_quickaclcheck($this->_omrade_full.':*') >= $lvl);
	}
	
	public function getAcl() {
		return auth_quickaclcheck($this->_omrade_full.':*');
	}
	
	public static function getOmrader($parent_ns, $acl = 0) {
	
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
			if ($objOmrade->checkAcl($acl)) {
				$OmradeCol->additem($objOmrade, $objOmrade->getOmrade());
			}
		}
		
		return $OmradeCol;
	}

}
