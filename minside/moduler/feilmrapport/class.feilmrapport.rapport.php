<?php
if(!defined('MS_INC')) die();

class Rapport {
	private $_id;
	private $_rapportCreatedTime;
	private $_rapportOwnerId;
	private $_rapportOwnerName;
	private $_rapportTemplateId;
	private $_isSaved = false;
	
	public $skift;
	public $rapportdata = array();
	private $_rapportdataloaded = false;
	
	public function __construct($ownerid, $id = null, $createdtime = null, $issaved = false, $templateid = null, $ownername = null) {
		$this->_id = $id;
		$this->_rapportCreatedTime = $createdtime;
		$this->_rapportOwnerId = $ownerid;
		$this->_rapportOwnerName = $ownername;
		if ($templateid) {
			$this->_rapportTemplateId = $templateid;
		} else {
			$templateid = RapportTemplateFactory::getCurrentTplId();
			if (!$templateid === false) $this->_rapportTemplateId = $templateid;
		}
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
	
	public function getRapportOwnerId() {
		return $this->_rapportOwnerId;
	}
	
	public function getRapportOwnerName() {
		if (isset($this->_rapportOwnerName)) {
			return $this->_rapportOwnerName;
		} else {
			return 'OwnerID: ' . $this->_rapportOwnerId;
		}
	}
	
	public function isSent() {
		return $this->rapportSent;
	}
	
	public function setSkiftCol(SkiftCollection &$skiftcol) {
		if ($skiftcol->length() > 0) {
			$this->skift = $skiftcol;
			return true;
		} else {
			return false;
		}
	}
	
	public function __toString() {
		return 'RapportID: ' . $this->_id . ', OwnerID: ' . $this->_rapportOwnerId . '.';
	}
	
	public function _loadSkift(Collection $col) {
		$arSkift = SkiftFactory::getSkiftForRapport($this->_id, $col);
	}
	
	public function getRapportData() {
		if (!$this->_rapportdataloaded) {
			$this->_rapportdataloaded = true;
			$this->rapportdata = SkiftFactory::getDataForRapport($this->_id);
		}
		return $this->rapportdata;
	}
	
	public function genRapport() {
		if (!$this->_isSaved) die('Funksjonen genRapport() kan kun vise rapporter fra database');
		return $this->_genRapport($this->getRapportData(), array(), true);
	
	}
	
	public function genRapportTemplate() {
		return $this->_genRapport();
	}
	
	public function genRapportTemplateErrors($validinput, $invalidinput) {
		if (!isset($validinput)) $validinput = array();
		if (!isset($invalidinput)) $invalidinput = array();
		
		return $this->_genRapport($validinput, $invalidinput, false);
	}
	
	public function lagreRapport($validinput) {
		global $INFO;
		global $msdb;
		if ($this->_isSaved) throw new Exception('Denne rapporten er allerede lagret');
		if (!isset($this->_rapportOwnerId)) throw new Exception('Rapport-eier ikke angitt');
		if (!is_array($validinput)) throw new Exception('Ingen data gitt');
		if (!($this->skift->length() > 0)) throw new Exception('Ingen skift lastet i rapportobjekt');
		if (!isset($this->_rapportTemplateId)) throw new Exception('Intet aktivt rapport-template');
		
		$skiftcol = $this->skift;
		
		$this->_rapportCreatedTime = date("Y-m-d H:i:s");
		$saferapportcreated = $msdb->quote($this->_rapportCreatedTime);
		$saferapportowner = $msdb->quote($this->_rapportOwnerId);
		$safetemplateid = $msdb->quote($this->_rapportTemplateId);
		
		$sql = "INSERT INTO feilrap_rapport (createtime, rapportowner, templateid) VALUES ($saferapportcreated, $saferapportowner, $safetemplateid);";
		$msdb->exec($sql);
		$this->_id = $msdb->getLastInsertId();
		$saferapportid = $msdb->quote($this->_id);
		
		$arSkift = array();
		foreach ($skiftcol as $objSkift) {
			$arSkift[] = $objSkift->getId();
		}
		$skiftidlist = implode(',', $arSkift);
		$sql = "UPDATE feilrap_skift SET israpportert=1, rapportid=$saferapportid WHERE skiftid IN ($skiftidlist);";
		$msdb->exec($sql);
		
		// Skittent hack, disse må defineres to plasser atm :\	
		 $validinput['tpldata']['fulltnavn'] = $INFO['userinfo']['name'];
		 $validinput['tpldata']['datotid'] = date('dmy \&\n\d\a\s\h\; H:i');
			
				
		foreach ($validinput as $inputtype => $arValues)  {
			$safeinputtype = $msdb->quote($inputtype);
			foreach ($arValues as $inputnavn => $inputvalue) {
				$safeinputnavn = $msdb->quote($inputnavn);
				$safeinputvalue = $msdb->quote($inputvalue);
				$sql = "INSERT INTO feilrap_rapportdata (rapportid, dataname, datavalue, datatype) VALUES ($saferapportid, $safeinputnavn, $safeinputvalue, $safeinputtype);";
				$msdb->exec($sql);
			}
		}
		
		$this->_isSaved = true;
		
	}
	
	
	private function _genRapport($validinput = array(), $invalidinput = array(), $savedrapport = false) {
			global $INFO;
			$totaltellere = array();
			$brukertellere = array();
			
			$tellercol = new TellerCollection();
			$notatcol = new NotatCollection();
			
			$tplErstatter = new Erstatter();
			
			if (!($this->skift->length() > 0)) throw new Exception('Ingen skift lastet i rapportobjekt');
			
			$skiftcol = $this->skift;
			
			// for hvert skift som er gitt funksjonen
			foreach ($skiftcol as $objSkift) {
				
				$hiddenSkiftider .= '<input type="hidden" name="selskift[]" value="' . $objSkift->getId() . "\" />\n";
			
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
							// legger denne til i rapportens teller-collection, med navnet på teller (eks. "TLSPT") som key.
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
			
			
			// Definer [[data:datumnavn]] som skal replaces her.
			if ($savedrapport) {
				$tpldata = $validinput['tpldata'];
			} else {
				$tpldata['fulltnavn'] = $INFO['userinfo']['name'];
				$tpldata['datotid'] = date('dmy \&\n\d\a\s\h\; H:i');
			}
			
			if (!$notatoutput) $notatoutput = 'Ingen notater.';
			if (!$notatsaveoutput) {
				$notatsaveoutput = 'Ingen notater.';
			} else {
				$notatsaveoutput = '<ul class="msul">' . $notatsaveoutput . '</ul>';
			}
			if (!$uloggetoutput) $uloggetoutput = 'Ingen uloggede samtaler.';
			
	
			
			// Erstatter [[notater:annet]] med notat-output. 
			$funcErstattNotat = function ($matches) use (&$notatoutput, &$notatsaveoutput, $savedrapport) {
				if ($savedrapport) {
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
			$funcErstattInputBool = function ($matches) use (&$validinput, &$invalidinput, $savedrapport, $validationerrors) {
				$varname = $matches[1];
				if ($savedrapport) {
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
				
				if ($output == '') $output = '<span title="Verdien av ' . $varname . ' er ikke lagret i denne rapporten.">N/A</span>';
				
				return $output;
			};
			$tplErstatter->addErstattning('/\[\[inputbool:([A-Za-z]+)\]\]/u', $funcErstattInputBool);
			
			// Erstatter [[inputtekst:varname]] med tekst-input felt
			$funcErstattInputTekst = function ($matches) use (&$validinput, &$invalidinput, $savedrapport, $validationerrors) {
				$varname = $matches[1];
				if ($savedrapport) {
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
				
				if ($output == '') $output = '<span title="Verdien av ' . $varname . ' er ikke lagret i denne rapporten.">N/A</span>';
				
				return $output;
			};
			$tplErstatter->addErstattning('/\[\[inputtekst:([A-Za-z]+)\]\]/u', $funcErstattInputTekst);
			
			// Erstatter [[inputlitetall:varname]] med 3-siffret tekst-input
			$funcErstattInputLiteTall = function ($matches) use (&$validinput, &$invalidinput, $savedrapport, $validationerrors) {
				$varname = $matches[1];
				if ($savedrapport) {
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
				
				if ($output == '') $output = '<span title="Verdien av ' . $varname . ' er ikke lagret i denne rapporten.">N/A</span>';
				
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
		
			
			$tmpOutput = RapportTemplateFactory::getTemplateOutput($tplErstatter, $this->_rapportTemplateId);
			
			if (!$savedrapport) $output .= $hiddenSkiftider;
			
			$output .= $tmpOutput;			
			
			return $output;
	}
	
	public function genMailForm() {
		global $msdb;
		
		if (!$this->_isSaved) return false;
		
		$sql = "SELECT wikifullname, wikiepost FROM internusers WHERE INSTR(`wikigroups`, 'feilm') OR INSTR(`wikigroups`, 'teaml');";
		$data = $msdb->assoc($sql);
		
		$arEpost = array();
		
		if(is_array($data) && sizeof($data)) {
			foreach($data as $datum) {
				$selectoptions .= '<option value="' . $datum['wikiepost'] . '">' . $datum['wikifullname'] . '</option>' . "\n";
			}
		}
		
		
		$output .= '<span class="subactheader">Send rapport:</span><br /><br />';
		$output .= 'Hold inne CTRL-knappen for å velge flere personer.<br />';
		$output .= '<form name="mailrapport" action="' . MS_FMR_LINK . '" method="POST">' . "\n";
		$output .= '<input type="hidden" name="act" value="mailrapport" />' . "\n";
		$output .= '<input type="hidden" name="rapportid" value="' . $this->_id . '" />' . "\n";
		$output .= '<select multiple name="mailmottakere[]" />' . "\n";
		$output .= $selectoptions;
		$output .= '</select><br /><br />' . "\n";
		$output .= '<input type="submit" class="msbutton" name="sendmail" value="Send rapport per e-post" />' . "\n";
		$output .= '</form>' . "\n\n";
		
		return $output;
		
	}
	
	public function estimateSkiftType() {
		$rapporttime = date('G', $this->_rapportCreatedTime); // 0-23

		if ($rapporttime >= 6 && $rapporttime < 10) {
			$skifttype = 'N';
		} elseif ($rapporttime >= 14 && $rapporttime < 18) {
			$skifttype = 'D';
		} elseif ($rapporttime >= 22) {
			$skifttype = 'E';
		} else {
			$skifttype = 'U';
		}
		
		return $skifttype;	
	}
}
