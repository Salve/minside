<?php
if(!defined('MS_INC')) die();

class RapportDataCollection extends Collection {
	public function addItem(RapportData $obj, $key = null) {
		parent::addItem($obj, $key);
	}
}