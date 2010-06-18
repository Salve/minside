<?php
if(!defined('MS_INC')) die();

class Rapport {
	private $_id;
	private $_rapportCreatedTime;
	private $_rapportFromTime;
	private $_rapportToTime;
	private $_rapportOwnerId;
	private $_isSaved = false;
	public $rapportSent = false;
	
	public $skift;
	
	public function __construct($ownerid, $id = null, $createdtime = null, $issaved = false) {
		$this->_id = $id;
		$this->_rapportCreatedTime = $createdtime;
		$this->_rapportOwnerId = $ownerid;
		$this->_rapportFromTime = $fromtime;
		$this->_rapportToTime = $totime;
		$this->_isSaved = (bool) $issaved;
		
		$this->skift = new SkiftCollection();
		if ($issaved) $this->skift->setLoadCallback('_loadSkift', $this);
	}
	
	public function getId() {
		return $this->_id;
	}
	
	public function getRapportCreatedTime() {
		return $this->_rapportCreatedTime;
	}
	
	public function getRapportFromTime() {
		return $this->_rapportFromTime;
	}
	
	public function getRapportToTime() {
		return $this->_rapportToTime;
	}
	
	public function getRapportOwnerId() {
		return $this->_rapportOwnerId;
	}
	
	public function isSent() {
		return $this->rapportSent;
	}
	
	public function __toString() {
		return 'RapportID: ' . $this->_id . ', OwnerID: ' . $this->_rapportOwnerId . '.';
	}
	
	public function _loadSkift(Collection $col) {
		$arSkift = SkiftFactory::getSkiftForRapport($this->_id, $col);
	}
	
	
	public function nyRapport(SkiftCollection $skiftcol, $validinput = array(), $invalidinput = array(), $saverapport = false) {
			global $INFO;
			$totaltellere = array();
			$brukertellere = array();
			
			$tellercol = new TellerCollection();
			$notatcol = new NotatCollection();
			
			$tplErstatter = new Erstatter();
			
			
			// for hvert skift som er gitt funksjonen
			foreach ($skiftcol as $objSkift) {
				// for hvert notat i hvert skift (disse blir hentet fra db her,  via callback på collection)
				foreach ($objSkift->notater as $objNotat) {
					// ignorer inaktive notater (de som er slettet av bruker)
					if ($objNotat->isActive()) {
						// hvis dette notatet er markert som valgt
						if (is_array($validinput['selnotat']) && in_array($objNotat->getId(), $validinput['selnotat'])) {
							// til bruk når det er validation errors og rapport-in-progress skal vises
							$checked = 'checked="yes" ';
							// til bruk når det ikke er noen validation errors og rapport skal lagres (altså uten checkbox)
							$notatsaveoutput .= '<li>' . $objNotat . ' (' . $objSkift->getSkiftOwnerName() . ")</li>\n";
						} else {
							$checked = '';
						}
						// til bruk når rapport ikke er forsøkt lagret enda, eller forsøkt lagret med validation errors
						$notatoutput .= '<input type="checkbox" ' . $checked . 'name="rappinn[selnotat][]" value="' . $objNotat->getId() . '" /> '
							. $objNotat . ' (' . $objSkift->getSkiftOwnerName() . ")<br />\n";
						
					}
				}
				// for hver teller for hver skift (db load her)
				foreach ($objSkift->tellere as $objTeller) {
					// ignorer tellere som ikke er økt
					if ($objTeller->getTellerVerdi() > 0) {
						// hvis denne telleren allerede har blitt lagt til teller-collection for dette skiftet (fra et annnet skift, tidligere i loopen)
						if ($tellercol->exists($objTeller->getTellerName())) {
							// hent ut telleren som allerede er lagt til (by ref)
							$objColTeller = $tellercol->getItem($objTeller->getTellerName());
							// endre verdien på denne telleren
							$objColTeller->setTellerVerdi($objColTeller->getTellerVerdi() + $objTeller->getTellerVerdi());
						// denne telleren er ikke sett før for denne rapporten
						} else {
							// lager ny teller med data fra orginal teller
							$objColTeller = new Teller($objTeller->getId(), $objSkift->getId(), $objTeller->getTellerName(), $objTeller->getTellerDesc(), $objTeller->getTellerType(), $objTeller->getTellerVerdi());
							// legger denne til i rapportens teller-collection, men navnet på teller (eks. "TLSPT") som key.
							$tellercol->addItem($objColTeller, $objTeller->getTellerName());
						}
						
						if ($objTeller->getTellerType() == 'ULOGGET') {
							// bygger opp et array med navn => verdi for alle tellere av type ulogget.
							$uloggettotaler[$objTeller->getTellerName()] += $objTeller->getTellerVerdi();
						}
						
						$tellertotaler[$objTeller->getTellerName()] += $objTeller->getTellerVerdi();						
						$brukertellere[$objTeller->getTellerName()][$objSkift->getSkiftOwnerName()] += $objTeller->getTellerVerdi();
					}
				}
			}
			
			
			foreach ($brukertellere as $tellername => $bruker) {
				
				foreach ($bruker as $brukernavn => $brukertotal) {
					if ($brukertotal > 0) {
						$brukertellernotater["$tellername"] .= $brukernavn . ': ' . $brukertotal . ' (' . round($brukertotal / $tellertotaler[$tellername] * 100) . '%) ';
					}
				}
				
				if ($uloggettotaler[$tellername] > 0) {
					$objTeller = $tellercol->getItem($tellername);
					$uloggetoutput .= '<span title="' . $brukertellernotater[$tellername] . '">' . $objTeller->getTellerDesc() . ': ' . $objTeller->getTellerVerdi() . "</span><br />\n";
				}

			}
			
			foreach ($validinput['selskift'] as $skiftid) {
				$hiddenSkiftider .= '<input type="hidden" name="selskift[]" value="' . $skiftid . "\" />\n";
			}
			
			// Definer [[data:datumnavn]] som skal replaces her.
			$tpldata['fulltnavn'] = $INFO['userinfo']['name'];
			$tpldata['datotid'] = date('dmy \&\n\d\a\s\h\; H:i');
			
			if (!$notatoutput) $notatoutput = 'Ingen notater.';
			if (!$notatsaveoutput) {
				$notatsaveoutput = 'Ingen notater.';
			} else {
				$notatsaveoutput = '<ul>' . $notatsaveoutput . '</ul>';
			}
			if (!$uloggetoutput) $uloggetoutput = 'Ingen uloggede samtaler.';
			
	
			
			// Erstatter [[notater:annet]] med notat-output. 
			$funcErstattNotat = function ($matches) use (&$notatoutput, &$notatsaveoutput, $saverapport) {
				if ($saverapport) {
					return $notatsaveoutput;
				} else {
					return $notatoutput;
				}
			};
			$tplErstatter->addErstattning('/\[\[notater:[A-Za-z]+\]\]/u', $funcErstattNotat);
			
			// Erstatter [[ulogget]] med ulogget-output. 
			$funcErstattUlogget = function ($matches) use (&$uloggetoutput) {
				return $uloggetoutput;
			};
			$tplErstatter->addErstattning('/\[\[ulogget\]\]/u', $funcErstattUlogget);
			
			// Erstatter [[teller:TELLERNAVN]] med tallverdien til telleren
			$funcErstattTeller = function ($matches) use (&$brukertellernotater, &$tellercol) {
				$key = $matches[1];
				$objTeller = $tellercol->getItem($key);
				if ($objTeller instanceof Teller) {
					$telleroutput = '<span title="' . $brukertellernotater[$key] . '">' . ((string) $objTeller->getTellerVerdi()) . '</span>';
				} else {
					$telleroutput = '<span>0</span>';
				}
				return $telleroutput;
			};
			$tplErstatter->addErstattning('/\[\[teller:([A-Z]+)\]\]/u', $funcErstattTeller); 
			
			// Erstatter [[inputbool:varname]] med ja/nei dropdown input
			$funcErstattInputBool = function ($matches) use (&$validinput, &$invalidinput, $saverapport, $validationerrors) {
				$varname = $matches[1];
				if ($saverapport) {
					$output = ($validinput['bool']["$varname"]) ? 'Ja' : 'Nei';
				} else {
					if (isset($validinput['bool']["$varname"])) {
						$output = '<select name="rappinn[bool][' . $varname . ']"><option value="NOSEL">Velg:</option><option value="True" ' . (($validinput['bool']["$varname"]) ? 'selected="yes"' : '') . '>Ja</option><option value="False"' . (($validinput['bool']["$varname"]) ? '' : 'selected="yes"') . '>Nei</option></select>';
					} else {
						$output = '<select name="rappinn[bool][' . $varname . ']"><option value="NOSEL">Velg:</option><option value="True">Ja</option><option value="False">Nei</option></select>';
						if (isset($invalidinput['bool']["$varname"])) {
							$output .= '<img src="/wiki/lib/images/error.png" title="' . $invalidinput['bool']["$varname"] . '">';
						}
					}
				}
				return $output;
			};
			$tplErstatter->addErstattning('/\[\[inputbool:([A-Za-z]+)\]\]/u', $funcErstattInputBool);
			
			// Erstatter [[inputtekst:varname]] med tekst-input felt
			$funcErstattInputTekst = function ($matches) use (&$validinput, &$invalidinput, $saverapport, $validationerrors) {
				$varname = $matches[1];
				if ($saverapport) {
					$output = $validinput['tekst']["$varname"];
				} else {
					if (isset($validinput['tekst']["$varname"])) {
						$output = '<input type="tekst" maxlength="250" name="rappinn[tekst][' . $varname . ']" value="' . $validinput['tekst']["$varname"] . '" />';
					} else {
						$output = '<input type="tekst" maxlength="250" name="rappinn[tekst][' . $varname . ']" />';
						if (isset($invalidinput['tekst']["$varname"])) {
							$output .= '<img src="/wiki/lib/images/error.png" title="' . $invalidinput['tekst']["$varname"] . '">';
						}
					}
				}
				return $output;
			};
			$tplErstatter->addErstattning('/\[\[inputtekst:([A-Za-z]+)\]\]/u', $funcErstattInputTekst);
			
			// Erstatter [[inputlitetall:varname]] med 3-siffret tekst-input
			$funcErstattInputLiteTall = function ($matches) use (&$validinput, &$invalidinput, $saverapport, $validationerrors) {
				$varname = $matches[1];
				if ($saverapport) {
					$output = $validinput['litetall']["$varname"];
				} else {
					if (isset($validinput['litetall']["$varname"])) {
						$output = '<input type="text" maxlength="3" size="2" name="rappinn[litetall][' . $varname .  ']" value="' . $validinput['litetall']["$varname"] . '" />';
					} else {
						$output = '<input type="text" maxlength="3" size="2" name="rappinn[litetall][' . $varname . ']" />';
						if (isset($invalidinput['litetall']["$varname"])) {
							$output .= '<img src="/wiki/lib/images/error.png" title="' . $invalidinput['litetall']["$varname"] . '">';
						}
					}
				}
				return $output;
			};
			$tplErstatter->addErstattning('/\[\[inputlitetall:([A-Za-z]+)\]\]/u', $funcErstattInputLiteTall);

			// Erstatter [[data:varname]] med $tpldata[varname]
			$funcErstattData = function ($matches) use (&$tpldata) {
				$varname = $matches[1];
				if (isset($tpldata[$varname])) {
					return $tpldata[$varname];
				} else {
					return "Verdi av \"$varname\" ikke funnet";
				}
			};
			$tplErstatter->addErstattning('/\[\[data:([A-Za-z]+)\]\]/u', $funcErstattData);
		
			
			
			
			$tmpOutput = RapportTemplate::getTemplate($tplErstatter);
			
			
			
			
			$output .= $hiddenSkiftider;
			$output .= $tmpOutput;
			
			
			
			
			
			
			
			return $output;
	}

}
