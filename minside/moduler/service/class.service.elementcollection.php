<?php
if(!defined('MS_INC')) die();

class ElementCollection extends Collection {
	public function addItem(OppdragElement $obj, $key = null) {
		parent::addItem($obj, $key);
	}
}