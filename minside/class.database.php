<?php
if(!defined('MS_INC')) die();
require_once('msconfig.php');

class Database {

	private $hConn;
	public $num_queries;
	public $querytime;
	private $debug = false; // sett til true for å vise sql-streng og tid brukt for hver sql-spørring som kjøres

	public function __construct() {
		
		try {
			$this->hConn = new PDO('mysql:host=' . mscfg::$db['host'] . ';dbname=' . mscfg::$db['name'], mscfg::$db['user'], mscfg::$db['password']);
		}
		catch(PDOException $e) {
			throw new Exception("En feil har oppstått ved oppkobling mot database: " . $e->getMessage(), E_USER_ERROR);
		}
				
		
	}
	
	public function assoc($sqlstring, $fetchone = false) {
		try {
			$db_starttime = microtime(true);
			$stmt = $this->hConn->query("$sqlstring");
			if ($fetchone) {
				$result = $stmt->fetch(PDO::FETCH_ASSOC);
			} else {
				$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
			}
			$db_endtime = microtime(true);
			if ($this->debug) msg($sqlstring . ' : ' . round(($db_endtime - $db_starttime), 5));
			$this->querytime += ($db_endtime - $db_starttime);
			$this->num_queries++;
			return $result;
		} catch (PDOException $e) {
			die($e->getMessage() . ' Sqlstring: ' . $sqlstring);
		}
	}
	
	public function num($sqlstring, $fetchone = false) {
		try {
			$db_starttime = microtime(true);
			$stmt = $this->hConn->query("$sqlstring");
			if ($fetchone) {
				$result = $stmt->fetch(PDO::FETCH_NUM);	
			} else {
				$result = $stmt->fetchAll(PDO::FETCH_NUM);
			}
			$db_endtime = microtime(true);
			if ($this->debug) msg($sqlstring . ' : ' . round(($db_endtime - $db_starttime), 5));
			$this->querytime += $db_endtime - $db_starttime;
			$this->num_queries++;
			return $result;
		} catch (PDOException $e) {
			die($e->getMessage() . ' Sqlstring: ' . $sqlstring);
		}
	}
	
	public function exec($sqlstring) {
		try {
			$db_starttime = microtime(true);
			$result = $this->hConn->exec("$sqlstring");
			$db_endtime = microtime(true);
			if ($this->debug) msg($sqlstring . ' : ' . round(($db_endtime - $db_starttime), 5));
			$this->querytime += ($db_endtime - $db_starttime);
			$this->num_queries++;
			return $result;	
		} catch (PDOException $e) {
			die($e->getMessage() . ' Sqlstring: ' . $sqlstring);
		}
	}
	
	public function quote($inputstring) {
		
		return $this->hConn->quote($inputstring);
		
	}
	
	public function __destruct() {
	
		$this->hConn = null;
		
	}
	
	public function getHandle() {
		return $this->hConn;
	}
	

}
