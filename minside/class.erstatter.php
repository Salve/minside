<?php
if(!defined('MS_INC')) die();
class Erstatter {

	private $_arPatterns = array();
	private $_arReplacements = array();
	
	public function addErstattning($pattern, $replacement) {
		$this->_arPatterns[] = $pattern;
		$this->_arReplacements[] = $replacement;
	}
	
	public function erstatt($inputstreng) {
		
		$output = preg_replace($this->_arPatterns, $this->_arReplacements, $inputstreng);
		return $output;
	
	}
	
	public function getPatterns() {
		return $this->_arPatterns;
	}
	
	public function getReplacements() {
		return $this->_arReplacements;
	}
	
	public function getReplacement($key) {
		if (isset($this->_arReplacements[$key])) {
			return $this->_arReplacements[$key];
		} else {
			return false;
		}
	}

}
