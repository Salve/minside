<?php
require_once('mwconfig.php');
require_once('class.database.php');
require_once('class.mwmodul_base.php');
require_once('class.mwdispatcher.php');

class minwiki {

public $mwdb;
public $mwmod = array();

	public function __construct() {
	
		try {
			$mwdb = new Database();
		} catch(Exception $e) {
			echo $e->getMessage(), '<br />';
		}
		
	}
	
	public function gen_minwiki() {
	
		$mwoutput = 'Velkommen til Min Wiki!<br />';
		
		$this->lastmoduler();
		$mwdisp = new mwdispatcher('feilmrapport', $this->mwmod, 'show', NULL);
		
		$mwoutput .= $mwdisp->dispatch();
		return $mwoutput;
		
		
	}
	
	private function lastmoduler() {
		
		foreach (mwcfg::$moduler as $modulnavn) {
			require_once 'moduler/' . $modulnavn . '.mwmodul.php';
			$mwclassnavn = 'mwmodul_' . $modulnavn;
			$this->mwmod[$modulnavn] = new $mwclassnavn;
		}
	
	}
	
	

}