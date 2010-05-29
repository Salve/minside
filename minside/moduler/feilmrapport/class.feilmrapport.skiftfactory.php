<?php
if(!defined('MS_INC')) die();

class SkiftFactory {
	public static function getSkift($id) {
		global $msdb;
		
		$sql = "SELECT skiftcreated, userid, skiftclosed FROM feilrap_skift WHERE skiftid = " . $msdb->quote($id) . ";";
		$data = $msdb->assoc($sql);
		
		if(is_array($data) && sizeof($data)) {
			return new Skift($id, $data[0]['skiftcreated'], $data[0]['userid'], $data[0]['skiftclosed']);
		} else {
			throw new Exception("Skift med id: $id finnes ikke i database");
		}
		
	}
	
	public static function getTeller($tellerid, $skiftid) {
		global $msdb;
		$safeskiftid = $msdb->quote($skiftid);
		$safetellerid = $msdb->quote($tellerid);
		
		$sql = "SELECT feilrap_teller.tellertype, feilrap_teller.tellerdesc, SUM(IF(feilrap_tellerakt.skiftid=$safeskiftid,feilrap_tellerakt.verdi,0)) AS 'tellerverdi', feilrap_teller.isactive FROM feilrap_teller LEFT JOIN feilrap_tellerakt ON feilrap_teller.tellerid = feilrap_tellerakt.tellerid WHERE feilrap_teller.tellerid=$safetellerid LIMIT 1;";
		
		$data = $msdb->assoc($sql);
		
		if(is_array($data) && sizeof($data)) {
			$objTeller = new Teller($tellerid, $skiftid, $data[0]['tellerdesc'], $data[0]['tellertype'], $data[0]['tellerverdi'], $data[0]['isactive']);
			return $objTeller;
		} else {
			die("Klarte ikke å laste tellerid: $tellerid for skift: $skiftid.");
		}
	
	
	}

	public static function getTellereForSkift($id, $col) {
		global $msdb;
		$id = $msdb->quote($id);
		$sql = "SELECT feilrap_teller.tellerid, feilrap_teller.tellertype, feilrap_teller.tellerdesc, SUM(IF(feilrap_tellerakt.skiftid=$id,feilrap_tellerakt.verdi,0)) AS 'tellerverdi' FROM feilrap_teller LEFT JOIN feilrap_tellerakt ON feilrap_teller.tellerid = feilrap_tellerakt.tellerid WHERE feilrap_teller.isactive=1 GROUP BY feilrap_teller.tellerid;";
		
		$data = $msdb->assoc($sql);
		
		if(is_array($data) && sizeof($data)) {
			foreach($data as $datum) {
			$objTeller = new Teller($datum['tellerid'], $id, $datum['tellerdesc'], $datum['tellertype'], $datum['tellerverdi']);
			$col->addItem($objTeller);
			}
		}
	
	}
	
}
