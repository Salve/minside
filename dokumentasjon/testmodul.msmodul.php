<?php
if(!defined('MS_INC')) die();
class msmodul_testmodul implements msmodul{

	private $_msmodulact; // "oppgaven" som skal utføres - kan komme fra $_REQUEST['act'] eller være satt i et script
	private $_msmodulvars; // ikke i bruk per d.d. - tanken er at variabler kan overføres som array når modulen lastes av hovedside (og ikke direkte basert på userinput).
	private $_userID; // id i 'internusers' tabell som matcher brukernavn på innlogget DokuWiki-bruker
	private $_adgangsNiva; // int som angir innlogget brukers rettigheter for denne modulen, se toppen av minside.php for mulige verdier.
	
	public function __construct($UserID, $AdgangsNiva) { // kalles automatisk når alle moduler lastes inn, ingen output eller tungt arbeid her, ikke sikkert modulen vil bli brukt at all.
	
		$this->_userID = $UserID;
		$this->_adgangsNiva = $AdgangsNiva;
	}
	
	public function gen_msmodul($act, $vars){ // kalles av dispatcher når output ønskes fra denne modulen
		$this->_msmodulact = $act;
		$this->_msmodulvars = $vars;
		
		$output .= 'Dette er output fra testmodul! UserId er: '. $this->_userID . ' act er: ' . $this->_msmodulact . '<br />'; // En streng med litt info bygges i en variabel
																																// echo/print skal ikke benyttes
		
		return $output; // Oppbygd streng returneres til minside.php via dispatcher, vil bli inkludert i annen output fra minside.
	
	}
	
	public function registrer_meny(MenyitemCollection &$meny){ // $meny er en samling "Menyitems" som sendes på runde til alle lastede moduler.
		$lvl = $this->_adgangsNiva;
	
		if ($lvl > MSAUTH_NONE) { // Moduler bør ikke vise noe i menyen med mindre brukeren har tilatelse til å benytte modul
			$toppmeny = new Menyitem('Testmodul','&page=testmodul'); // ?do=minside kommer automatisk på url, bare angi det som er relevant for modulen
			if (isset($this->_msmodulact)) { // Viser kun undermenyer dersom modulen har blitt generert
				if ($lvl == MSAUTH_ADMIN) {
					$toppmeny->addChild(new Menyitem('TestAdmin','&page=testmodul&act=admin'));
				}
			}
			$meny->addItem($toppmeny);
		}
		
		/*
		 * Eksempler på meny-bygging
		 * $meny er en Collection og aksepterer kun Menyitem objekter via ->addItem()-metoden.
		 *
		 * Menyitem objekter opprettes med to parametre, Teksten som skal vises i menyen og streng som skal legges til i slutten av url
		 * Menyitem objekter kan ha en Collection med andre Menyitem objekter. 
		 *		$undermeny = new MenyitemCollection();
		 *		$undermeny->addItem(new Menyitem('tekst','url'));
		 *		$undermeny->addItem(new Menyitem('tekst2','url2'));
		 *		
		 *		$undermeny kan så legges til et Menyitem-objekt ved å bruke ->addChildren()-metoden: $toppmeny->addChildren($undermeny))
		 *		Som en snarvei kan man også legge til ett og ett menyitem til et annet menyitem med ->addChild()-metoden. En Collection opprettes da automatisk internt i Menyitemet.
		 */
		
		
	}
	
}
