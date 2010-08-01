<?php
if(!defined('MS_INC')) die();

class MsNyhet {
	
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
	
	public function __construct($isSaved = false) {
		$this->_issaved = $isSaved;
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
		$this->_tilgang = $input;
		return true;
	}
	
	public function getViktighet() {
		return $this->_viktighet;
	}
	public function setViktighet($input) {
		$this->_viktighet = $input;
		return true;
	}
	
	public function getCreateTime() {
		return $this->_createtime;
	}
	public function setCreateTime($input) {
		$this->_createtime = $input;
		return true;
	}

	
	public function getLastModTime() {
		return $this->_lastmodtime;
	}
	public function setLastModTime($input) {
		$this->_lastmodtime = $input;
		return true;
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
		$this->_deletetime = $input;
		return true;
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
		$this->_imgpath = $input;
		return true;
	}
	
	public function getWikiPath() {
		return $this->_wikipath;
	}
	public function setWikiPath($input) {
		$this->_wikipath = $input;
		return true;
	}
	
	public function getTitle() {
		return $this->_title;
	}	
	public function setTitle($input) {
		$this->_title = $input;
		return true;
	}
	
	public function getHtmlBody() {
		return $this->_htmlbody;
	}
	public function setHtmlBody($input) {
		$this->_htmlbody = $input;
		return true;
	}

	public function getWikiTekst() {
		return $this->_wikitekst;
	}
	public function setWikiTekst($input) {
		$this->_wikitekst = $input;
		return true;
	}

	public function getWikiHash() {
		return $this->_wikihash;
	}
	public function setWikiHash($input) {
		$this->_wikihash = $input;
		return true;
	}

	public function isSaved() {
		return (bool) $this->_issaved;
	}
	
	protected function set_var(&$var, &$value) {
		
		if (($this->_issaved && isset($var)) || !$this->_issaved) {
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
		
		$id = $this->getWikiPath();
		$text = $this->getWikiText();
		$summary = 'Nyhetsendring utført gjennom MinSide.';
		$minor = false;
		
		$resultat = saveWikiText($id, $text, $summary, $minor);
		$strResultat = (string) $resultat;
		msg('saveWikiText kallt, resultat: ' . $strResultat);
	
	}
	
}