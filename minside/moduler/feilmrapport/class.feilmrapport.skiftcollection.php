<?php
if(!defined('MS_INC')) die();

class SkiftCollection extends Collection {
	public function addItem(Skift $obj, $key = null) {
		parent::addItem($obj, $key);
	}
}
