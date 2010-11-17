<?php
if(!defined('MS_INC')) die();

class ActCollection extends Collection {
	public function addItem(MsAct $obj, $key = null) {
		parent::addItem($obj, $key);
	}
}