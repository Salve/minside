<?php
if(!defined('MS_INC')) die();
class Erstatter {

	private $_arPatterns = array();
	private $_arReplacements = array();
	
	public function addErstattning($pattern, $replacement) {
		if (is_string($pattern) && is_string($replacement)) {
			$this->_arPatterns[] = $pattern;
			$this->_arReplacements[] = $replacement;
			return true;
		} else {
			return false;
		}
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

}
