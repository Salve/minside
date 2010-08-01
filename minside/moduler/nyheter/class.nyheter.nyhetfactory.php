<?php
if(!defined('MS_INC')) die();
require_once('class.nyheter.msnyhet.php');
require_once('class.nyheter.nyhetcollection.php');

class NyhetFactory {

    private function __construct() { }
    
    public static function getTestNyhet() {
    
        $objNyhet = new MsNyhet();
        
		$objNyhet->setType(1);
		$objNyhet->setTilgang('feilm');
		$objNyhet->setViktighet(2);
		$objNyhet->setTitle('En liten testnyhet');
		$objNyhet->setHtmlBody(nl2br(
            '<strong>Her er nyheten!</strong> Lorem ipsum dolor sit amet osv. En masse tekst kommer som regel inn i nyheter. Blandt annet punktlister og sånt, men det får vi ta senere. Enda en setning hives på.
            
            For good measure tar vi også et avsnitt nummer to. Dette trenger ikke være like langt, men vi vil gjerne ha litt tekstbrytning for å sjekke at layout er lesbar.'
        ));
        
        return $objNyhet;
    
    }
    
}