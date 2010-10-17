<?php
if(!defined('MS_INC')) die();
require_once('class.nyheter.msnyhet.php');
require_once('class.nyheter.nyhetcollection.php');

class NyhetFactory {

    const SQL_NYHET_FIELDS =
        '   nyheter_nyhet.nyhetid AS nyhetid,
            nyheter_nyhet.omrade AS omrade,
            nyheter_nyhet.nyhetstype AS nyhetstype,
            nyheter_nyhet.issticky AS issticky,
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
    
    const SQL_TAG_AND_JOIN_FORMAT =
        ' LEFT JOIN
        (
            SELECT 
                tagbind.nyhetid AS nyhetid
            FROM 
                nyheter_tag_x_nyhet AS tagbind
            WHERE 
                tagbind.tagid IN(%1$s)
            GROUP BY 
                tagbind.nyhetid
            HAVING 
                count(tagbind.nyhetid) = \'%2$u\'
        ) AS taghits ON taghits.nyhetid = nyheter_nyhet.nyhetid
        ';
    
    const SQL_TAG_OR_JOIN_FORMAT =
        ' LEFT JOIN
        (
            SELECT 
                DISTINCT tagbind.nyhetid AS nyhetid
            FROM 
                nyheter_tag_x_nyhet AS tagbind
            WHERE 
                tagbind.tagid IN(%1$s)
        ) AS taghits ON taghits.nyhetid = nyheter_nyhet.nyhetid
        ';
    
    const SQL_KATEGORI_JOIN_FORMAT =
        ' LEFT JOIN
        (
            SELECT
                DISTINCT katbind.nyhetid AS nyhetid
            FROM
                nyheter_tag_x_nyhet AS katbind
            WHERE
                katbind.tagid IN(%1$s)
        ) AS kathits ON kathits.nyhetid = nyheter_nyhet.nyhetid
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
		
		$omrader = self::getSafeOmrader(MSAUTH_1);
        
        $sql = "SELECT " . self::SQL_NYHET_FIELDS . 
			" FROM nyheter_nyhet " . self::SQL_FULLNAME_JOINS .
			" WHERE pubtime < NOW()
				AND nyheter_nyhet.omrade IN ($omrader)
				AND deletetime IS NULL
			ORDER BY pubtime DESC
            LIMIT 10000;";
        $res = $msdb->assoc($sql);
        
        return self::createNyhetCollectionFromDbResult($res);
        
    }
    
    public static function getNyligePubliserteNyheter() {
        global $msdb;
		
		$omrader = self::getSafeOmrader(MSAUTH_1);
        
        $sql = "SELECT " . self::SQL_NYHET_FIELDS . 
			" FROM nyheter_nyhet " . self::SQL_FULLNAME_JOINS .
			" WHERE (DATE_ADD(pubtime, INTERVAL 7 DAY) > NOW() OR issticky = 1)
                AND pubtime < NOW()
				AND nyheter_nyhet.omrade IN ($omrader)
				AND deletetime IS NULL
			ORDER BY pubtime DESC
            LIMIT 100;";
        $res = $msdb->assoc($sql);
        
        return self::createNyhetCollectionFromDbResult($res);
        
    }
    
    public static function getUpubliserteNyheter() {
        global $msdb;
		
        // Henter kun upubliserte nyheter bruker kan redigere
		$omrader = self::getSafeOmrader(MSAUTH_3);
        
        $sql = "SELECT " . self::SQL_NYHET_FIELDS . 
			" FROM nyheter_nyhet " . self::SQL_FULLNAME_JOINS .
			" WHERE (pubtime > NOW() OR pubtime IS NULL)
				AND nyheter_nyhet.omrade IN ($omrader)
				AND deletetime IS NULL
			ORDER BY pubtime DESC
            LIMIT 500;";
        $res = $msdb->assoc($sql);
        
        return self::createNyhetCollectionFromDbResult($res);
        
    }
    
    public static function getUlesteNyheterForBrukerId($brukerid) {
        global $msdb;
        
		$omrader = self::getSafeOmrader(MSAUTH_1);
		
        $safebrukerid = $msdb->quote($brukerid);
        
        $sql = "SELECT " . self::SQL_NYHET_FIELDS . "
                FROM nyheter_nyhet 
                LEFT JOIN nyheter_lest 
                ON nyheter_nyhet.nyhetid = nyheter_lest.nyhetid 
                    AND nyheter_lest.brukerid = $safebrukerid " . 
				self::SQL_FULLNAME_JOINS .
                " LEFT JOIN internusers AS bruker
                    ON bruker.id = $safebrukerid
                WHERE nyheter_lest.nyhetid IS NULL
					AND nyheter_nyhet.omrade IN ($omrader)
					AND pubtime < NOW()
                    AND pubtime > bruker.createtime
					AND deletetime IS NULL
                ORDER BY nyheter_nyhet.nyhetid ASC
                LIMIT 10
            ;";
            
        $res = $msdb->assoc($sql);
        
        return self::createNyhetCollectionFromDbResult($res);
        
    }
	
	public static function getDeletedNyheter() {
        global $msdb;
		
		$omrader = self::getSafeOmrader(MSAUTH_2);
        
        $sql = "SELECT " . self::SQL_NYHET_FIELDS . 
			" FROM nyheter_nyhet " . self::SQL_FULLNAME_JOINS .
			" WHERE deletetime IS NOT NULL
				AND nyheter_nyhet.omrade IN ($omrader)
			ORDER BY deletetime DESC;";
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
    
    public static function getNyheterMedLimits(array $limits=array()) {
        global $msdb;
        
        $omrader = self::getSafeOmrader(MSAUTH_1);
        
        $where = array();
        $join = array();
        
        // Kategorier
        if (array_key_exists('fkat', $limits) && is_array($limits['fkat']) && sizeof($limits['fkat'])) {
            $arsafekat = array();
            foreach ($limits['fkat'] as $katid) {
                $arsafekat[] = $msdb->quote($katid);
            }
            $katlist = implode(', ', $arsafekat);
            $join[] = sprintf(self::SQL_KATEGORI_JOIN_FORMAT, $katlist);
            $where[] = "kathits.nyhetid IS NOT NULL";
        }
        // Tags
        if (array_key_exists('ftag', $limits) && is_array($limits['ftag']['data']) && 
            sizeof($limits['ftag']['data']) && !empty($limits['ftag']['mode'])) {
            $arsafetag = array();
            foreach ($limits['ftag']['data'] as $tagid) {
                $arsafetag[] = $msdb->quote($tagid);
            }
            $taglist = implode(', ', $arsafetag);
            if($limits['ftag']['mode'] == 'OR') {
                $join[] = sprintf(self::SQL_TAG_OR_JOIN_FORMAT, $taglist);
            } else {
                $join[] = sprintf(self::SQL_TAG_AND_JOIN_FORMAT, $taglist, count($arsafetag));
            }
            $where[] = "taghits.nyhetid IS NOT NULL";
        }
        // Fradato
        if (array_key_exists('fdato', $limits)) $where[] = "pubtime >= '" . $limits['fdato'] . "'";
        // Tildato
        if (array_key_exists('tdato', $limits)) $where[] = "pubtime <= '" . $limits['tdato'] . "'";
        // Overskriftsøk
        if (array_key_exists('oskrift', $limits)) $where[] = "nyhettitle LIKE " . $msdb->quote($limits['oskrift']);
        
        
        $sql_where = implode(' AND ', $where);
        $sql_join = implode(" \n", $join);
        
        $sql = "SELECT " . self::SQL_NYHET_FIELDS . 
			" FROM nyheter_nyhet " . self::SQL_FULLNAME_JOINS .
            (($sql_join) ?: '' ) .
			" WHERE pubtime < NOW()
                " . (($sql_where) ? " AND $sql_where" : '' ) .
				" AND nyheter_nyhet.omrade IN ($omrader)
				AND deletetime IS NULL
			ORDER BY pubtime DESC
            LIMIT 10000;";
        $res = $msdb->assoc($sql);
        
        return self::createNyhetCollectionFromDbResult($res);
        
    }
    
    protected static function createNyhetCollectionFromDbResult(array &$result, $linktags=true) {
        $objNyhetCol = new NyhetCollection();
        
        foreach ($result as $row) {
            $objNyhet = self::createNyhetsobjektFromDbRow($row, false);
            $objNyhetCol->addItem($objNyhet, $objNyhet->getId());
        }
        
        if(($objNyhetCol->length() > 0) && $linktags) {
            NyhetTagFactory::attachTagsToNyhetCollection($objNyhetCol);
        }
        
        return $objNyhetCol;
    }
    
    protected static function createNyhetsobjektFromDbRow(&$row, $linktags=true) {
        
        if (empty($row) || !is_array($row)) {
            throw new Exception('Ingen eller ugyldig data gitt til factory');
        }
        
        $objNyhet = new MsNyhet(true, $row['nyhetid']);
        $objNyhet->under_construction = true;
        
        $objNyhet->setType($row['nyhetstype']);
        $objNyhet->setOmrade($row['omrade']);
		$objNyhet->setTitle($row['nyhettitle']);
		$objNyhet->setImagePath($row['imgpath']);
		$objNyhet->setIsSticky($row['issticky']);
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
        
        if($linktags) {
            NyhetTagFactory::attachTagsToNyhet($objNyhet);
        }
        
        return $objNyhet;
        
    }
    
	protected static function getSafeOmrader($auth) {
		global $msdb;
		
		$colOmrader = NyhetOmrade::getOmrader('msnyheter', $auth);
		$arOmrader = array();
		foreach ($colOmrader as $objOmrade) {
			$arOmrader[] = $msdb->quote($objOmrade->getOmrade());
		}
		$omrader = implode(',', $arOmrader);
		return ($omrader) ?: "''";
	}
	
}
