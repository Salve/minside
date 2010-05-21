<?php
if(!defined('DOKU_INC')) die();
define('MW_INC', true);
require_once('mwconfig.php');
require_once('class.database.php');
require_once('interface.mwmodul.php');
require_once('class.mwdispatcher.php');
require_once('class.collectioniterator.php');
require_once('class.collection.php');


class minwiki {

private $mwdb;
private $mwmod = array();
private $UserID;
private $username;

	public function __construct($username) {
	
		try {
			$this->mwdb = new Database();
		} catch(Exception $e) {
			echo $e->getMessage(), '<br />';
		}
		$this->username = $username;
		
	}
	
	public function gen_minwiki() {
		$this->_lastmoduler();
		
		$mwoutput .= 'Velkommen til Min Wiki!<br />';
		$mwoutput .= 'Navn: ' . $this->username . ' ID: ' . $this->getUserID() . '<br />';
		
		
		$mwdisp = new mwdispatcher('feilmrapport', $this->mwmod, 'show', NULL);
		$mwoutput .= $mwdisp->dispatch();
		
		
		/*
		$mwdisp = new mwdispatcher('testmodul', $this->mwmod, 'show', NULL);
		$mwoutput .= $mwdisp->dispatch();
		*/
		return $mwoutput;
		
		
	}
	
	private function _lastmoduler() {
		
		foreach (mwcfg::$moduler as $modulnavn) {
			require_once 'moduler/' . $modulnavn . '/' . $modulnavn . '.mwmodul.php';
			$mwclassnavn = 'mwmodul_' . $modulnavn;
			$this->mwmod[$modulnavn] = new $mwclassnavn($this->getUserID(), $this->mwdb);
		}
	
	}
	
	public function getUserID() {
		if (!isset($this->username)) { die('Username not set on minwiki create'); }
			
		if (isset($this->UserID)) {
			return $this->UserID;
		} else {
			$result = $this->mwdb->assoc('SELECT id FROM internusers WHERE wikiname = "' . $this->username . '";');
			$this->UserID = $result['id'];
			return $this->UserID;
		}

	}
	

}