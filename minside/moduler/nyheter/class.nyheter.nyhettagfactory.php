<?php
if(!defined('MS_INC')) die();

class NyhetTagFactory {

    const SQL_NYHET_TAG_FIELDS =
        '   nyheter_tag.tagid AS tagid,
            nyheter_tag.tagnavn AS tagnavn,
            nyheter_tag.tagtype AS tagtype,
            nyheter_tag.no_select AS no_select,
            nyheter_tag.no_view AS no_view,
            nyheter_tag.is_deleted AS is_deleted
        ';
        
    private static $objBlankKategori;	
	
    private function __construct() { }
    
    public static function getBlankKategori() {
        if (!isset(self::$objBlankKategori)) {
            $objNyhetTag = new NyhetTag(NyhetTag::TYPE_KATEGORI);
            $objNyhetTag->under_construction = true;
            $objNyhetTag->setNavn('Ikke kategorisert');
            $objNyhetTag->setNoSelect(true);
            $objNyhetTag->setNoView(false);
            $objNyhetTag->under_construction = false;
            self::$objBlankKategori = $objNyhetTag;
        }
        
        return self::$objBlankKategori;
    }
    
    public static function getNyhetTagById($input_tagid) {
        global $msdb;
        
        $safeid = $msdb->quote($input_tagid);
        $sql = "SELECT " . self::SQL_NYHET_TAG_FIELDS . 
			" FROM nyheter_tag
			  WHERE tagid=$safeid LIMIT 1;";
        $res = $msdb->assoc($sql);
        
        return self::createNyhetTagObjektFromDbRow($res[0]);
        
    }
    
    public static function getAlleNyhetTags($get_noselect = true, $get_noview = false, $get_deleted = false, $type=null) {
        global $msdb;
        
        $limits = array();
        if (!$get_noselect) {
            $limits[] = 'no_select = 0';
        }
        if (!$get_noview) {
            $limits[] = 'no_view = 0';
        }
        if (!$get_deleted) {
            $limits[] = 'is_deleted = 0';
        }
        if ($type) {
            $limits[] = 'tagtype = ' . $msdb->quote($type);
        }
        
        $sql = "SELECT " . self::SQL_NYHET_TAG_FIELDS . 
			" FROM nyheter_tag \n";
		for($i=0; $i <= (count($limits) - 1); $i++) {
            $sql .= (($i === 0) ? 'WHERE ' : 'AND ') . $limits[$i] . "\n";
        }
        $sql .= "ORDER BY tagtype, tagid\n";
        $res = $msdb->assoc($sql);
        
        return self::createNyhetTagCollectionFromDbResult($res);
        
    }
    
    protected static function createNyhetTagCollectionFromDbResult(array &$result) {
        $objNyhetTagCol = new NyhetTagCollection();
        
        foreach ($result as $row) {
            $objNyhetTag = self::createNyhetTagObjektFromDbRow($row);
            $objNyhetTagCol->addItem($objNyhetTag, $objNyhetTag->getId());
        }
        
        return $objNyhetTagCol;
    }
    
    protected static function createNyhetTagObjektFromDbRow(&$row) {
        
        if (empty($row) || !is_array($row)) {
            throw new Exception('Ingen eller ugyldig data gitt til factory');
        }
        
        $objNyhetTag = new NyhetTag($row['tagtype'], true, $row['tagid']);
        
        $objNyhetTag->under_construction = true;
        
        $objNyhetTag->setNavn($row['tagnavn']);
        $objNyhetTag->setNoSelect($row['no_select']);
        $objNyhetTag->setNoView($row['no_view']);
        $objNyhetTag->setIsDeleted($row['is_deleted']);
        
        $objNyhetTag->under_construction = false;
        
        return $objNyhetTag;
        
    }
	
}
