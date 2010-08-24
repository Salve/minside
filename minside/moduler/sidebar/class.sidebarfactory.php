<?php
if(!defined('MS_INC')) die();

class SidebarFactory {
	public static function getSidebar() {
		global $msdb;
		
		$sql = "SELECT 
					blokkid,
					blokknavn,
					blokkurl,
					blokktype,
					blokkorder,
					blokkacl
				FROM sidebar_blokk;";
		
		$result = $msdb->assoc($sql);
		
		$objSidebar = new MenyitemCollection();
		
		foreach ($result as $row) {
			$objMenyitem = self::createMenyitem($row);
			$objSidebar->addItem($objMenyitem, $objMenyitem->getId());
		}
		
		return $objSidebar;
		
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
