<?php
if(!defined('MS_INC')) die();

class NyhetTagCollection extends Collection {
	public function addItem(NyhetTag $obj, $key = null) {
		parent::addItem($obj, $key);
	}
}