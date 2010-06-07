<?php
if(!defined('MS_INC')) die();

class SkiftFactory {
	public static function getSkift($id) {
		global $msdb;
		
		$sql = "SELECT feilrap_skift.skiftcreated, feilrap_skift.userid, feilrap_skift.skiftclosed, feilrap_skift.israpportert, internusers.wikiname FROM feilrap_skift LEFT JOIN internusers ON feilrap_skift.userid = internusers.id WHERE skiftid = " . $msdb->quote($id) . ";";
		$data = $msdb->assoc($sql);
		
		if(is_array($data) && sizeof($data)) {
			$objSkift = new Skift($id, $data[0]['skiftcreated'], $data[0]['userid'], $data[0]['skiftclosed']);
			$objSkift->setSkiftOwnerName($data[0]['wikiname']);
			return $objSkift;
		} else {
			throw new Exception("Skift med id: $id finnes ikke i database");
		}
		
	}
	
	public static function getRapport($rapportid) {
		global $msdb;
		
		$saferapportid = $msdb->quote($rapportid);
		
		$sql = "SELECT createtime, issent, rapportfratid, rapporttiltid, rapportowner FROM feilrap_rapport WHERE rapportid=$saferapportid LIMIT 1;";
		$data = $msdb->assoc($sql);
		
		if(is_array($data) && sizeof($data)) {
			return new Skift($rapportid, $data[0]['createtime'], $data[0]['userid'], $data[0]['rapportfratid'], $data[0]['rapporttiltid'], true);
		} else {
			throw new Exception("Rapport med id: $rapportid finnes ikke i database");
		}
		
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
			die("Klarte ikke å laste tellerid: $tellerid for skift: $skiftid.");
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
		
		$sql = "SELECT skiftid FROM feilrap_skift WHERE skiftcreated > (now() - INTERVAL 14 HOUR)";
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
		$sql = "SELECT feilrap_teller.tellerid, feilrap_teller.tellertype, feilrap_teller.tellernavn, feilrap_teller.tellerdesc, SUM(IF(feilrap_tellerakt.skiftid=$id,feilrap_tellerakt.verdi,0)) AS 'tellerverdi' FROM feilrap_teller LEFT JOIN feilrap_tellerakt ON feilrap_teller.tellerid = feilrap_tellerakt.tellerid WHERE feilrap_teller.isactive=1 GROUP BY feilrap_teller.tellerid;";
		
		$data = $msdb->assoc($sql);
		
		if(is_array($data) && sizeof($data)) {
			foreach($data as $datum) {
				$objTeller = new Teller($datum['tellerid'], $id, $datum['tellernavn'], $datum['tellerdesc'], $datum['tellertype'], $datum['tellerverdi']);
				$col->addItem($objTeller, $datum['tellernavn']);
			}
		}
	
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
