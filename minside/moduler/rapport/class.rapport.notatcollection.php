<?php
if(!defined('MS_INC')) die();

class NotatCollection extends Collection {
	public function addItem(Notat $obj, $key = null) {
		parent::addItem($obj, $key);
	}
}
