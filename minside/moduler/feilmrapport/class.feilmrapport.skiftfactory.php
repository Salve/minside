<?php
if(!defined('MS_INC')) die();

class SkiftFactory {
	public static function getSkift($id) {
		global $msdb;
		
		$sql = "SELECT feilrap_skift.skiftcreated, feilrap_skift.userid, feilrap_skift.skiftclosed, feilrap_skift.israpportert, feilrap_skift.rapportid, internusers.wikiname FROM feilrap_skift LEFT JOIN internusers ON feilrap_skift.userid = internusers.id WHERE skiftid = " . $msdb->quote($id) . ";";
		$data = $msdb->assoc($sql);
		
		if(is_array($data) && sizeof($data)) {
			$objSkift = new Skift($id, $data[0]['skiftcreated'], $data[0]['userid'], $data[0]['skiftclosed'], $data[0]['israpportert'], $data[0]['rapportid']);
			$objSkift->setSkiftOwnerName($data[0]['wikiname']);
			return $objSkift;
		} else {
			throw new Exception("Skift med id: $id finnes ikke i database");
		}
		
	}
	
	public static function getRapport($rapportid) {
		global $msdb;
		
		$saferapportid = $msdb->quote($rapportid);
		
		$sql = "SELECT rapportid, createtime, rapportowner, templateid FROM feilrap_rapport WHERE rapportid=$saferapportid LIMIT 1;";
		$data = $msdb->assoc($sql);
		
		if(is_array($data) && sizeof($data)) {
			return new Rapport($data[0]['rapportowner'], $data[0]['rapportid'], $data[0]['createtime'], true, $data[0]['templateid']);
		} else {
			throw new Exception("Rapport med id: $saferapportid finnes ikke i database");
		}
		
	}
	
	public static function getRapporter($fromtime = null, $totime = null) {
		global $msdb;
		
		if ($fromtime && $totime) {
			$safefromtime = $msdb->quote($fromtime);
			$safetotime = $msdb->quote($totime);
			$where = " WHERE feilrap_rapport.createtime >= $safefromtime AND feilrap_rapport.createtime <= $safetotime";
		} elseif ($fromtime) {
			$safefromtime = $msdb->quote($fromtime);
			$where = " WHERE feilrap_rapport.createtime >= $safefromtime";
		} elseif ($totime) {
			$safetotime = $msdb->quote($totime);
			$where = " WHERE feilrap_rapport.createtime <= $safetotime";
		} else {
			$where = '';
		}
		
		$sql = "SELECT feilrap_rapport.rapportid, feilrap_rapport.createtime, feilrap_rapport.rapportowner, feilrap_rapport.templateid, internusers.wikiname FROM feilrap_rapport LEFT JOIN internusers ON feilrap_rapport.rapportowner = internusers.id" . $where . ";";
		
		
		$data = $msdb->assoc($sql);
		
		$col = new RapportCollection();
		
		if(is_array($data) && sizeof($data)) {
			foreach ($data as $datum) {
				$objRapport = new Rapport($datum['rapportowner'], $datum['rapportid'], $datum['createtime'], true, $datum['templateid'], $datum['wikiname']);
				$col->addItem($objRapport, $datum['rapportid']);
			}
		}
		
		return $col;
	
	}
	
	public static function getRapporterByMonth($inputmonth, $inputyear = null) {
		
		if (!$inputyear) $inputyear = date('Y');
		
		$fromtime = mktime(0, 0, 0, $inputmonth, 1, $inputyear); // returnerer første dag i gitt måned
		$totime = mktime(23, 59, 59, $inputmonth + 1, 0, $inputyear); // returnerer siste dag i gitt måned (nullte dag i neste måned). måned 13 er ok input...
		
		$sqlfromtime = date('Y-m-d H:i:s', $fromtime);
		$sqltotime = date('Y-m-d H:i:s', $totime);
		
		
		return self::getRapporter($sqlfromtime, $sqltotime);
	
	}
	
	public static function getNyligeRapporter() {
		global $msdb;

		$fromtime = time() - (24 * 60 * 60); // Definer hvor langt tilbake "nylig" er her (i sekunder).
		$sqlfromtime = date('Y-m-d H:i:s', $fromtime);
		$sqltotime = date('Y-m-d H:i:s');
		
		return self::getRapporter($sqlfromtime, $sqltotime);
	
	}
	
	public static function getDataForRapport($rapportid) {
		global $msdb;
		
		$saferapportid = $msdb->quote($rapportid);
		$outputarray = array();
		
		$sql = "SELECT datatype, dataname, datavalue FROM feilrap_rapportdata WHERE rapportid=$saferapportid;";
		$data = $msdb->assoc($sql);
	
		if(is_array($data) && sizeof($data)) {
			foreach ($data as $row) {
				$type = $row['datatype'];
				$name = $row['dataname'];
				$value = $row['datavalue'];
				$outputarray["$type"]["$name"] = $value;
			}
		}
		
		return $outputarray;
	
	}
	
	public static function getTeller($tellerid, $skiftid) {
		global $msdb;
		$safeskiftid = $msdb->quote($skiftid);
		$safetellerid = $msdb->quote($tellerid);
		
		$sql = "SELECT feilrap_teller.tellertype, feilrap_teller.tellernavn, feilrap_teller.tellerdesc, SUM(IF(feilrap_tellerakt.skiftid=$safeskiftid,feilrap_tellerakt.verdi,0)) AS 'tellerverdi', feilrap_teller.isactive FROM feilrap_teller LEFT JOIN feilrap_tellerakt ON feilrap_teller.tellerid = feilrap_tellerakt.tellerid WHERE feilrap_teller.tellerid=$safetellerid GROUP BY feilrap_teller.tellerid LIMIT 1;";
		
		$data = $msdb->assoc($sql);
		
		if(is_array($data) && sizeof($data)) {
			$objTeller = new Teller($tellerid, $skiftid, $data[0]['tellernavn'], $data[0]['tellerdesc'], $data[0]['tellertype'], $data[0]['tellerverdi'], $data[0]['isactive']);
			return $objTeller;
		} else {
			die("Klarte ikke å laste tellerid: $safetellerid for skift: $safeskiftid.");
		}
	
	
	}
	
	public static function getSkiftForRapport($rapportid, &$col) {
		global $msdb;
		
		$saferapportid = $msdb->quote($rapportid);
		
		$sql = "SELECT skiftid FROM feilrap_skift WHERE rapportid=$saferapportid;";
		$data = $msdb->assoc($sql);
		
		if(is_array($data) && sizeof($data)) {
			foreach ($data as $datum) {
				$objSkift = self::getSkift($datum['skiftid']);
				$col->addItem($objSkift);
			}
		}
	}
	
	public static function getMuligeSkiftForRapport() {
		global $msdb;
		
		$sql = "SELECT skiftid FROM feilrap_skift WHERE (skiftcreated > (now() - INTERVAL 48 HOUR)) AND (israpportert = 0)";
		$data = $msdb->assoc($sql);
		
		$col = new SkiftCollection();
		
		if(is_array($data) && sizeof($data)) {
			foreach ($data as $datum) {
				$objSkift = self::getSkift($datum['skiftid']);
				$col->addItem($objSkift);
			}
		}
		
		return $col;
		
	}

	public static function getTellereForSkift($id, &$col) {
		global $msdb;
		$id = $msdb->quote($id);
		$sql = "SELECT feilrap_teller.tellerid, feilrap_teller.tellertype, feilrap_teller.isactive, feilrap_teller.tellernavn, feilrap_teller.tellerdesc, SUM(IF(feilrap_tellerakt.skiftid=$id,feilrap_tellerakt.verdi,0)) AS 'tellerverdi' FROM feilrap_teller LEFT JOIN feilrap_tellerakt ON feilrap_teller.tellerid = feilrap_tellerakt.tellerid GROUP BY feilrap_teller.tellerid;";
		
		$data = $msdb->assoc($sql);
		
		if(is_array($data) && sizeof($data)) {
			foreach($data as $datum) {
				$isactive = (bool) $datum['isactive'];
				$objTeller = new Teller($datum['tellerid'], $id, $datum['tellernavn'], $datum['tellerdesc'], $datum['tellertype'], $datum['tellerverdi'], $isactive);
				$col->addItem($objTeller, $datum['tellernavn']);
			}
		}
	
	}
	
	public static function getAlleTellere() {
		global $msdb;
		
		$col = new TellerCollection;
		
		$sql = "SELECT tellerid, tellertype, tellernavn, tellerdesc, isactive, tellerorder FROM feilrap_teller ORDER BY tellerorder ASC;";
		$data = $msdb->assoc($sql);
		
		if(is_array($data) && sizeof($data)) {
			foreach($data as $datum) {
				$objTeller = new Teller($datum['tellerid'], 0, $datum['tellernavn'], $datum['tellerdesc'], $datum['tellertype'], 0, (bool) $datum['isactive']);
				$objTeller->setOrder($datum['tellerorder']);
				$col->addItem($objTeller);
			}
		}
		
		return $col;
	
	}
	
	public static function getNotat($notatid) {
		global $msdb;
		$safenotatid = $msdb->quote($notatid);
		
		$sql = "SELECT skiftid, isactive, notattype, notattekst, inrapport FROM feilrap_notat WHERE notatid=$safenotatid;";
		
		$data = $msdb->assoc($sql);
		
		if(is_array($data) && sizeof($data)) {
			foreach($data as $datum) {		
				$rapportert = ($datum['inrapport'] == 1) ? true : false;
				$active = ($datum['isactive'] == 1) ? true : false;
								
				$objNotat = new Notat($notatid, $datum['skiftid'], $datum['notattype'], $datum['notattekst'], true, $active, $rapportert);
				
				return $objNotat;
			}
		}	
	}
	
	public static function getNotaterForSkift($skiftid, &$col) {
		global $msdb;
		$safeskiftid = $msdb->quote($skiftid);
		$sql = "SELECT notatid, isactive, notattype, notattekst, inrapport FROM feilrap_notat WHERE skiftid=$safeskiftid;";
		
		$data = $msdb->assoc($sql);
		
		if(is_array($data) && sizeof($data)) {
			foreach($data as $datum) {
				$rapportert = ($datum['inrapport'] == 1) ? true : false;
				$active = ($datum['isactive'] == 1) ? true : false;

				$objNotat = new Notat($datum['notatid'], $skiftid, $datum['notattype'], $datum['notattekst'], true, $active, $rapportert);
				$col->addItem($objNotat, $datum['notatid']);
			}
		}
	}
	
}
