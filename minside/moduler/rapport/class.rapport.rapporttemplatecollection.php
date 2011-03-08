<?php
if(!defined('MS_INC')) die();

class RapportTemplateCollection extends Collection {
	public function addItem(RapportTemplate $obj, $key = null) {
		parent::addItem($obj, $key);
	}
}
