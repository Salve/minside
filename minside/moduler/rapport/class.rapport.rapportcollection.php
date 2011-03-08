<?php
if(!defined('MS_INC')) die();

class RapportCollection extends Collection {
	public function addItem(Rapport $obj, $key = null) {
		parent::addItem($obj, $key);
	}
}
