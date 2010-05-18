<?php
if(!defined('DOKU_INC')) die();
require_once('mwconfig.php');
require_once('class.database.php');
require_once('class.mwmodul_base.php');
require_once('class.mwdispatcher.php');

class minwiki {

public $mwdb;
public $mwmod = array();
private $UserID;
private $username;

	public function __construct($username) {
	
		try {
			$mwdb = new Database();
		} catch(Exception $e) {
			echo $e->getMessage(), '<br />';
		}
		
		$this->username = $username;
		
	}
	
	public function gen_minwiki() {
	
		$mwoutput = 'Velkommen til Min Wiki!<br />';
		
		$this->_lastmoduler();
		$mwdisp = new mwdispatcher('feilmrapport', $this->mwmod, 'show', NULL);
		
		$mwoutput .= $mwdisp->dispatch();
		return $mwoutput;
		
		
	}
	
	private function _lastmoduler() {
		
		foreach (mwcfg::$moduler as $modulnavn) {
			require_once 'moduler/' . $modulnavn . '.mwmodul.php';
			$mwclassnavn = 'mwmodul_' . $modulnavn;
			$this->mwmod[$modulnavn] = new $mwclassnavn;
		}
	
	}
	
	public function getUserID() {
		if (!isset($this->username)) { die('Username not set on minwiki create'); }
	
	}
	

}