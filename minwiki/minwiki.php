<?php
if(!defined('DOKU_INC')) die();
define('MW_INC', true);
define(MW_LINK, "?do=minwiki");
define('MWAUTH_NONE',0);
define('MWAUTH_1',1);
define('MWAUTH_2',2);
define('MWAUTH_3',4);
define('MWAUTH_4',8);
define('MWAUTH_5',16);
define('MWAUTH_ADMIN',255);

require_once('mwconfig.php');
require_once('class.database.php');
require_once('interface.mwmodul.php');
require_once('class.mwdispatcher.php');
require_once('class.collectioniterator.php');
require_once('class.collection.php');
require_once('class.menyitem.php');
require_once('class.menyitemcollection.php');


class minwiki {

private $mwmod = array();
private $UserID;
private $username;

	public function __construct($username) {
	
		try {
			$GLOBALS['mwdb'] = new Database();
		} catch(Exception $e) {
			die($e->getMessage());
		}
		$this->username = $username;
		
	}
	
	public function gen_minwiki() {
		$this->_lastmoduler();
		$mwoutput .= '<div class="minwiki">';
		
		$mwoutput .= $this->_genMenu();
		
		$mwoutput .= 'Output fra minwiki! Navn: ' . $this->username . ' ID: ' . $this->getUserID() . '<br />';
		
		if(array_key_exists('page', $_REQUEST)) {
			$page = $_REQUEST['page'];
		} else {
			$page = 'feilmrapport';
		}
		
		if(array_key_exists('act', $_REQUEST)) {
			$act = $_REQUEST['act'];
		} else {
			$act = 'show';
		}
		
		$mwdisp = new mwdispatcher($page, $this->mwmod, $this, $act, NULL);
		$mwoutput .= $mwdisp->dispatch();
		
		
		
		$mwdisp = new mwdispatcher('testmodul', $this->mwmod, $this, 'show', NULL);
		$mwoutput .= $mwdisp->dispatch();
		
		
		
		
		$mwoutput .= '</div>';
		return $mwoutput;
		
		
	}
	
	private function _lastmoduler() {
		
		foreach (mwcfg::$moduler as $modulnavn) {
			require_once 'moduler/' . $modulnavn . '/' . $modulnavn . '.mwmodul.php';
			$mwclassnavn = 'mwmodul_' . $modulnavn;
			$this->mwmod[$modulnavn] = new $mwclassnavn($this->getUserID(), $this->sjekkAdgang($modulnavn));
		}
	
	}
	
	public function getUserID() {
		global $mwdb;
		
		if (!isset($this->username)) { die('Username not set on minwiki create'); }
			
		if (isset($this->UserID)) {
			return $this->UserID;
		} else {
			$result = $mwdb->assoc('SELECT id FROM internusers WHERE wikiname = ' . $mwdb->quote($this->username) . ' LIMIT 1;');
			$this->UserID = $result[0]['id'];
			return $this->UserID;
		}

	}
	
	private function _genMenu() {
	
		$meny = new MenyitemCollection();
		
		foreach ($this->mwmod as $mwmod) {
			$mwmod->registrer_meny($meny);
		}
	
		$output .= '<div class="toc">';
		$output .= '<div class="tocheader toctoggle" id="toc__header">Min Wiki Meny</div>';
		$output .= '<div id="toc__inside">';
		$output .= '<ul class="toc">';
		foreach ($meny as $menyitem) {
			$output .= '<li class="level1">';
			$output .= '<div class="li"><span class="li"><a href="' . MW_LINK . $menyitem->getHref() . '" class="toc">' . $menyitem->getTekst() . '</a></span></div>';
			$output .= '</li>';
			if ($menyitem->hasChildren()) {
				$output .= '<ul class="toc">';
				foreach ($menyitem->getChildren() as $undermenyitem) {
					$output .= '<li class="level2">';
					$output .= '<div class="li"><span class="li"><a href="' . MW_LINK . $undermenyitem->getHref() . '" class="toc">' . $undermenyitem->getTekst() . '</a></span></div>';
					$output .= '</li>';
					if ($undermenyitem->hasChildren()) {
						$output .= '<ul class="toc">';
						foreach ($undermenyitem->getChildren() as $bunnmenyitem) {
							$output .= '<li class="level2">';
							$output .= '<div class="li"><span class="li"><a href="' . MW_LINK . $bunnmenyitem->getHref() . '" class="toc">' . $bunnmenyitem->getTekst() . '</a></span></div>';
							$output .= '</li>';
						}
						$output .= '</ul>';
					}
				}
				$output .= '</ul>';
			}
		}
		$output .= '</ul>';
		$output .= '</div>';
		$output .= '</div>';
		
		$output .= '';
		
		return $output;
	}
	
	public function sjekkAdgang($modul = '') {
	
		$id = 'mwauth:' . $modul . ':info';
		// echo 'Sjekker adgang til: ', $id, '. Adgangsnivå er: ', auth_quickaclcheck($id), '<br />'; // Debug.
		return auth_quickaclcheck($id);	
	
	}
	

}
