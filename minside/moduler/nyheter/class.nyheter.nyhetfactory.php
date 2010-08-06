<?php
if(!defined('MS_INC')) die();
require_once('class.nyheter.msnyhet.php');
require_once('class.nyheter.nyhetcollection.php');

class NyhetFactory {

    const SQL_NYHET_FIELDS =
        'nyhetid, tilgangsgrupper, nyhetstype, viktighet, createtime, modtime, deletetime, wikipath, wikihash, nyhettitle, imgpath, nyhetbodycache';

    private function __construct() { }
    
    public static function getTestNyhet() {
    
        $objNyhet = new MsNyhet();
        
		$objNyhet->setType(1);
		$objNyhet->setTilgang('feilm');
		$objNyhet->setViktighet(2);
		$objNyhet->setTitle('En liten testnyhet');
		$objNyhet->setHtmlBody(nl2br(
            '<strong>Her er nyheten!</strong> Lorem ipsum dolor sit amet osv. En masse tekst kommer som regel inn i nyheter. Blandt annet punktlister og sånt, men det får vi ta senere. Enda en setning hives på.
            
            For good measure tar vi også et avsnitt nummer to. Dette trenger ikke være like langt, men vi vil gjerne ha litt tekstbrytning for å sjekke at layout er lesbar.'
        ));
        
        return $objNyhet;
    
    }
    
    public static function getNyhetById($input_nyhetid) {
        global $msdb;
        
        $safeid = $msdb->quote($input_nyhetid);
        $sql = "SELECT " . self::SQL_NYHET_FIELDS . " FROM nyheter_nyhet WHERE nyhetid=$safeid LIMIT 1;";
        $res = $msdb->assoc($sql);
        
        return self::createNyhetsobjektFromDbRow($res[0]);
        
    }
    
    public static function getAlleNyheter() {
        global $msdb;
        
        $sql = "SELECT " . self::SQL_NYHET_FIELDS . " FROM nyheter_nyhet ORDER BY nyhetid DESC;";
        $res = $msdb->assoc($sql);
        
        return self::createNyhetCollectionFromDbResult($res);
        
    }
    
    public static function getNyhetByWikiPath($input_wikipath) {
        global $msdb;
        
        $safewikipath = $msdb->quote($input_wikipath);
        $sql = "SELECT " . self::SQL_NYHET_FIELDS . " FROM nyheter_nyhet WHERE wikipath=$safewikipath LIMIT 1;";
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
        $objNyhet->setTilgang($row['tilgangsgrupper']);
		$objNyhet->setViktighet($row['viktighet']);
		$objNyhet->setTitle($row['nyhettitle']);
		$objNyhet->setHtmlBody($row['nyhetbodycache']);
		$objNyhet->setCreateTime($row['createtime']);
		$objNyhet->setLastModTime($row['modtime']);
		$objNyhet->setDeleteTime($row['deletetime']);
		$objNyhet->setWikiPath($row['wikipath']);
		$objNyhet->setWikiHash($row['wikihash']);
        
        $objNyhet->under_construction = false;
        
        return $objNyhet;
        
    }
    
}
