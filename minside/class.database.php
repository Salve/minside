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
			$this->hConn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		}
		catch(PDOException $e) {
			throw new Exception("En feil har oppstått ved oppkobling mot database: " . $e->getMessage(), E_USER_ERROR);
		}
				
		
	}
	
	public function assoc($sqlstring, $fetchone = false) {
		$db_starttime = microtime(true);
		
		try {
			$stmt = $this->hConn->prepare("$sqlstring");
			$stmt->execute();
		} catch (PDOException $e) {
			die($e->getMessage() . ' Sqlstring: ' . $sqlstring);
		}
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
	}
	
	public function num($sqlstring, $fetchone = false) {
		$db_starttime = microtime(true);
		
		try {
			$stmt = $this->hConn->prepare("$sqlstring");
			$stmt->execute();
		} catch (PDOException $e) {
			die($e->getMessage() . ' Sqlstring: ' . $sqlstring);
		}
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
	}
	
	public function exec($sqlstring) {
		$db_starttime = microtime(true);
		
		try {
			$result = $this->hConn->exec("$sqlstring");
		} catch (PDOException $e) {
			die($e->getMessage() . ' Sqlstring: ' . $sqlstring);
		}
		
		$db_endtime = microtime(true);
		
		if ($this->debug) msg($sqlstring . ' : ' . round(($db_endtime - $db_starttime), 5));
		$this->querytime += ($db_endtime - $db_starttime);
		$this->num_queries++;
		
		return $result;	
	}
	
	public function quote($inputstring) {
		
		return $this->hConn->quote($inputstring);
		
	}
	
	public function getLastInsertId() {
	
		return $this->hConn->lastInsertId();
	
	}
	
	public function __destruct() {
	
		$this->hConn = null;
		
	}
	
	public function getHandle() {
		return $this->hConn;
	}
	
	public function startTrans() {
		$this->hConn->beginTransaction();
		if ($this->debug) msg('Starter transaction', 2);
	}
	
	public function commit() {
		$this->hConn->commit();
		if ($this->debug) msg('Comitter transaction', 1);
	}
	
	public function rollBack() {
		$this->hConn->rollBack();
		if ($this->debug) msg('Rollback transaction', -1);
	}

}
