<?php
if(!defined('MS_INC')) die();

class RappValidator {
	
	public static function ValBool($input, &$output, &$error) {
		if ($input === 'True') {
			$output = true;
			$error = null;
			return true;
		} elseif ($input === 'False') {
			$output = false;
			$error = null;
			return true;
		} else {
			$output = null;
			$error = 'Velg ja eller nei.';
			return false;
		}
	}
	
	public static function ValLiteTall($input, &$output, &$error) {
		
		$input = trim($input);
		$result = preg_match('/^[0-9]{1,3}$/uAD', $input, $matches);
		
		if ($result) {
			$output = (int) $matches[0];
			$error = null;
			return true;
		} else {
			$output = null;
			$error = 'Må være gyldig tall, 1-3 siffer';
			return false;
		}
		
	}
	
	public static function ValTekst($input, &$output, &$error) {
		
		$input = htmlspecialchars(trim($input));
		
		if (strlen($input) > 250) {
			$output = null;
			$error = 'Tekst er for lang, maksimalt 250 tegn.';
			return false;
		} elseif(strlen($input) < 1) {
			$output = null;
			$error = 'Felt kan ikke være tomt';
			return false;
		} else {
			$output = $input;
			$error = null;
			return true;
		}
		
	}
	
}
