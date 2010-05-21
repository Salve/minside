<?php
if(!defined('MW_INC')) die();
class CollectionIterator implements Iterator {

	private $_collection;
	private $_currIndex = 0;
	private $_keys;
	
	function __construct(Collection $objCol) {
		$this->_collection = $objCol;
		$this->_keys = $this->_collection->keys();
	}
	
	function rewind() {
		$this->_currIndex = 0;
	}
	
	function hasMore() {
		return $this->_currIndex < $this->_collection->length();
	}
	
	function key() {
		return $this->_keys[$this->_currIndex];
	}
	
	function current() {
		return $this->_collection->getItem($this->_keys[$this->_currIndex]);
	}
	
	function next() {
		$this->_currIndex++;
	}
	
	function valid() {
		return isset($this->_keys[$this->_currIndex]);
	}


}
