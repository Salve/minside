<?php
if(!defined('MS_INC')) die();

class RapportTeamCollection extends Collection {
	public function addItem(RapportTeam $obj, $key = null) {
		parent::addItem($obj, $key);
	}
}