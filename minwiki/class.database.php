<?php
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
	
	public function query($sqlstring) {
	
	if ($result = $this->hConn->query("$sqlstring")) {
	
	
	
	}
	
	}
	
	public function __destruct() {
	
		$this->hConn = null;
		
	}

}