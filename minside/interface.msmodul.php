<?php
if(!defined('MS_INC')) die();
interface msmodul{
	
	function __construct($UserID, $AdgangsNiva);

	public function gen_msmodul($act, $vars);
	
	public function registrer_meny(MenyitemCollection &$meny);

}
