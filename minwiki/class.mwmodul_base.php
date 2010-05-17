<?php
require_once('interface.mwmodul.php');

abstract class mwmodul_base{


	abstract function __construct();

	abstract function gen_mwmodul($act, $vars);


}