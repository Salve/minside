<?php
require_once('mwconfig.php');

class Database {

	private $hConn;

	public function __construct() {
		
		$this->hConn = new mysqli(mwcfg::$db['host'], mwcfg::$db['user'], mwcfg::$db['password'], mwcfg::$db['name']);

		if (mysqli_connect_errno()) {
			throw new Exception("En feil har oppstått ved oppkobling mot database: " . mysqli_connect_error(), E_USER_ERROR);
		}
		
	}
	
	public function __destruct() {
	
		$this->hConn->close();
		
	}

}