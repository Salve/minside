<?php
if(!defined('MW_INC')) die();
require_once('mwconfig.php');

class Database {

	private $hConn;

	public function __construct() {
		
		try {
			$this->hConn = new PDO('mysql:host=' . mwcfg::$db['host'] . ';dbname=' . mwcfg::$db['name'], mwcfg::$db['user'], mwcfg::$db['password']);
		}
		catch(PDOException $e) {
			throw new Exception("En feil har oppstått ved oppkobling mot database: " . $e->getMessage(), E_USER_ERROR);
		}
				
		
	}
	
	public function assoc($sqlstring) {
	
		if ($stmt = $this->hConn->query("$sqlstring")) {
			$result = $stmt->fetch(PDO::FETCH_ASSOC);
			return $result;
		} else {
			die('sqlfailed');
		}
	}
	
	public function num($sqlstring) {
	
		if ($stmt = $this->hConn->query("$sqlstring")) {
			$result = $stmt->fetch(PDO::FETCH_NUM);
			return $result;
		} else {
			die('sqlfailed');
		}
	}
	
	public function exec($sqlstring) {
	
		die('exec ikke implementert enda!');
	
	}
	
	public function __destruct() {
	
		$this->hConn = null;
		
	}

}