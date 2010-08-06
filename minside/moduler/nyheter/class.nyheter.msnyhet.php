<?php
if(!defined('MS_INC')) die();

class MsNyhet {

    public $under_construction = false;
	
	protected $_id;	
	protected $_tilgang;
	protected $_type;
	protected $_viktighet;
	protected $_createtime;
	protected $_lastmodtime;
	protected $_deletetime;
	protected $_issaved;
	protected $_hasunsavedchanges;
	protected $_wikipath;
	
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
	
	public function getTilgang() {
		return $this->_tilgang;
	}
	public function setTilgang($input) {
        return $this->set_var($this->_tilgang, $input);
	}
	
	public function getViktighet() {
		return $this->_viktighet;
	}
	public function setViktighet($input) {
		return $this->set_var($this->_viktighet, $input);
	}
	
	public function getCreateTime() {
		return $this->_createtime;
	}
	public function setCreateTime($input) {
        return $this->set_var($this->_createtime, $input);
	}

	public function getLastModTime() {
		return $this->_lastmodtime;
	}
	public function setLastModTime($input) {
        return $this->set_var($this->_lastmodtime, $input);
	}

	public function hasUnsavedChanges() {
		return (bool) $this->_hasunsavedchanges;
	}
	
	public function isModified() {
		return isset($this->_lastmodtime);
	}
	
	public function getDeleteTime() {
		return $this->_deletetime;
	}
	public function setDeleteTime($input) {
        return $this->set_var($this->_deletetime, $input);
	}

	public function isDeleted() {
		return isset($this->_deletetime);
	}
	
	public function hasImage() {
		return isset($this->_imgpath);
	}
	public function getImagePath() {
		return $this->_imgpath;
	}
	public function setImagePath($input) {
        return $this->set_var($this->_imgpath, $input);
	}
	
	public function getWikiPath() {
		return $this->_wikipath;
	}
	public function setWikiPath($input) {
        if ($input == 'auto') {
            $input = 'msnyheter:ks:' . date('Ymd ') . $this->getTitle();
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
        // if (empty($this->_wikitekst) && $this->isSaved()) {
            // $rawwiki = rawWiki($this->getWikiPath());
            // $this->setWikiTekst($rawwiki);
        // }
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
        $safetilgang = $msdb->quote($this->getTilgang());
        $safetype = $msdb->quote($this->getType());
        $safeviktighet = $msdb->quote($this->getViktighet());
        $safewikipath = $msdb->quote($this->getWikiPath());
        $safeimgpath = $msdb->quote($this->getImagePath());
        $safetitle = $msdb->quote($this->getTitle());
        $safehtmlbody = $msdb->quote($this->getHtmlBody());
        $safewikihash = $msdb->quote($this->getWikiHash());        
 
        
        $midsql = "tilgangsgrupper = $safetilgang,
                nyhetstype = $safetype,
                viktighet = $safeviktighet,
                wikipath = $safewikipath,
                wikihash = $safewikihash,
                nyhettitle = $safetitle,
                imgpath = $safeimgpath,
                nyhetbodycache = $safehtmlbody
                ";
                
        if (!$this->isSaved()) {
            $presql = "INSERT INTO nyheter_nyhet SET\n";
            $presql .= "nyhetid = DEFAULT,\n";
            $presql .= "createtime = NOW(),\n";
            $postsql = ";";
        } else {
            $presql = "UPDATE nyheter_nyhet SET\n";
            $presql .= "modtime = NOW(),\n";
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
