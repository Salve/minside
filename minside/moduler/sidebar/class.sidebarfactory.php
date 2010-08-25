<?php
if(!defined('MS_INC')) die();

class SidebarFactory {

	const DB_COLS = 'blokkid, blokknavn, blokkurl, blokktype, blokkorder, blokkacl ';

	public static function getSidebar() {
		global $msdb;
		
		$sql = "SELECT " . SidebarFactory::DB_COLS . " FROM sidebar_blokk;";
		
		$result = $msdb->assoc($sql);
		
		$objSidebar = new MenyitemCollection();
		
		foreach ($result as $row) {
			$objMenyitem = self::createMenyitem($row);
			$objSidebar->addItem($objMenyitem, $objMenyitem->getId());
		}
		
		return $objSidebar;
		
	}
	
	public static function getBlokkById($inputid) {
		global $msdb;
		
		$safeid = $msdb->quote($inputid);
		$sql = "SELECT " . SidebarFactory::DB_COLS . " FROM sidebar_blokk WHERE blokkid=$safeid LIMIT 1;";
		$result = $msdb->assoc($sql);
		
		if (is_array($result) && sizeof($result)) {
			return self::createMenyitem($result[0]);
		} else {
			throw new Exception('Finner ikke forespurt blokk i database.');
		}
	}
	
	private static function createMenyitem($dbrow) {
		$objMenyitem = new Menyitem(
			$dbrow['blokknavn'],
			$dbrow['blokkurl'],
			$dbrow['blokkacl'],
			$dbrow['blokktype']
		);
		$objMenyitem->setSaved(
			$dbrow['blokkid'],
			$dbrow['blokkorder']
		);
		
		return $objMenyitem;
	}

}
