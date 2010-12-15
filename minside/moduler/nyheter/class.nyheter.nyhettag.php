<?php
if(!defined('MS_INC')) die();

class NyhetTag {
    
    const TYPE_KATEGORI = 2;
    const TYPE_TAG = 3;
    
    protected $_type;
    protected $_id;
    protected $_navn;
    protected $_noselect;
    protected $_noview;
    protected $_isdeleted;
    
    protected $_issaved = false;
    protected $_hasunsavedchanges = false;
    
    public $under_construction = false;
    public $baseurl;
    
    public function __construct($type, $issaved=false, $id=null, $baseurl='') {
        if ($type != self::TYPE_KATEGORI && $type != self::TYPE_TAG) {
            throw new Exception('Ugyldig tag/kategori-type: ' . htmlspecialchars($type));
        }
        if ($issaved && ($id == false)) {
            throw new Exception('Logic error: ID må angis når nyhet er definert som lagret.');
        }
        
        $this->_issaved = $issaved;
        $this->_id = $id;
        $this->_type = (int) $type;
        $this->baseurl = $baseurl;
    }
    
    public function __destruct() {
        if ($this->_hasunsavedchanges) {
            $id = $this->getId();
            if(MinSide::DEBUG) msg("NyhetTag $id destructed with unsaved changes", -1);
        }
    }
    
    public function getType() {
        return $this->_type;
    }
    
    protected function setSaved($id) {
        if ($this->isSaved()) {
            throw new Exception('Kan ikke lagre nyhettag som allerede er lagret');
        }
        $this->_id = (int) $id;
        $this->_issaved = true;
    }
    public function isSaved() {
        return (bool) $this->_issaved;
    }
    public function getId() {
        if (!$this->isSaved()) return false;
        return $this->_id;
    }
    
    public function setNavn($input) {
        $this->set_var($this->_navn, $input);
    }
    public function getNavn() {
        return $this->_navn;
    }
    
    public function setNoSelect($input) {
        $input = (bool) $input;
        $this->set_var($this->_noselect, $input);
    }
    public function noSelect() {
        return $this->_noselect;
    }
    
    public function setNoView($input) {
        $input = (bool) $input;
        $this->set_var($this->_noview, $input);
    }
    public function noView() {
        return $this->_noview;
    }
    
    public function setIsDeleted($input) {
        $input = (bool) $input;
        $this->set_var($this->_isdeleted, $input);
    }
    public function isDeleted() {
        return (bool) $this->_isdeleted;
    }
    
    protected function set_var(&$var, &$value) {
		
		if (!$this->under_construction && ($var != $value)) {
            
            if(MinSide::DEBUG) {
                $trace = debug_backtrace();
                $caller = $trace[1]['function'];
                msg('Endring av nyhet_tag fra funksjon: ' . $caller);
            }
            
            $this->_hasunsavedchanges = true;
		}
		
		$var = $value;
		return true;
	}
    
    public function updateDb() {
        if (!$this->_hasunsavedchanges) return false;
        
        global $msdb;
        
        $safenavn = $msdb->quote($this->getNavn());
        $safetype = (int) $this->getType();
        $safeid = $msdb->quote($this->getId());
        $safenoview = ($this->noView()) ? '1' : '0';
        $safenoselect = ($this->noSelect()) ? '1' : '0';
        
        $midsql = " SET
                    tagnavn=$safenavn,
                    tagtype=$safetype,
                    no_view=$safenoview,
                    no_select=$safenoselect ";
                    
        if($this->isSaved()) {
            $presql = "UPDATE nyheter_tag ";
            $postsql = " WHERE tagid=$safeid LIMIT 1;";
        } else {
            $presql = "INSERT INTO nyheter_tag ";
            $postsql = '';
        }
        $sql = $presql . $midsql . $postsql;
        $res = $msdb->exec($sql);
        
        if(!$this->isSaved()) {
            $this->setSaved($msdb->getLastInsertId());
        }
        $this->_hasunsavedchanges = false;
        
        return (bool) $res;
    }
    
    public function slett() {
        if ($this->isDeleted()) throw new Exception('Kan ikke slette tag/kategori som allerede er slettet.');
        
        global $msdb;
        $safeid = $msdb->quote($this->getId());
        $sql = "UPDATE nyheter_tag SET is_deleted = 1 WHERE tagid = $safeid LIMIT 1";
        
        return (bool) $msdb->exec($sql);
    }
    
    public static function compare_strlen_navn(NyhetTag $a, NyhetTag $b) {
        $lenA = strlen($a->getNavn());
        $lenB = strlen($b->getNavn());
        if($lenA == $lenB) return 0;
        return ($lenA > $lenB) ? +1 : -1;
    }
    
    public static function compare_alpha_navn(NyhetTag $a, NyhetTag $b) {
        $navnA = $a->getNavn();
        $navnB = $b->getNavn();
        if($navnA == $navnB) return 0;
        return ($navnA > $navnB) ? +1 : -1;
    }
    
    public function getKategoriUpdateFunction() {
        if (!$this->isSaved()) throw new Exception('Kan ikke generere DB-update funksjon for kategori som ikke er lagret.');
        if ($this->getType() !== self::TYPE_KATEGORI) throw new Exception('Kan ikke generere DB-update funksjon for tags som ikke er av type kategori.');
        $tagid = $this->getId();
        return function($nyhetid) use ($tagid)
            {
                global $msdb;
                $safekatid = $msdb->quote($tagid);
                $safenyhetid = $msdb->quote($nyhetid);
                $safetype = NyhetTag::TYPE_KATEGORI;
                $deletesql = "DELETE FROM 
                                nyheter_tag_x_nyhet    
                            USING
                                    nyheter_tag 
                                INNER JOIN 
                                    nyheter_tag_x_nyhet 
                                ON 
                                    nyheter_tag.tagid = nyheter_tag_x_nyhet.tagid
                            WHERE 
                                    nyheter_tag_x_nyhet.nyhetid = $safenyhetid
                                AND
                                    nyheter_tag.tagtype = $safetype;";
                $insertsql = "INSERT INTO nyheter_tag_x_nyhet
                                SET tagid = $safekatid, 
                                    nyhetid = $safenyhetid;";
                $checksql = "SELECT
                                COUNT(*)
                            FROM
                                nyheter_tag
                            INNER JOIN
                                nyheter_tag_x_nyhet
                            ON
                                nyheter_tag.tagid = nyheter_tag_x_nyhet.tagid
                            WHERE
                                    nyheter_tag_x_nyhet.nyhetid = $safenyhetid
                                AND
                                    nyheter_tag.tagtype = $safetype;";
                $msdb->startTrans();
                $msdb->exec($deletesql);
                $msdb->exec($insertsql);
                $res = $msdb->num($checksql);
                if ($res[0][0] === '1') {
                    $msdb->commit();
                    return true;
                } else {
                    $msdb->rollBack();
                    throw new Exception('Update DB feilet: Feil under knytning av kategori og nyhet.');
                }
            };
    }
    
    public static function getTagUpdateFunction(NyhetTagCollection &$colTags) {
        return function($nyhetid) use(&$colTags)
            {
                global $msdb;
                $safenyhetid = $msdb->quote($nyhetid);
                $safetype = NyhetTag::TYPE_TAG;
                $arTags = array();
                foreach($colTags as $objTag) {
                    $arTags[] = '(' . $safenyhetid . ", '" . $objTag->getId() . "')";
                }
                $safeinsertvalues = implode(", \n", $arTags);
                $deletesql = "DELETE FROM 
                                nyheter_tag_x_nyhet    
                            USING
                                    nyheter_tag 
                                INNER JOIN 
                                    nyheter_tag_x_nyhet 
                                ON 
                                    nyheter_tag.tagid = nyheter_tag_x_nyhet.tagid
                            WHERE 
                                    nyheter_tag_x_nyhet.nyhetid = $safenyhetid
                                AND
                                    nyheter_tag.tagtype = $safetype;";
                $insertsql = "INSERT INTO nyheter_tag_x_nyhet (nyhetid, tagid)
                                VALUES
                                $safeinsertvalues;";
                $checksql = "SELECT
                                COUNT(*)
                            FROM
                                nyheter_tag
                            INNER JOIN
                                nyheter_tag_x_nyhet
                            ON
                                nyheter_tag.tagid = nyheter_tag_x_nyhet.tagid
                            WHERE
                                    nyheter_tag_x_nyhet.nyhetid = $safenyhetid
                                AND
                                    nyheter_tag.tagtype = $safetype;";
                $msdb->startTrans();
                $msdb->exec($deletesql);
                if ($colTags->length() > 0) {
                    $msdb->exec($insertsql);
                }
                $res = $msdb->num($checksql);
                if ($res[0][0] === (string) $colTags->length()) {
                    $msdb->commit();
                    return true;
                } else {
                    $msdb->rollBack();
                    throw new Exception('Update DB feilet: Feil under knytning av tags og nyhet.');
                }
            };
    }
    
}
