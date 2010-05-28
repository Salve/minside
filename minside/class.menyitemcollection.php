<?php
if(!defined('MW_INC')) die();

class MenyitemCollection extends Collection {
	public function addItem(Menyitem $obj, $key = null) {
		parent::addItem($obj, $key);
	}
}