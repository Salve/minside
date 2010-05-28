<?php
if(!defined('MW_INC')) die();
interface mwmodul{
	
	function __construct($UserID, $AdgangsNiva);

	public function gen_mwmodul($act, $vars);
	
	public function registrer_meny(MenyitemCollection &$meny);

}
