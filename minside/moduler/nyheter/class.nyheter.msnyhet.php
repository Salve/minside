<?php
if(!defined('MS_INC')) die();

class MsNyhet {
	
	const PATH_MAX_LEN = 40; // Maks lengde på nyhetsoverskift del av wikipath før cleanID kjøres
    const TITLE_MAX_LEN = 140;
    
    public $under_construction = false;
	
	protected $_id;	
	protected $_omrade;
	protected $_type;
	protected $_issticky;
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
    
    protected $_arreadlist;
    protected $_objkategori;
    protected $_coltags;
    
    private $_dbcallback = array();
	
	public function __construct($isSaved = false, $id = null) {
		if ($isSaved && !$id) {
            throw new Exception('Logic Error: NyhetID må settes når isSaved = true');
        }
        
        $this->_issaved = $isSaved;
        $this->_id = $id;
	}
    
    public function __destruct() {
        if(MinSide::DEBUG) {
            if ($this->hasUnsavedChanges()) {
                $id = $this->getId();
                msg("Nyhet $id destructed with unsaved changes", -1);
            }
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
				throw new Exception('Ugyldig område angitt: ' . htmlspecialchars($input));
			}
		}
		
        return $this->set_var($this->_omrade, $input);
	}
    
    public function getKategori() {
        if (!isset($this->_objkategori)) $this->_objkategori = NyhetTagFactory::getBlankKategori();
        return $this->_objkategori;
    }
    public function getKategoriNavn() {
        if (!isset($this->_objkategori)) $this->_objkategori = NyhetTagFactory::getBlankKategori();
        return $this->_objkategori->getNavn();
    }
    public function setKategori(NyhetTag $objInputTag, $nosave = false) {
        if (($objInputTag->getType() !== NyhetTag::TYPE_KATEGORI) ||
             !$objInputTag->isSaved()) {
            throw new Exception('Feil ved setting av kategori på nyhet: Ikke gyldig kategori-objekt.');
        }
        if ($this->getKategori() == $objInputTag) return false;
        $this->_objkategori = $objInputTag;
        if (!$this->under_construction && !$nosave) {
            if(MinSide::DEBUG) msg('Loading callback for kategori update'); // debug
            $this->_hasunsavedchanges = true;
            $this->_dbcallback['kategori'] = $objInputTag->getKategoriUpdateFunction();
        }
        return true;
    }
    
    public function getTags() {
        if (!isset($this->_coltags)) $this->_coltags = new NyhetTagCollection();
        return $this->_coltags;
    }
    public function hasTag(NyhetTag $objInputTag) {
        if ($objInputTag->getType() !== NyhetTag::TYPE_TAG) {
            return false;
        }
        $colTag = $this->getTags();
        return (bool) $colTag->exists($objInputTag->getId());
    }
    public function addTag(NyhetTag $objInputTag, $nosave = false) {
        if (($objInputTag->getType() !== NyhetTag::TYPE_TAG) ||
            !$objInputTag->isSaved()) {
            throw new Exception('Feil ved setting av tag på nyhet: Ikke gyldig tag-objekt.');
        }
        $colTags = $this->getTags();
        $colTags->additem($objInputTag, $objInputTag->getId());
        if (!$this->under_construction && !$nosave && !isset($this->_dbcallback['tags'])) {
            if(MinSide::DEBUG) msg('Loading callback for tag update'); // debug
            $this->_hasunsavedchanges = true;
            $this->_dbcallback['tags'] = NyhetTag::getTagUpdateFunction($colTags);
        }
    }
    public function setTags(NyhetTagCollection $colInputTags, $nosave = false) {
        if ($colInputTags != $this->getTags()) {
            $this->_coltags = $colInputTags;
            if (!$this->under_construction && !$nosave && !isset($this->_dbcallback['tags'])) {
                if(MinSide::DEBUG) msg('Loading callback for tag update'); // debug
                $this->_hasunsavedchanges = true;
                $this->_dbcallback['tags'] = NyhetTag::getTagUpdateFunction($this->_coltags);
            }
        } else {
            if(MinSide::DEBUG) msg('Setting tags: no changes - no save'); // debug
        }
    }
	
	public function getPublishTime() {
		return $this->_pubtime;
	}
    public function isPublished() {
        $pubtime = strtotime($this->_pubtime);
        return ($pubtime < time());
    }
	public function setPublishTime($input) {
		// Validerer ikke dersom objekt bygges av factory
		if (!$this->under_construction) {
            $input = strtotime(trim($input));
            $aar = (int) date('Y', $input);
            $mnd = (int) date('n', $input);
            $dag = (int) date('j', $input);
            $tim = (int) date('G', $input);
            $min = (int) date('i', $input);
            $sek = (int) date('s', $input);
            
            if (
                $aar < 2010 ||
                $aar > 2020 ||
                $mnd < 1 ||
                $mnd > 12 ||
                $dag < 1 ||
                $dag > 31 ||
                $tim < 0 ||
                $tim > 23 ||
                $min < 0 ||
                $min > 60 ||
                $sek < 0 ||
                $sek > 60
            ) {
                $invaliddate = true;
                $input = '';
            } else {
                $input = date('Y-m-d H:i:s', $input);
            }
		}
        $res = $this->set_var($this->_pubtime, $input);
		return ($invaliddate) ? false : $res;
	}
	
	public function getCreateTime() {
		return $this->_createtime;
	}
	public function setCreateTime($input) {
        $this->_createtime = $input;
	}
	public function getCreateByNavn() {
		return $this->_createbynavn;
	}
	public function setCreateByNavn($input) {
        $this->_createbynavn = $input;
	}
	public function getCreateByEpost() {
		return $this->_createbyepost;
	}
	public function setCreateByEpost($input) {
        $this->_createbyepost = $input;
	}
	
	public function isSticky() {
		return (bool) $this->_issticky;
	}
	public function setIsSticky($input) {
		return $this->set_var($this->_issticky, $input);
	}

	public function isModified() {
		return !empty($this->_lastmodtime);
	}
	public function getLastModTime() {
		return $this->_lastmodtime;
	}
	public function setLastModTime($input) {
        $this->_lastmodtime = $input;
	}
	public function getLastModByNavn() {
		return $this->_lastmodbynavn;
	}
	public function setLastModByNavn($input) {
        $this->_lastmodbynavn = $input;
	}
	public function getLastModByEpost() {
		return $this->_lastmodbyepost;
	}
	public function setLastModByEpost($input) {
        $this->_lastmodbyepost = $input;
	}

	public function hasUnsavedChanges() {
		return (bool) $this->_hasunsavedchanges;
	}
	
	public function getDeleteTime() {
		return $this->_deletetime;
	}
	public function setDeleteTime($input) {
        $this->_deletetime = $input;
	}
	public function getDeleteByNavn() {
		return $this->_deletebynavn;
	}
	public function setDeleteByNavn($input) {
        $this->_deletebynavn = $input;
	}
	public function getDeleteByEpost() {
		return $this->_deletebyepost;
	}
	public function setDeleteByEpost($input) {
        $this->_deletebyepost = $input;
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
		
		$tagformat = '<img src="%1$slib/exe/fetch.php?w=%2$u&amp;media=%3$s" class="nyhetimg" alt="" width="%2$u" />';
		return sprintf($tagformat, DOKU_BASE, $width, $this->getImagePath());
		
	}
	
	public function getWikiPath() {
		return $this->_wikipath;
	}
	public function setWikiPath($input) {
        if (empty($input)) return false;
        if ($input == 'auto') {
            $input = str_replace(array(':', ';', '/', '\\'), '_' , $this->getTitle());
            if (strlen($input) > self::PATH_MAX_LEN) $input = substr($input, 0, self::PATH_MAX_LEN);
            $input = 'msnyheter:'.$this->getOmrade().':' . date('YmdHis ') . $input;
            $input = cleanID($input, true);
        }
        return $this->set_var($this->_wikipath, $input);
	}
	
	public function getTitle($safe = false) {
		return ($safe) ? htmlspecialchars($this->_title) : $this->_title;
	}	
	public function setTitle($input) {
        if (strlen($input) > self::TITLE_MAX_LEN) $input = substr($input, 0, self::TITLE_MAX_LEN);
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
	public function setWikiTekst($input, $nowrite = false) {
        $input = cleanText($input);
        $newhash = md5($input);
        $oldhash = $this->getWikiHash();
        if ($newhash != $oldhash) {
            if(MinSide::DEBUG) msg("Wikitekst has changed; old hash: $oldhash new hash: $newhash");
            $this->_wikitekst = $input;
            $this->setWikiHash($newhash);
            if (!$nowrite) { $this->write_wikitext(); }
            $this->update_html();
        } else {
            if(MinSide::DEBUG) msg('setWikiTekst called, no changes');
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
            
            if(MinSide::DEBUG) {
                $trace = debug_backtrace();
                $caller = $trace[1]['function'];
                msg('Endring av nyhet fra funksjon: ' . $caller);
            }
            
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
    
    public function merkUlestForAlle() {
        global $msdb;
        
        $safenyhetid = $msdb->quote($this->getId());
        
        $sql = "DELETE FROM nyheter_lest WHERE
                nyhetid = $safenyhetid;";
                
        $res = $msdb->exec($sql);
        
        return $res;
    }
	
	public function slett() {
		if ($this->isDeleted()) {
			return false;
		}
			
		global $msdb;
		
		$safenyhetid = $msdb->quote($this->getId());
		$safebrukerid = $msdb->quote(MinSide::getUserID());
		
		$sql = "UPDATE nyheter_nyhet
				SET deletetime = NOW(),
				deleteby = $safebrukerid
				WHERE nyhetid = $safenyhetid;";
		
		return (bool) $msdb->exec($sql);
	}
    
    public function permslett() {
        if (!$this->isDeleted()) {
            return false;
        }
        
        $id = $this->getWikiPath();
		$summary = 'Permanent nyhetsletting utført gjennom MinSide.';
		$minor = false;
        
        if (empty($id)) throw new Exception('Logic error: Kan ikke perm-slette nyhet som ikke har definert path i dokuwiki.');
		
        $file = wikiFN($id); // filnavn
        
        $GLOBALS['ms_writing_to_dw'] = true;
		saveWikiText($id, '', $summary, $minor);
        $GLOBALS['ms_writing_to_dw'] = false;
        
        // sjekk at wikiside er borte
        if (@file_exists($file)) {
            return false;
        }
        
        global $msdb;
        $safenyhetid = $msdb->quote($this->getId());
        
        $msdb->startTrans();
        $sql[] = "DELETE
                FROM nyheter_lest
                WHERE nyhetid=$safenyhetid;";
        $sql[] = "DELETE
                FROM nyheter_tag_x_nyhet
                WHERE nyhetid=$safenyhetid;";
        $sql[] = "DELETE
                FROM nyheter_nyhet
                WHERE nyhetid=$safenyhetid
                LIMIT 1;";
                
        $res = true;
        try {
            foreach ($sql as $stmt) {
                $res = ($res && ($msdb->exec($stmt) !== false));
            }
        } catch(Exception $e) {
            $msdb->rollBack();
            return false;
        }
        if ($res) {
            $msdb->commit();
            return true;
        } else {
            $msdb->rollBack();
            return false;
        }
    
    }
	
	public function restore() {
		if (!$this->isDeleted()) {
			return false;
		}
			
		global $msdb;
		
		$safenyhetid = $msdb->quote($this->getId());
		
		$sql = "UPDATE nyheter_nyhet
				SET deletetime = NULL,
				deleteby = NULL
				WHERE nyhetid = $safenyhetid;";
		
		return (bool) $msdb->exec($sql);
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
		if(strlen($text) > 100000) throw new Exception('Nyhet body for lang. Maks 50 000 tegn.');
        
        $GLOBALS['ms_writing_to_dw'] = true;
		$resultat = saveWikiText($id, $text, $summary, $minor);
        $GLOBALS['ms_writing_to_dw'] = false;

		if(MinSide::DEBUG) msg('saveWikiText kallt, path: ' . $id . ' textlen: ' . strlen($text));
        
	}
    
    public function update_html() {
        if(MinSide::DEBUG) msg('update html kallt');
        global $conf;
        $keep = $conf['allowdebug'];
        $conf['allowdebug'] = 0;
        
        $wikitekst = $this->getWikiTekst();
        if (!isset($wikitekst)) {
            $html = p_wiki_xhtml($this->getWikiPath(), '', false);
        } else {
            $html = DokuWiki_Plugin::render($wikitekst);
        }
        
        $conf['allowdebug'] = $keep;

        $this->setHtmlBody($html);
    }
    
    public function update_db($userid=null, $time=null) {
        if(MinSide::DEBUG) msg('update db kallt');
        global $msdb;
        
        $safeid = $msdb->quote($this->getId());
        $safeomrade = $msdb->quote($this->getOmrade());
        $safesticky = ($this->isSticky()) ? '1' : '0';
        $safetype = $msdb->quote($this->getType());
        $safewikipath = $msdb->quote($this->getWikiPath());
        $safeimgpath = $msdb->quote($this->getImagePath());
        $safetitle = $msdb->quote($this->getTitle());
        $safehtmlbody = $msdb->quote($this->getHtmlBody());
        $safewikihash = $msdb->quote($this->getWikiHash());
		$safepubtime = ($this->getPublishTime()) ? $msdb->quote($this->getPublishTime()) : 'NULL';
        
        if(strlen($safehtmlbody) > 100000) throw new Exception('Nyhet body for lang. Maks 50 000 tegn.');
        
        $midsql = "omrade = $safeomrade,
                nyhetstype = $safetype,
                wikipath = $safewikipath,
                wikihash = $safewikihash,
				issticky = $safesticky,
                nyhettitle = $safetitle,
                imgpath = $safeimgpath,
                nyhetbodycache = $safehtmlbody,
				pubtime = $safepubtime
                ";
                
        if (!$this->isSaved()) {
            $createtime = ($time) ? "'".$time."'" : 'NOW()';
            $createby = ($userid) ?: MinSide::getUserID();
            $presql = "INSERT INTO nyheter_nyhet SET\n";
            $presql .= "nyhetid = DEFAULT,\n";
            $presql .= "createtime = $createtime,\n";
            $presql .= "createby = '$createby',\n";
            $postsql = ";";
        } else {
            $presql = "UPDATE nyheter_nyhet SET\n";
            $presql .= "modtime = NOW(),\n";
			$presql .= "modby = " . MinSide::getUserID() . ",\n";
            $postsql = "WHERE nyhetid = $safeid LIMIT 1;";
        }
        
        $sql = $presql . $midsql . $postsql;
        $res = $msdb->exec($sql);
        
        if(!$this->isSaved()) {
            $this->setId($msdb->getLastInsertId());
        }
        
        $this->_issaved = true;
        
        foreach($this->_dbcallback as $func) {
            if(MinSide::DEBUG) msg('Running callback!');
            $func($this->getId());
        }
        
        $this->_hasunsavedchanges = false;
        // return true dersom db ble endret
        return (bool) $res;
    }
    
    public function getReadList($force_reload=false) {
        if (!$this->isSaved()) throw new Exception('Kan ikke hente stats for nyhet som ikke er lagret');
        if ($force_reload || !isset($this->_arreadlist)) {
            $this->_loadReadList();
        }
        return $this->_arreadlist;
    }
    
    protected function _loadReadList() {
        if(MinSide::DEBUG) msg('Loading read-list for nyhet: ' . $this->getId());
        
        global $msdb;
        
        $omrade = $this->getOmrade();
        $nyhetid = $this->getId();
        $safeomrade = $msdb->quote($omrade);
        $safenyhetid = $msdb->quote($nyhetid);
        $sql = "
        SELECT
            users.id AS brukerid,
            users.wikiname AS wikiname,
            users.wikifullname AS brukerfullnavn,
            users.wikiepost AS brukerepost,
            users.wikigroups AS brukergrupper,
            lest.readtime AS readtime,
            lest.readtime IS NULL AS ikkelest
        FROM 
                internusers AS users
            LEFT JOIN
                nyheter_lest AS lest 
                    ON lest.brukerid = users.id
                    AND lest.nyhetid = $safenyhetid
        WHERE
            users.isactive = '1'
        ORDER BY
            ikkelest,
            readtime
        ";
        $data = $msdb->assoc($sql);
        
        $arReadList = array();
        foreach($data as $datum) {
            // Må sjekke access på hver bruker :\
            $user['name'] = $datum['wikiname'];
            $user['groups'] = (array) explode(',', $datum['brukergrupper']);
            $datum['adgangsniva'] = $this->getAcl($user);
            if($datum['adgangsniva'] < AUTH_READ) continue;
            
            $arReadList[] = $datum;
        }
        
        $this->_arreadlist = $arReadList;
    }
    
    public function getAcl($user=null) {
        $ns = curNS($this->getWikiPath());
        return self::_checkAcl($ns, $user);
    }
    
    private static function _checkAcl($inNS, $user=null) {
        $objOmrade = NyhetOmrade::OmradeFactory('msnyheter', $inNS);
        if ($objOmrade instanceof NyhetOmrade) {
            return $objOmrade->getAcl($user);
        } else {
            return MSAUTH_NONE;
        }
	}
    
    public static function merk_flere_lest(NyhetCollection $col) {
        global $msdb;
        
        if ($col->length() == 0) return false;
        
        $sql = "INSERT INTO nyheter_lest (nyhetid, brukerid, readtime) VALUES ";
        foreach($col as $objNyhet) {
            $inserts[] = sprintf("('%u', '%u', NOW())", $objNyhet->getId(), MinSide::getUserID());
        }
        $sql .= implode(",\n", $inserts);
        
        return (bool) $msdb->exec($sql);
    }
    
    public static function getBrukereSomHarPublisertNyheter() {
        global $msdb;
        
        $sql = "
            SELECT
                users.id AS id,
                users.wikifullname AS navn
            FROM
                internusers AS users
            LEFT JOIN
                nyheter_nyhet AS nyhet ON users.id = nyhet.createby
            WHERE nyhet.createby IS NOT NULL
            GROUP BY users.id;";
        
        return $msdb->assoc($sql);

    }
    
    public static function getGoogleGraphUri($inputdata, $resolution=null, $innfratid=null, $inntiltid=null) {
        // Inputdata er array med brukere og tidspunkt de har lest en nyhet
        // Resolution er antall sekunder per datapunkt
        // Fra og til tid er timestamps som setter limits for periode som skal vises
        
        $total_users = count($inputdata);
        $sorteddata = array();
        foreach($inputdata as $datum) {
            if($datum['ikkelest'] == 0) {
                $sorteddata[] = strtotime($datum['readtime']);
            }
        }
        sort($sorteddata);
        $siste_datapunkt = $sorteddata[count($sorteddata)-1];
        // Bruker første og siste timestamp som fra/til tid hvis de ikke er gitt
        $fratid = ($innfratid===null) ? $sorteddata[0] : $innfratid;
        $tiltid = ($inntiltid===null) ? $siste_datapunkt: $inntiltid;
        
        if ($tiltid < $fratid) throw new Exception('Data må representere en positiv tidsverdi.');
        
        if($resolution === null) $resolution = ceil(($tiltid - $fratid) / 200); // Totalt 200 datapunkter, gir url på ca 1k tegn.
        if($resolution < 1) $resolution = 1;
        $resolution = (int) $resolution;
        
        // Hvis vi har generert til/fratid, sørg for at første og siste datapunkt vises.
        if($innfratid === null) $fratid -= $resolution;
        if($inntiltid === null) $tiltid += $resolution;
        
        // Fordele data på datapunkter
        $fordeltdata = array();
        foreach($sorteddata as $datum) {
            $sekunderfrastart = $datum - $fratid + 1;
            $index = ceil($sekunderfrastart / $resolution);
            if($index < 1) $index = 1;
            $fordeltdata[$index]++;
        }

        $num_datapoints = ceil(($tiltid - $fratid + 1) / $resolution);
        if($num_datapoints < 1) $num_datapoints = 1;
        $counter_lest = 0;
        $arDataset = array();
        for($i=1;$i<=$num_datapoints;$i++) {
            // Sjekk at vi ikke viser data for tidspunkter i fremtiden
            if(($i * $resolution) + $fratid -1 > time()) {
                $arDataset[] = -1;
            } else {
                $counter_lest += $fordeltdata[$i];
                $arDataset[] = round(($counter_lest / $total_users) * 100);
            }
        }
        
        // Labels
        
        // X2 - dager
        $minst_to_dager = ( date('dmY', $fratid) != date('dmY', $tiltid) );
        $periode_lengde = $tiltid - $fratid;
        $start_sec = (int) date('s', $fratid);
        $start_min = (int) date('i', $fratid);
        $start_time = (int) date('H', $fratid);
        $start_dag = (int) date('j', $fratid);
        $start_md = (int) date('n', $fratid);
        $start_aar = (int) date('Y', $fratid);
        
        $dager_i_periode = floor($periode_lengde / 86400);
        $dager_per_mark = ceil($dager_i_periode / 10);
        
        if($minst_to_dager) {
            $daglabel_val = array();
            $daglabel_pos = array();
            $dagcounter = 1;
            do {
                $mark = mktime(0, 0, 0, $start_md, $start_dag + $dagcounter, $start_aar);
                $mark_fra_start = $mark - $fratid;
                $mark_dag = date('d', $mark);
                $mark_mnd_nr = date('n', $mark);
                $mark_mnd = NyhetGen::$mnd_navn_kort[$mark_mnd_nr];
                $daglabel_val[] = $mark_dag.'. '.$mark_mnd;
                $daglabel_pos[] = round(($mark_fra_start / $periode_lengde) * 100);
                $dagcounter += $dager_per_mark;
            } while($fratid + ($dagcounter * 86400) < $tiltid ); // 86400 = 60*60*24 = 24 timer
            $x2_tekst = '2:|' . implode('|', $daglabel_val);
            $x2_pos = '2,' . implode(',', $daglabel_pos);
        } else {
            $x2_tekst = '2:|<- '.date('d', $fratid) . '. ' . NyhetGen::$mnd_navn_kort[date('n', $fratid)] . date(' Y', $fratid);
            $x2_pos = '2,0';
        }
        
        // X1 - Timer/min/sec
        if($dager_per_mark > 1) {
            $x1_tekst = '1:||';
            $x1_pos = '1,0|';
        } else {
            $arMuligeResolutions = array(1, 2, 5, 10, 15, 30, 60, 90, 120, 300, 600, 900, 1800, 3600, 7200, 10800, 14400, 43200);
            foreach($arMuligeResolutions as $res) {
                if ($periode_lengde / $res <= 12) break;
            }
            if($res < 120) {
                $tidsformat = 'H:i:s';
            } else {
                $tidsformat = 'H:i';
            }
            
            $arX1 = self::getTimeMarks($fratid, $tiltid, $res, $tidsformat);
                        
            $x1_tekst = '1:|' . implode('|', $arX1[0]) . '|';
            $x1_pos = '1,' . implode(',', $arX1[1]) .'|';
        }
        
        if(MinSide::DEBUG) {
            msg('Times: ' . implode(', ', $arX1[0]));
            msg('Pos: ' . implode(', ', $arX1[1]));
        }
        
        
        // Gen URI
        $dataset = implode(',', $arDataset);
        $googleurl=
            "http://chart.apis.google.com/chart" .
            "?chxs=0N**%25,676767,13,0,l,676767" . // Aksedetaljer
                "|1,676767,9,0,lt,676767" .
                "|2,436976,13,0,lt,436976" .
            "&chxtc=1,5|2,10" . // Akse tick mark style
            "&chxt=y,x,x" . // Akser vist
            "&chxl=" . // Custom labels
                $x1_tekst .
                $x2_tekst .
            "&chxp=" . // Label positions
                $x1_pos .
                $x2_pos .
            "&chs=650x450" . // Image size
            "&cht=lc" . // Graph type
            "&chco=436976" . // Linje-farge (data)
            "&chd=t:$dataset" . // Data
            "&chg=10,5,1,3" . // Grid style
            "&chls=2,4,0" . // Line style
            "&chm=B,DEE7ECBB,0,0,0"; // Fill under kurve
        if(strlen($googleurl) > 2000 ) throw new Exception('Feil under generering av graf. URI for lang.');
        return $googleurl;
    }
    
    private static function getTimeMarks($starttime, $endtime, $resolution, $tidsformat) {
        $midnatt_start = mktime(0,0,0,date('n', $starttime),date('j', $starttime),date('Y', $starttime));
        $sek_fra_midnatt = $starttime - $midnatt_start;
        $forste_mark = (ceil($sek_fra_midnatt / $resolution) * $resolution) + $midnatt_start;
        $periode_lengde = $endtime - $starttime;
        
        $mark_array = array();
        $pos_array = array();
        for($i = $forste_mark; $i <= $endtime; $i += $resolution) {
            $mark_array[] = date($tidsformat, $i);
            
            $mark_fra_start = $i - $starttime;
            $pos_array[] = round(($mark_fra_start / $periode_lengde) * 100);
        }
        
        return array($mark_array, $pos_array);
        
    }
}
