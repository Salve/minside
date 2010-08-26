<?php
if(!defined('MS_INC')) die();

class NyhetOmrade {

	public static function getOmrader($parent_ns) {
	
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
		
		$ns_liste = array();
		foreach ($filerfunnet as $fil) {
			$ns_liste[] = noNS($fil['id']);
		}
		
		return $ns_liste;
	}

}
