<?php
if(!defined('MW_INC')) die();
interface mwmodul{
	
	function __construct($UserID, &$dbHandle);

	function gen_mwmodul($act, $vars);

}