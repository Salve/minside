<?php
if(!defined('MS_INC')) die();

class BrukerCollection extends Collection {
	public function addItem(Bruker $obj, $key = null) {
		parent::addItem($obj, $key);
	}
}