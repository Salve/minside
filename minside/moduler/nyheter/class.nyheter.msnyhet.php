<?php
if(!defined('MS_INC')) die();

abstract class MsNyhet {
	
	protected $_id;	
	protected $_tilgang;
	protected $_type;
	protected $_kategori;
	protected $_createtime;
	protected $_lastmodtime;
	protected $_deletetime;
	protected $_issaved;
	
	protected $_htmlheader;
	protected $_htmlbody;
	protected $_wikitekst;
	
	protected function __construct() {	}
	
	public function getId() {
		return $this->_id;
	}
	public function getTilgang() {
		return $this->_tilgang;
	}
	public function getKategori() {
		return $this->_kategori;
	}
	public function getCreateTime() {
		return $this->_createtime;
	}
	public function getLastModTime() {
		return $this->_lastmodtime;
	}
	public function isModified() {
		return isset($this->_lastmodtime);
	}
	public function getDeleteTime() {
		return $this->_deletetime;
	}
	public function isDeleted() {
		return isset($this->_deletetime);
	}
	public function getHtmlHeader() {
		return $this->_htmlheader;
	}
	public function getHtmlBody() {
		return $this->_htmlbody;
	}
	public function getWikiTekst() {
		return $this->_wikitekst;
	}
	public function isSaved() {
		return (bool) $this->_issaved;
	}
	
}