<?php
if(!defined('MS_INC')) die();
require_once('class.nyheter.msnyhet.php');
require_once('class.nyheter.nyhetcollection.php');

class NyhetFactory {

    const SQL_NYHET_FIELDS =
        '   nyheter_nyhet.nyhetid AS nyhetid,
            nyheter_nyhet.omrade AS omrade,
            nyheter_nyhet.nyhetstype AS nyhetstype,
            nyheter_nyhet.viktighet AS viktighet,
            nyheter_nyhet.createtime AS createtime,
            nyheter_nyhet.modtime AS modtime,
            nyheter_nyhet.deletetime AS deletetime,
            nyheter_nyhet.wikipath AS wikipath,
            nyheter_nyhet.wikihash AS wikihash,
            nyheter_nyhet.nyhettitle AS nyhettitle,
            nyheter_nyhet.imgpath AS imgpath,
            nyheter_nyhet.nyhetbodycache AS nyhetbodycache,
            nyheter_nyhet.pubtime AS pubtime,
			createby.wikifullname AS createby_fullname,
			createby.wikiepost AS createby_epost,
			modby.wikifullname AS modby_fullname,
			modby.wikiepost AS modby_epost,
			deleteby.wikifullname AS deleteby_fullname,
			deleteby.wikiepost AS deleteby_epost
        ';
	
	const SQL_FULLNAME_JOINS =
		'LEFT JOIN internusers AS createby
			ON nyheter_nyhet.createby = createby.id
		LEFT JOIN internusers AS modby
			ON nyheter_nyhet.modby = modby.id
		LEFT JOIN internusers AS deleteby
			ON nyheter_nyhet.deleteby = deleteby.id
		';
	
    private function __construct() { }
    
    public static function getNyhetById($input_nyhetid) {
        global $msdb;
        
        $safeid = $msdb->quote($input_nyhetid);
        $sql = "SELECT " . self::SQL_NYHET_FIELDS . 
			" FROM nyheter_nyhet " . self::SQL_FULLNAME_JOINS .
			" WHERE nyhetid=$safeid LIMIT 1;";
        $res = $msdb->assoc($sql);
        
        return self::createNyhetsobjektFromDbRow($res[0]);
        
    }
    
    public static function getAllePubliserteNyheter() {
        global $msdb;
		
		$omrader = self::getSafeReadOmrader();
        
        $sql = "SELECT " . self::SQL_NYHET_FIELDS . 
			" FROM nyheter_nyhet " . self::SQL_FULLNAME_JOINS .
			" WHERE pubtime < NOW()
				AND nyheter_nyhet.omrade IN ($omrader)
			ORDER BY nyhetid DESC;";
        $res = $msdb->assoc($sql);
        
        return self::createNyhetCollectionFromDbResult($res);
        
    }
    
    public static function getUlesteNyheterForBrukerId($brukerid) {
        global $msdb;
        
		$omrader = self::getSafeReadOmrader();
		
        $safebrukerid = $msdb->quote($brukerid);
        
        $sql = "SELECT " . self::SQL_NYHET_FIELDS . "
                FROM nyheter_nyhet 
                LEFT JOIN nyheter_lest 
                ON nyheter_nyhet.nyhetid = nyheter_lest.nyhetid 
                    AND nyheter_lest.brukerid = $safebrukerid " . 
				self::SQL_FULLNAME_JOINS .
                " WHERE nyheter_lest.nyhetid IS NULL
					AND nyheter_nyhet.omrade IN ($omrader)
					AND pubtime < NOW()
                ORDER BY nyheter_nyhet.nyhetid DESC
            ;";
            
        $res = $msdb->assoc($sql);
        
        return self::createNyhetCollectionFromDbResult($res);
        
    }
    
    public static function getNyhetByWikiPath($input_wikipath) {
        global $msdb;
        
        $safewikipath = $msdb->quote($input_wikipath);
        $sql = "SELECT " . self::SQL_NYHET_FIELDS . 
			" FROM nyheter_nyhet " . self::SQL_FULLNAME_JOINS .
			" WHERE nyheter_nyhet.wikipath=$safewikipath LIMIT 1;";
        $res = $msdb->assoc($sql);
        
        return self::createNyhetsobjektFromDbRow($res[0]);
        
    }
    
    protected static function createNyhetCollectionFromDbResult(array &$result) {
        $objNyhetCol = new NyhetCollection();
        
        foreach ($result as $row) {
            $objNyhet = self::createNyhetsobjektFromDbRow($row);
            $objNyhetCol->addItem($objNyhet, $objNyhet->getId());
        }
        
        return $objNyhetCol;
    }
    
    protected static function createNyhetsobjektFromDbRow(array &$row) {
        
        $objNyhet = new MsNyhet(true, $row['nyhetid']);
        $objNyhet->under_construction = true;
        
        $objNyhet->setType($row['nyhetstype']);
        $objNyhet->setOmrade($row['omrade']);
		$objNyhet->setViktighet($row['viktighet']);
		$objNyhet->setTitle($row['nyhettitle']);
		$objNyhet->setImagePath($row['imgpath']);
		$objNyhet->setHtmlBody($row['nyhetbodycache']);
		$objNyhet->setCreateTime($row['createtime']);
		$objNyhet->setCreateByNavn($row['createby_fullname']);
		$objNyhet->setCreateByEpost($row['createby_epost']);
		$objNyhet->setLastModTime($row['modtime']);
		$objNyhet->setLastModByNavn($row['modby_fullname']);
		$objNyhet->setLastModByEpost($row['modby_epost']);
		$objNyhet->setDeleteTime($row['deletetime']);
		$objNyhet->setDeleteByNavn($row['deleteby_fullname']);
		$objNyhet->setDeleteByEpost($row['deleteby_epost']);
		$objNyhet->setWikiPath($row['wikipath']);
		$objNyhet->setWikiHash($row['wikihash']);
		$objNyhet->setPublishTime($row['pubtime']);
        
        $objNyhet->under_construction = false;
        
        return $objNyhet;
        
    }
    
	protected static function getSafeReadOmrader() {
		global $msdb;
		
		$colOmrader = NyhetOmrade::getOmrader('msnyheter', AUTH_READ);
		$arOmrader = array();
		foreach ($colOmrader as $objOmrade) {
			$arOmrader[] = $msdb->quote($objOmrade->getOmrade());
		}
		$omrader = implode(',', $arOmrader);
		return ($omrader) ?: "''";
	}
	
}
