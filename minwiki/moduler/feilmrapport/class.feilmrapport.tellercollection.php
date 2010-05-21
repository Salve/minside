<?php
if(!defined('MW_INC')) die();

class TellerCollection extends Collection {
	public function addItem(Teller $obj, $key = null) {
		parent::addItem($obj, $key);
	}
}
