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
			$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
			return $result;
		} else {
			die('sqlfailed');
		}
	}
	
	public function num($sqlstring) {
	
		if ($stmt = $this->hConn->query("$sqlstring")) {
			$result = $stmt->fetchAll(PDO::FETCH_NUM);
			return $result;
		} else {
			die("sqlquery failed on: $sqlstring");
		}
	}
	
	public function exec($sqlstring) {
	
		$result = $this->hConn->exec("$sqlstring");
	
		if ($result === false) {
			die("sqlexec failed on: $sqlstring");
		} else {
			return $result;
		}
	
	}
	
	public function quote($inputstring) {
		
		return $this->hConn->quote($inputstring);
		
	}
	
	public function __destruct() {
	
		$this->hConn = null;
		
	}

}
