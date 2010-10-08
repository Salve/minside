<?php
if(!defined('MS_INC')) die();

class OppdragCollection extends Collection {
	public function addItem(Oppdrag $obj, $key = null) {
		parent::addItem($obj, $key);
	}
}