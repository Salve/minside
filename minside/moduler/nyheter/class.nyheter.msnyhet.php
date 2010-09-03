<?php
if(!defined('MS_INC')) die();

class MsNyhet {
	
	const VIKTIGHET_1 = '7 dager';
	const VIKTIGHET_2 = '5 dager';
	const VIKTIGHET_3 = '3 dager';

    public $under_construction = false;
	
	protected $_id;	
	protected $_omrade;
	protected $_type;
	protected $_createtime;
	protected $_createbynavn;
	protected $_createbyepost;
	protected $_lastmodtime;
	protected $_lastmodbynavn;
	protected $_lastmodbyepost;
	protected $_deletetime;
	protected $_deletebynavn;
	protected $_deletebyepost;
	protected $_issaved;
	protected $_hasunsavedchanges;
	protected $_wikipath;
	protected $_pubtime;
	
	protected $_imgpath;
	protected $_title;
	protected $_htmlbody;
	protected $_wikihash;
	protected $_wikitekst;
	
	public function __construct($isSaved = false, $id = null) {
		if ($isSaved && !$id) {
            throw new Exception('Logic Error: NyhetID må settes når isSaved = true');
        }
        
        $this->_issaved = $isSaved;
        $this->_id = $id;
	}
    
    public function __destruct() {
        if ($this->hasUnsavedChanges()) {
            $id = $this->getId();
            msg("Nyhet $id destructed with unsaved changes", -1);
        }
    }
	
	public function getId() {
		return $this->_id;
	}
	public function setId($inputid) {
		if (isset($this->_id)) {
			throw new Exception('Logic Error: ID på nyhet er allerede satt!');
		}
		
		$this->_id = $inputid;
		return true;
	}
	
	public function getType() {
		return $this->_type;
	}
	public function setType($input) {
		return $this->set_var($this->_type, $input);
	}
	
	public function getOmrade() {
		return $this->_omrade;
	}
	public function setOmrade($input) {
		if (!$this->under_construction) {
			$colOmrader = NyhetOmrade::getOmrader('msnyheter', AUTH_CREATE);
			if (!$colOmrader->exists($input)) {
				throw new Exception('Ugyldig område angitt!');
			}
		}
		
        return $this->set_var($this->_omrade, $input);
	}
	
	public function getPublishTime() {
		return $this->_pubtime;
	}
	public function setPublishTime($input) {
		// Validerer ikke dersom objekt bygges av factory
		if (!$this->under_construction) {
			$input = trim($input);
			// Matcher dato, optional tid. Sjekker skuddår
			$pattern = "{^(((\d{4})(-)(0[13578]|10|12)(-)(0[1-9]|[12][0-9]|3[01]))|((\d{4})(-)(0[469]|11)(-)([0][1-9]|[12][0-9]|30))|((\d{4})(-)(02)(-)(0[1-9]|1[0-9]|2[0-8]))|(([02468][048]00)(-)(02)(-)(29))|(([13579][26]00)(-)(02)(-)(29))|(([0-9][0-9][0][48])(-)(02)(-)(29))|(([0-9][0-9][2468][048])(-)(02)(-)(29))|(([0-9][0-9][13579][26])(-)(02)(-)(29)))(\s([0-1][0-9]|2[0-4]):([0-5][0-9]):([0-5][0-9]))?$}";
			if(!preg_match($pattern, $input)) {
				// Vill skrives til db som NULL
				$input = '';
			} else {
				// Må legge på 00:00:00 for å matche return fra db
				$input = date('Y-m-d H:i:s', strtotime($input));
			}
		}
		return $this->set_var($this->_pubtime, $input);
	}
	
	public function getCreateTime() {
		return $this->_createtime;
	}
	public function setCreateTime($input) {
        return $this->set_var($this->_createtime, $input);
	}
	public function getCreateByNavn() {
		return $this->_createbynavn;
	}
	public function setCreateByNavn($input) {
        return $this->set_var($this->_createbynavn, $input);
	}
	public function getCreateByEpost() {
		return $this->_createbyepost;
	}
	public function setCreateByEpost($input) {
        return $this->set_var($this->_createbyepost, $input);
	}
	

	public function isModified() {
		return !empty($this->_lastmodtime);
	}
	public function getLastModTime() {
		return $this->_lastmodtime;
	}
	public function setLastModTime($input) {
        return $this->set_var($this->_lastmodtime, $input);
	}
	public function getLastModByNavn() {
		return $this->_lastmodbynavn;
	}
	public function setLastModByNavn($input) {
        return $this->set_var($this->_lastmodbynavn, $input);
	}
	public function getLastModByEpost() {
		return $this->_lastmodbyepost;
	}
	public function setLastModByEpost($input) {
        return $this->set_var($this->_lastmodbyepost, $input);
	}

	public function hasUnsavedChanges() {
		return (bool) $this->_hasunsavedchanges;
	}
	
	public function getDeleteTime() {
		return $this->_deletetime;
	}
	public function setDeleteTime($input) {
        return $this->set_var($this->_deletetime, $input);
	}
	public function getDeleteByNavn() {
		return $this->_deletebynavn;
	}
	public function setDeleteByNavn($input) {
        return $this->set_var($this->_deletebynavn, $input);
	}
	public function getDeleteByEpost() {
		return $this->_deletebyepost;
	}
	public function setDeleteByEpost($input) {
        return $this->set_var($this->_deletebyepost, $input);
	}


	public function isDeleted() {
		return !empty($this->_deletetime);
	}
	
	public function hasImage() {
		return !empty($this->_imgpath);
	}
	public function getImagePath() {
		return $this->_imgpath;
	}
	public function setImagePath($input, $strip = false) {
		// strip settes når input er fra mediamanager, for å strippe wiki-syntax
		if ($strip) {
			$regexp = '/^\{\{\:(.*)\|\}\}$/';
			$input = trim($input);
			if (preg_match($regexp, $input, $matches) === 1) {
				$input = cleanID($matches[0], false);
				if (strlen($input) < 6) return false;
			} else {
				return false;
			}
		}
		
		if (!$this->under_construction && !empty($input)) {
			$filename = mediaFN($input);
			if (!@file_exists($filename)) {
				msg('Bilde ble ikke lagret, filen finnes ikke!', -1);
				$input = '';
			}
		}
	
        return $this->set_var($this->_imgpath, $input);
	}
	public function getImageTag($width) {
		if (!$this->hasImage()) return false;
		
		$width = (int) $width;
		if ($width < 10 || $width > 2000) throw new Exception('Thumbnailbredde out of bounds. Sjekk config.');
		
		$tagformat = '<img src="%1$slib/exe/fetch.php?w=%2$u&amp;media=%3$s" class="media" alt="" width="%2$u" />';
		return sprintf($tagformat, DOKU_BASE, $width, $this->getImagePath());
		
	}
	
	public function getWikiPath() {
		return $this->_wikipath;
	}
	public function setWikiPath($input) {
        if ($input == 'auto') {
            $input = 'msnyheter:'.$this->getOmrade().':' . date('YmdHis ') . $this->getTitle();
            $input = cleanID($input, true);
        }
        return $this->set_var($this->_wikipath, $input);
	}
	
	public function getTitle() {
		return $this->_title;
	}	
	public function setTitle($input) {
        return $this->set_var($this->_title, $input);
	}
	
	public function getHtmlBody() {
		if (!isset($this->_htmlbody)) {
            $this->update_html();
        }
        
        return $this->_htmlbody;
	}
	public function setHtmlBody($input) {
        return $this->set_var($this->_htmlbody, $input);
	}

	public function getWikiTekst() {
		return $this->_wikitekst;
	}
	public function setWikiTekst($input) {
        $input = cleanText($input);
        $newhash = md5($input);
        $oldhash = $this->getWikiHash();
        if ($newhash != $oldhash) {
            msg("Wikitekst has changed; old hash: $oldhash new hash: $newhash");
            $this->set_var($this->_wikitekst, $input);
            $this->setWikiHash($newhash);
            $this->write_wikitext();
            $this->update_html();
        } else {
            msg('setWikiTekst called, no changes');
        }
        
        return true;
	}

	public function getWikiHash() {
		return $this->_wikihash;
	}
	public function setWikiHash($input) {
        return $this->set_var($this->_wikihash, $input);
	}

	public function isSaved() {
		return (bool) $this->_issaved;
	}
	
	protected function set_var(&$var, &$value) {
		
		if (!$this->under_construction && ($var != $value)) {
            
            $trace = debug_backtrace();
            $caller = $trace[1]['function'];
            msg('Endring av nyhet fra funksjon: ' . $caller);
            
            $this->_hasunsavedchanges = true;
		}
		
		$var = $value;
		return true;
	}
    
    public function merkLest($brukerid) {
        global $msdb;
        
        $safenyhetid = $msdb->quote($this->getId());
        $safebrukerid = $msdb->quote($brukerid);
        
        $sql = "INSERT INTO nyheter_lest SET
                nyhetid = $safenyhetid,
                brukerid = $safebrukerid,
                readtime = NOW();";
                
        $res = $msdb->exec($sql);
        
        return (bool) $res;
    }
	
	protected function write_wikitext() {
		// function saveWikiText($id,$text,$summary,$minor=false)
		// definert i /inc/common.php på linje 927
		// Denne kaller funksjonen io_writeWikiPage som er definert på linje 145 i /inc/io.php
		//
		// $id = full path til wikiside, f.eks. "siebel:henvendelser"
		// $text = content som skal skrives, streng
		// $summary = edit-notat, vises i endringslogg
		// $minor = minor-edit checkbox i wiki-redigerings ui
		
        // Sørger for at IO_WIKIPAGE_WRITE handler i acthandler.php
        // ikke trigger ekstra db-update når vi skriver til wiki.
        
        
		$id = $this->getWikiPath();
		$text = $this->getWikiTekst();
		$summary = 'Nyhetsendring utført gjennom MinSide.';
		$minor = false;
        
        if (empty($id) || empty($text)) throw new Exception('Logic error: Kan ikke lagre nyhet i wiki uten både wikipath og tekst.');
		
        $GLOBALS['ms_writing_to_dw'] = true;
		$resultat = saveWikiText($id, $text, $summary, $minor);
        $GLOBALS['ms_writing_to_dw'] = false;

		msg('saveWikiText kallt, path: ' . $id . ' textlen: ' . strlen($text));
        
	}
    
    public function update_html() {
        msg('update html kallt');
        global $conf;
        $keep = $conf['allowdebug'];
        $conf['allowdebug'] = 0;
        
        $newtekst = p_wiki_xhtml($this->getWikiPath(), '', false);
        
        $conf['allowdebug'] = $keep;

        $this->setHtmlBody($newtekst);
    }
    
    public function update_db() {
        msg('update db kallt');
        global $msdb;
        
        $safeid = $msdb->quote($this->getId());
        $safeomrade = $msdb->quote($this->getOmrade());
        $safetype = $msdb->quote($this->getType());
        $safewikipath = $msdb->quote($this->getWikiPath());
        $safeimgpath = $msdb->quote($this->getImagePath());
        $safetitle = $msdb->quote($this->getTitle());
        $safehtmlbody = $msdb->quote($this->getHtmlBody());
        $safewikihash = $msdb->quote($this->getWikiHash());
		$safepubtime = ($this->getPublishTime()) ? $msdb->quote($this->getPublishTime()) : 'NULL';
 
        
        $midsql = "omrade = $safeomrade,
                nyhetstype = $safetype,
                wikipath = $safewikipath,
                wikihash = $safewikihash,
                nyhettitle = $safetitle,
                imgpath = $safeimgpath,
                nyhetbodycache = $safehtmlbody,
				pubtime = $safepubtime
                ";
                
        if (!$this->isSaved()) {
            $presql = "INSERT INTO nyheter_nyhet SET\n";
            $presql .= "nyhetid = DEFAULT,\n";
            $presql .= "createtime = NOW(),\n";
            $presql .= "createby = " . MinSide::$UserID . ",\n";
            $postsql = ";";
        } else {
            $presql = "UPDATE nyheter_nyhet SET\n";
            $presql .= "modtime = NOW(),\n";
			$presql .= "modby = " . MinSide::$UserID . ",\n";
            $postsql = "WHERE nyhetid = $safeid LIMIT 1;";
        }
        
        $sql = $presql . $midsql . $postsql;
        $res = $msdb->exec($sql);
        $this->_hasunsavedchanges = false;
        
        if(!$this->isSaved()) {
            $this->setId($msdb->getLastInsertId());
        }
        
        $this->_issaved = true;
        
        // return true dersom db ble endret
        return (bool) $res;
    }
	
}
