<?php
if(!defined('MS_INC')) die();

class NyhetCollection extends Collection {
	public function addItem(MsNyhet $obj, $key = null) {
		parent::addItem($obj, $key);
	}
}