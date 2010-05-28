<?php
if(!defined('MS_INC')) die();

class Menyitem {
	private $_tekst;
	private $_href;
	private $_hasChildren = false;
	private $_children;
	
	function __construct($tekst, $href) {
		$this->_tekst = $tekst;
		$this->_href = $href;
	}
	
	public function getTekst() {
		return $this->_tekst;
	}
	
	public function getHref() {
		return $this->_href;
	}
	
	public function addChildren(MenyitemCollection $col) {
		$this->_hasChildren = true;
		if(isset($this->_children)) {
			foreach ($col as $child) {
				$this->_children->addItem($child);
			}
		} else {
			$this->_children = $col;
		}
		
	}
	
	public function addChild(Menyitem $child) {
		$this->_hasChildren = true;
		if(isset($this->_children)) {
			$this->_children->addItem($child);
		} else {
			$this->_children = new MenyitemCollection();
			$this->_children->addItem($child);
		}
	}
	
	public function getChildren() {
		return $this->_children;
	}
	
	public function hasChildren() {
		if ($this->_hasChildren === true) {
			return true;
		} else {
			return false;
		}
	}
	
	public function __toString() {
		$output = '<a href ="' . $this->_href . '">' . $this->_tekst . '</a>';
		return $output;
	}
	
}