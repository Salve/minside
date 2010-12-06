<?php
if(!defined('MS_INC')) die();
define('MS_FMR_LINK', MS_LINK . "&amp;page=feilmrapport");
require_once('class.feilmrapport.skift.php');
require_once('class.feilmrapport.teller.php');
require_once('class.feilmrapport.notat.php');
require_once('class.feilmrapport.rapport.php');
require_once('class.feilmrapport.skiftfactory.php');
require_once('class.feilmrapport.rappvalidator.php');
require_once('class.feilmrapport.rapporttemplate.php');
require_once('class.feilmrapport.rapporttemplatefactory.php');
require_once('class.feilmrapport.rapporttemplatecollection.php');
require_once('class.feilmrapport.tellercollection.php');
require_once('class.feilmrapport.notatcollection.php');
require_once('class.feilmrapport.rapportcollection.php');
require_once('class.feilmrapport.skiftcollection.php');

class msmodul_feilmrapport implements msmodul{

	private $_msmodulact;
	private $_msmodulvars;
	private $_frapout;
	private $_userId;
	private $_accessLvl;
	private $_currentSkiftId;
	private static $monthnames = array(
			1 => 'januar',
			2 => 'februar',
			3 => 'mars',
			4 => 'april',
			5 => 'mai',
			6 => 'juni',
			7 => 'juli',
			8 => 'august',
			9 => 'september',
			10 => 'oktober',
			11 => 'november',
			12 => 'desember',
	);
	
	public function __construct($UserID, $accesslvl) {
		$this->_userId = $UserID;
		$this->_accessLvl = $accesslvl;
	}
	
	public function getMsmodulact(){
		return $this->_msmodulact;
	}
	
	public function gen_msmodul($act, $vars){
		$this->_msmodulact = $act;
		$this->_msmodulvars = $vars;
		
		if (!$this->_accessLvl > MSAUTH_NONE) return '';
		
		//$this->_frapout .= 'Output fra feilmrapport: act er: '. $this->_msmodulact . ', userid er: ' . $this->_userId . '<br />';
		
		switch($this->_msmodulact) {
			case "stengskift":
				if (isset($_REQUEST[skiftid])) $this->_closeSkift($_REQUEST[skiftid]);
			case "genrapportsel":
				if ($this->_accessLvl >= MSAUTH_2) $this->_frapout .= $this->_genRapportSelectSkift();
				break;
			case "gensaverapport":
			case "genrapportmod":
				if ($this->_accessLvl >= MSAUTH_2) $this->_frapout .= $this->_genModRapport();
				break;
			case "modtpllive":
				if ($this->_accessLvl >= MSAUTH_5) {
					$this->_modTemplateLive();
					$this->_frapout .= $this->_genModRapportTemplates();
				}
				break;
			case "sletttpl":
				if ($this->_accessLvl >= MSAUTH_5) {
					$this->_slettTemplate();
					$this->_frapout .= $this->_genModRapportTemplates();
				}
				break;
			case "showtplmarkup":
				if ($this->_accessLvl >= MSAUTH_5) $this->_frapout .= $this->_genModTemplate('markup');
				break;
			case "showtplpreview":
				if ($this->_accessLvl >= MSAUTH_5) $this->_frapout .= $this->_genModTemplate('preview');
				break;
			case "genmodtpl":
				if ($this->_accessLvl >= MSAUTH_5) $this->_frapout .= $this->_genModTemplate('edit');
				break;
			case "nyraptpl":
				if ($this->_accessLvl >= MSAUTH_5) {
					$this->_nyRapportTemplate();
					$this->_frapout .= $this->_genModRapportTemplates();
				}
				break;
			case "genmodraptpl":
				if ($this->_accessLvl >= MSAUTH_5) $this->_frapout .= $this->_genModRapportTemplates();
				break;
			case "modraptpl":
				if ($this->_accessLvl >= MSAUTH_5) {
					$this->_saveRapportTemplate();
					$this->_frapout .= $this->_genModRapportTemplates();
				}
				break;
			case "nyteller":
				if ($this->_accessLvl >= MSAUTH_5) {
					$this->_nyTeller();
					$this->_frapout .= $this->_genTellerAdm();
				}
				break;
			case "flipteller":
				if ($this->_accessLvl >= MSAUTH_5) {
					$this->_flipTeller();
					$this->_frapout .= $this->_genTellerAdm();
				}
				break;
			case "telleradm":
				if ($this->_accessLvl >= MSAUTH_5) $this->_frapout .= $this->_genTellerAdm();
				break;
			case "rapportarkiv":
				if ($this->_accessLvl >= MSAUTH_2) $this->_frapout .= $this->_genRapportArkiv(false);
				break;
            case "rapportarkivforside":
				if ($this->_accessLvl >= MSAUTH_2) $this->_frapout .= $this->_genRapportArkiv(true);
				break;
			case "visrapport":
				if ($this->_accessLvl >= MSAUTH_2) $this->_frapout .= $this->_genRapport();
				break;
			case "mailrapport":
				if ($this->_accessLvl >= MSAUTH_2) $this->_sendRapportMail();
				$this->_frapout .= $this->genSkift();
				break;
			case "stengegetskift":
				$this->_closeCurrSkift();
				$this->_frapout .= $this->genSkift();
				break;
			case "nyttskift":
				$this->_createNyttSkift();
				$this->_frapout .= $this->genSkift();
				break;
			case "savenotat":
				$this->_lagreNotat();
				$this->_frapout .= $this->genSkift();
				break;
			case "delnotat":
				$this->_slettNotat();
				$this->_frapout .= $this->genSkift();
				break;
            case "undoakt":
                $this->_undoAkt();
                $this->_frapout .= $this->genSkift();
                break;
			case "modtellerorderned":
				if ($this->_accessLvl >= MSAUTH_5) {
					$this->_modTellerOrder('ned');
					$this->_frapout .= $this->_genTellerAdm();
				}
				break;
			case "modtellerorderopp":
				if ($this->_accessLvl >= MSAUTH_5) {
					$this->_modTellerOrder('opp');
					$this->_frapout .= $this->_genTellerAdm();
				}
				break;
			case "mod_teller":
				$this->_changeTeller();
			case "show":
			default:
				$this->_frapout .= $this->genSkift();
		}
        
        $output = $this->_frapout;
        $this->_frapout = '';
		return $output;
	
	}
	
	private function genNotat($objNotat, $edit = false) {
		if ($edit) {
			$output .= '<p>';
			$output .= '<form action="' . MS_FMR_LINK . '" method="POST">';
			$output .= '<input type="hidden" name="act" value="savenotat" />';
			if ($objNotat instanceof Notat) $output .= '<input type="hidden" name="notatid" value="' .  $objNotat->getId() . '" />';
			$output .= '<textarea id="notattekst" class="msedit" style="left:0px;" name="notattekst" rows="3" cols="40">';
			if ($objNotat instanceof Notat) $output .= $objNotat->getNotatTekst();
			$output .= '</textarea>';
			$output .= '<input type="submit" name="lagre" value="Lagre" class="msbutton">';
			$output .= '<input type="submit" name="lagre" value="Avbryt" class="msbutton">';
			$output .= '</form>';
			$output .= '</p>';
		} else {
			if ($objNotat instanceof Notat) {
				$stredit = ' <a href="' . MS_FMR_LINK . '&amp;act=modnotat&amp;notatid=' . $objNotat->getId() . '"><img src="' . MS_IMG_PATH . 'pencil.png"></a>';
				$strslett = ' <a href="' . MS_FMR_LINK . '&amp;act=delnotat&amp;notatid=' . $objNotat->getId() . '"><img src="' . MS_IMG_PATH . 'trash.png"></a>';
			}
			$output .= '<li>' . $objNotat . $stredit . $strslett . '</li>';
		}		
		
		return $output;
	}
	
	private function _genRapportArkiv($forside = false) {
				
		if ( ($this->_accessLvl < MSAUTH_3) || ( !isset($_REQUEST['arkivmnd']) ) ) {
		
			$rapportcol = SkiftFactory::getNyligeRapporter(); // Returnerer en RapportCollection
			
			
			$output .= '<span id="rapportsistedogn">Rapporter opprettet det siste døgnet:</span>' . "\n";
			
			$output .= $this->_genRapportListe($rapportcol);
		
		} else {
		
			$inputstr = $_REQUEST['arkivmnd'];
			list($inputyear, $inputmonth) = explode('-', $inputstr);
			
			$inputyear = (int) $inputyear;
			$inputmonth = (int) $inputmonth;
			
			if (!($inputyear > 1970 && $inputyear < 2030)) {
				die('Ugyldig år gitt til rapportarkiv');
			}
			
			if (!($inputmonth >= 1 && $inputmonth <= 12)) {
				die('Ugyldig måned gitt til rapportarkiv');
			}
			
			$rapportcol = SkiftFactory::getRapporterByMonth($inputmonth, $inputyear);
			$output .= '<span id="rapportarkivheader">Rapporter fra ' . self::$monthnames["$inputmonth"] . ' ' . $inputyear . ':</span>' . "\n";		
			
			$output .= $this->_genRapportListe($rapportcol, true);
			
		}
		
		
		
		if ($this->_accessLvl >= MSAUTH_3) $output .= $this->_genRapportArkivMenu();
		
		if(!$forside) {
            $pre = '<h1>Rapportarkiv</h1><div class="rapportarkiv level2">';
            $post = '</div>';
        } else {
            $pre = '<div class="rapportarkiv level1">';
            $post = '</div>';
        }
        
		return $pre . $output . $post;
	
	}
	
	private function _genRapportListe(RapportCollection $rapcol, $sortuke = false) {
		
		$output = "\n\n" . '<div class="rapparkivliste">';
		
		$currUkeNummer = 100;
		$currDagNummer = 100;
		$ukecounter = 0;
		$dagcounter = 0;
		$rapportcounter = 0;
		$firstuke = true;
		$firstdag = true;
		
		$ukedager = array('Søndag', 'Mandag', 'Tirsdag', 'Onsdag', 'Torsdag', 'Fredag', 'Lørdag');
		
		foreach ($rapcol as $objRapport) {
			$createtime = strtotime($objRapport->getRapportCreatedTime());
			$dagnummer = date('w', $createtime); // 0-6
			$skiftkort = $objRapport->estimateSkiftType();
			switch ($skiftkort) {
				case "D":
					$skiftlang = 'dskift';
					break;
				case "E":
					$skiftlang = 'eskift';
					break;
				case "N":
					$skiftlang = 'nskift';
					break;
				case "U":
					$skiftlang = 'ukjentskift';
					break;			
			}
			
			$ukedag = $ukedager[$dagnummer]; // Mandag, Tirsdag osv.
			
			if ($sortuke) {
				$ukenummer = date('W', $createtime);
				if ($ukenummer != $currUkeNummer) {
					$currUkeNummer = $ukenummer;
					$firstdag = true; // nullstill denne for hver nye uke
					$ukecounter++;
					$dagcounter = 0;
					
					$ukecontainerclass = ($ukecounter & 1) ? 'ukeone' : 'uketwo';
					
					
					if (!$firstuke) {					
						// Avslutt siste dag i forrige ukecontainer.
						$output .= '</div>'; // dagcontent
						$output .= '<div class="msclearer"></div>'; // dagcontainer_clear
						$output .= '</div>'; // dagcontainer
						
						// Avslutt forrige ukecontainer-div, med mindre dette er første uke som vises.
						$output .= '</div>'; // ukecontent
						$output .= '<div class="msclearer"></div>'; // ukecontainer_clear
						$output .= '</div>'; // ukecontainer
					} else {
						$firstuke = false;
					}
					
					$output .= "\n\n";
					$output .= '<div class="ukecontainer ' . $ukecontainerclass . '">' . "\n";
					$output .= '<div class="ukeheader">' . "\n";
					$output .= '<span class="ukenavn">';
					$output .= 'Uke <br />' . $ukenummer;
					$output .= '</span>' . "\n";
					$output .= '</div>' . "\n"; // ukeheader
					$output .= '<div class="ukecontent">' . "\n";
					
				}
				
				if (($currDagNummer != $dagnummer) || $firstdag === true) { // Første ukedag som skal skrives i denne uken kan være samme
					if ($firstdag === true) {								// som siste ukedag i forrige uke. Må derfor sjekke firstday.
						$firstdag = false;
					} else {
						$output .= '</div>'; // dagcontent
						$output .= '<div class="msclearer"></div>'; // dagcontainer_clear
						$output .= '</div>'; // dagcontainer
					}
					$rapportcounter = 0;
					$dagcounter++;
					$dagcontainerclass = ($dagcounter & 1) ? 'dagone' : 'dagtwo';
					$currDagNummer = $dagnummer;
					
					$output .= '<div class="dagcontainer ' . $dagcontainerclass . '">' . "\n";
					$output .= '<div class="dagheader">' . "\n";
					$output .= '<span class="dagnavn">' . $ukedag . '</span><br />';
					$output .= '<span class="dagdato">' . date('(j.n.)', $createtime) . '</span>';
					$output .= '</div>'; // dagheader
					$output .= '<div class="dagcontent">' . "\n\n";
				
				}
				
				$rapportcounter++;
				$rapportspanclass = ($rapportcounter & 1) ? 'rapone' : 'raptwo';
				
				$output .= '<span class="rapportnavn ' . $skiftlang . ' ' . $rapportspanclass . '"><a href="' . MS_FMR_LINK . '&amp;act=visrapport&amp;rapportid=' . $objRapport->getId() . '">';
				$output .= date('H:i', $createtime) . ' &mdash; ' . $objRapport->getRapportOwnerName();
				$output .= '</a></span><br />' . "\n";
				
			} else { 
				
				$rapportcounter++;
				$rapportspanclass = ($rapportcounter & 1) ? 'rapone' : 'raptwo';
	
				$output .= '<span style="font-size:1em;" class="rapportnavn ' . $skiftlang . ' ' . $rapportspanclass . '">';
				$output .= '<a href="' . MS_FMR_LINK . '&amp;act=visrapport&amp;rapportid=' . $objRapport->getId() . '">';
				$output .= $ukedag . date(' (j.n.) \k\l. H:i', $createtime) . ' &mdash; ' . $objRapport->getRapportOwnerName();
				$output .= '</a>';
				$output .= '</span><br />';

			}
		
		}
		
		if ($sortuke) {
			$output .= '</div><div class="msclearer"></div></div></div><div class="msclearer"></div></div></div>'; // dagcontent dagcontainer ukecontent ukecontainer_clear ukecontainer rapparkivliste
		} else {
			$output .= '<br /></div> <!-- rapparkivliste -->' . "\n"; // rapparkivliste
		}
		
		return $output;
	
	}
	
	private function _genRapportArkivMenu() {
		global $msdb;
	
		$sql = "SELECT YEAR(createtime) AS 'YEAR', GROUP_CONCAT(DISTINCT MONTH(createtime)) AS 'MONTHS' FROM feilrap_rapport GROUP BY `YEAR`;";
		$data = $msdb->assoc($sql);
		
		$output = '<span id="rapportarkivmenyheader">Rapportarkiv:</span>' . "\n";
        $output .= '<div class="rapportarkivmeny">';
        
		if(is_array($data) && sizeof($data)) {
			foreach ($data as $datum) {
			
				$arMonths = explode(',', $datum['MONTHS']);
				sort($arMonths);
                
				$year = $datum['YEAR'];
				$monthlist = '';
				foreach ($arMonths as $month) {
					$monthlist .= '<a href="' . MS_FMR_LINK . '&amp;act=rapportarkiv&amp;arkivmnd=' . $year . '-' . $month . '">' . substr(self::$monthnames["$month"], 0, 3) . '</a> ' . "\n";
				}
			
				$output .= '<span class="yearlist">' . "\n";
				$output .= '<span class="yearname">' . "\n";
				$output .= "$year: ";
				$output .= '</span>' . "\n"; // yearname
				$output .= $monthlist;
				$output .= '</span><br />' . "\n"; // yearlist
			}
		} else {
			$output .= '<p>Ingen rapporter funnet.</p>';
		}
	
		$output .= '</div>';
		return $output;
	
	
	}
	
	private function _genRapport() {
		if (isset($_REQUEST['rapportid'])) {
			$rapportid = $_REQUEST['rapportid'];
			
			if ($this->_accessLvl <= MSAUTH_2) { // Bruker kan kun vise nylige rapporter
				$rapcol = SkiftFactory::getNyligeRapporter(); // RapportCollection med alle rapporter bruker har tilgang til å vise
				if (!$rapcol->exists($rapportid)) {
					msg('Du har ikke tilgang til å vise denne rapporten.', -1);
					return false;
				}
			}
		
			try{
				$objRapport = SkiftFactory::getRapport($rapportid);
			}
			catch (Exception $e) {
				msg('Klarte ikke å hente rapport: ' . $e->getMessage(), -1);
				return;
			}
			
			try {
				$output = $objRapport->genRapport();
			}
			catch (Exception $e) {
				msg('Klarte ikke å vise rapport: ' . $e->getMessage(), -1);
				return;
			}
			
			$output .= $objRapport->genMailForm();
			
			return $output;
			
		} else {
			msg('Rapport id ikke definert.', -1);
			return;
		}
	}
	
	private function _genModRapport(){
			
			if (RapportTemplateFactory::getCurrentTplId() === false) {
				msg('Finner ikke noe aktivt rapport-template. Dette må opprettes for å kunne generere rapport.', -1);
				return false;
			}
	
	
			$skiftcol = new SkiftCollection();
			
			$validationerrors = 0;
			
			$noskift = '
						<div class="mswarningbar">Ingen skift valgt</div>
						<form name="tilbake" action="' . MS_FMR_LINK . '" method="POST">
							<input type="hidden" name="act" value="genrapportsel" />
							<input type="submit" class="msbutton" name="subvelgskift" value="Tilbake" />
						</form>';
			
			
			if (is_array($_POST['selskift'])) {
				foreach ($_POST['selskift'] as $skiftid) {
					if (trim($skiftid, '0123456789') != '') die('Ugyldig skiftid oppdaget!');
					
					try {
						$objSkift = SkiftFactory::getSkift($skiftid);
					}
					catch (Exception $e) {
						msg($e->getMessage,-1);
						return false;
					}				
					
					if ($objSkift instanceof Skift) {
						if ($objSkift->isClosed() && !$objSkift->isRapportert()) {
							$skiftcol->addItem($objSkift, $objSkift->getId());
						}
					}		
				}
				if ($skiftcol->length() == 0) { // selskift inneholder noe, men ingen valid skift				
				
					return $noskift; 
				}
			} else {
				return $noskift;
			}
			
			/*
			 *	Input validation
			 * 
			 * All data som gis til objRapport skal være validert
			 */
			// Validerer kun dersom bruker har submittet form
			if ($_REQUEST['genrap'] == 'Generer rapport') {
			
				$submitsave = true; // bruker har forsøkt å lagre rapport
				
				$validinput = array();
				$invalidinput = array();
				$validationerrors = $this->_validateRapportInput($validinput, $invalidinput, $skiftcol); // parameters by ref
				
				
				if ($validationerrors === 0) {
					$validsave = true;
				}
							
			}
			// Slutt validation
			
			$objRapport = new Rapport($this->_userId);
			$objRapport->setSkiftCol($skiftcol);
			
			if ($validsave) { // Inndata er ok, rapport skal lagres
			
				try {
					$objRapport->lagreRapport($validinput);
				}
				catch (Exception $e) {
					die('Input ok, men klarte ikke å lagre rapport: ' . $e->getMessage());
				}
				
				$rappoutput = $objRapport->genRapport();
				$rappoutput .= $objRapport->genMailForm();
			
			} elseif ($submitsave) {
				$rappoutput = '<div class="mswarningbar" id="rapporthaserrors"><strong>Rapporten kunne ikke genereres!</strong><br /><br />Ett eller flere av inputfeltene inneholder ugyldig data, eller er tomme.</div><br />' . "\n";
				try {
					$rappoutput .= $objRapport->genRapportTemplateErrors($validinput, $invalidinput);
				}
				catch (Exception $e) {
					die('Klarte ikke å vise rapport-template: ' . $e->getMessage());
				}
				
			} else {
				try {
					$rappoutput = $objRapport->genRapportTemplate();
				}
				catch (Exception $e) {
					die('Klarte ikke å vise rapport-template: ' . $e->getMessage());
				}			
			}
			
			$output .= '<form name="lagrerapport" action="' . MS_FMR_LINK . '" method="POST">' . "\n";
			$output .= '<input type="hidden" name="act" value="gensaverapport" />' . "\n";
			$output .= $rappoutput;
			$output .= '<br /><br />' . "\n";
			if (!$validsave) {
				$output .= '<input type="submit" class="msbutton" name="genrap" value="Generer rapport" />' . "\n";
				$output .= '<input type="submit" class="msbutton" name="genrap" value="Nullstill" />' . "\n";
			}
			$output .= '</form>' . "\n";

			$output .= '<form name="tilbake" action="' . MS_FMR_LINK . '" method="POST">' . "\n";
			if ($validsave) {
				$output .= '<input type="hidden" name="act" value="show" />' . "\n";
			} else {
				$output .= '<input type="hidden" name="act" value="genrapportsel" />' . "\n";
			}
			$output .= '<input type="submit" class="msbutton" name="genrap" value="Tilbake" />' . "\n";
			$output .= '</form>' . "\n";			
			
			return $output;
			
	}
	
	private function _sendRapportMail() {
		global $INFO;
		
		if (isset($_REQUEST['rapportid'])) {
			$rapportid = $_REQUEST['rapportid'];
		} else {
			die('Rapportid ikke angitt');
		}
		
		if (is_array($_REQUEST['mailmottakere']) && sizeof($_REQUEST['mailmottakere'])) {
			$mailmottakere = $_REQUEST['mailmottakere'];
		} else {
			msg('Kan ikke sende mail: Ingen mottakere valgt.', -1);
			return false;
		}
		
		if ($this->_accessLvl <= MSAUTH_2) { // Bruker kan kun vise nylige rapporter
			$rapcol = SkiftFactory::getNyligeRapporter(); // RapportCollection med alle rapporter bruker har tilgang til å vise
			if (!$rapcol->exists($rapportid)) {
				msg('Du har ikke tilgang til å sende denne rapporten.', -1);
				return false;
			} else {
				$objRapport = $rapcol->getItem($rapportid);
			}
		} else { // Bruker har adgang til alle rapporter		
			try{
				$objRapport = SkiftFactory::getRapport($rapportid);
			}
			catch (Exception $e) {
				msg('Klarte ikke å sende rapport: ' . $e->getMessage(), -1);
				return;
			}
		}
			
		try {
			$rapporthtml = $objRapport->genRapport();
		}
		catch (Exception $e) {
			msg('Klarte ikke å sende rapport: ' . $e->getMessage(), -1);
			return;
		}

		$skiftnavn = $objRapport->estimateSkiftType();
		$rapportid = $objRapport->getId();
		$userid = $this->_userId;
												
		$subject = 'Rapport feilmeldingstjenesten ' . date('d.m.Y ') . $skiftnavn;
		$headers = 'From: ' . $INFO['userinfo']['mail'] . "\r\n" .
			'Reply-To: ' . $INFO['userinfo']['mail'] . "\r\n" .
			'X-Mailer: PHP/LyseWiki/MinSide/FeilMRapport' . "\r\n" .
			'X-MinSide-RapportID: ' . $rapportid . "\r\n" .
			'X-MinSide-SenderID: ' . $userid . "\r\n" .
			'MIME-Version: 1.0' . "\r\n" .
			'Content-type: text/html; charset="utf-8"' . "\r\n";
		$mailto = implode(', ', $mailmottakere);
		$mailcounter = count($mailmottakere);
		
		$resultat = mail($mailto, $subject, $rapporthtml, $headers);
		if ($resultat) {
			msg('Rapport sendt til ' . $mailcounter . ' mottakere.', 1);
			return true;
		} else {
			msg('Mail-utsendelse feilet!', -1);
			return false;
		}
		
	}
	
	private function _validateRapportInput(&$validinput, &$invalidinput, &$skiftcol) {
	
		$validationerrors = 0;
	
		if (is_array($_POST['rappinn']['bool'])) {
			foreach ($_POST['rappinn']['bool'] as $varname => $inputitem) {
				$valOutput = ''; // Sendes by ref til validator funksjon, inneholder output, som kan være endret fra $inputitem selv om validation er ok. (f.eks. stripping av spaces osv)
				$valError = '';  // Sendes by ref til validator funksjon, inneholder error message for gitt varname dersom validation failer.
				$valResult = RappValidator::ValBool($inputitem, $valOutput, $valError);
				
				if ($valResult === true) {
					$validinput['bool'][$varname] = $valOutput;
				} else {
					$validationerrors++;
					$invalidinput['bool'][$varname] = $valError;
				}
			}
		} 
		
		if (is_array($_POST['rappinn']['litetall'])) {
			foreach ($_POST['rappinn']['litetall'] as $varname => $inputitem) {
				$valOutput = '';
				$valError = '';
				$valResult = RappValidator::ValLiteTall($inputitem, $valOutput, $valError);
				
				if ($valResult === true) {
					$validinput['litetall'][$varname] = $valOutput;
				} else {
					$validationerrors++;
					$invalidinput['litetall'][$varname] = $valError;
				}
			}
		}
		
		if (is_array($_POST['rappinn']['desimaltall'])) {
			foreach ($_POST['rappinn']['desimaltall'] as $varname => $inputitem) {
				$valOutput = '';
				$valError = '';
				$valResult = RappValidator::ValDesimalTall($inputitem, $valOutput, $valError);
				
				if ($valResult === true) {
					$validinput['desimaltall'][$varname] = $valOutput;
				} else {
					$validationerrors++;
					$invalidinput['desimaltall'][$varname] = $valError;
				}
			}
		}
		
		if (is_array($_POST['rappinn']['tekst'])) {
			foreach ($_POST['rappinn']['tekst'] as $varname => $inputitem) {
				$valOutput = '';
				$valError = '';
				$valResult = RappValidator::ValTekst($inputitem, $valOutput, $valError);
				
				if ($valResult === true) {
					$validinput['tekst'][$varname] = $valOutput;
				} else {
					$validationerrors++;
					$invalidinput['tekst'][$varname] = $valError;
				}
			}
		}
		
		
		
		if (is_array($_POST['rappinn']['selnotat'])) {
			foreach ($_POST['rappinn']['selnotat'] as $notatid) {
				$objNotat = SkiftFactory::getNotat($notatid);
				if ($skiftcol->exists($objNotat->getSkiftId())) { // Sjekker om skiftiden til notatet er en av de som er valgt for denne rapporten
					$validinput['selnotat'][] = $notatid;
				} else {
					die('Forsøk på å inkludere notat som ikke hører til rapporten');
				}
			}
		}
	
		return $validationerrors;
	
	}
	
	private function _genRapportSelectSkift(){
	
		$skiftcol = SkiftFactory::getMuligeSkiftForRapport();
	
		$output .= '
            <h1>Lag rapport</h1><div class="level2">
			<div style="margin-left:0px; width:600px;">
			<fieldset style="width: 600px;text-align:left;">
				<legend>
					Velg skift som skal inkluderes i rapport
				</legend>
				<form name="velgskift" action="' . MS_FMR_LINK . '" method="POST">
					<input type="hidden" name="act" value="genrapportmod" />';
		
		foreach ($skiftcol as $objSkift) {
		
			try {
				$starttid = new DateTime($objSkift->getSkiftCreatedTime());
			} catch (Exception $e) {
				msg($e->getMessage());
			}
			
			try {
				$slutttid = new DateTime($objSkift->getSkiftClosedTime());
			} catch (Exception $e) {
				msg($e->getMessage());
			}
            
            $starttime = strtotime($objSkift->getSkiftCreatedTime());
            $hourdiff = floor((time() - $starttime) / 60 / 60);
            
            if($hourdiff > 14) { 
                $ageclass = ' oldskift';
                $agewarning = ' (Obs! Gammelt skift!)';
            } elseif($hourdiff < 2) { 
                $ageclass = ' newskift'; 
                $agewarning = ' (Obs! Nytt skift!)';
            } else { 
                $ageclass = '' ;
                $agewarning = '';
            }
            
			if ($objSkift->isClosed()) {			
				
				$output .= '<input type="checkbox" name="selskift[]" value="' . $objSkift->getId() . '" />';
                $output .= '&nbsp;' . '<span class="skift'. $ageclass .'">' . strtoupper($objSkift->getSkiftOwnerName()) . ' &mdash; ' . $this->LesbarTid($starttid) . ' &ndash; ' . $this->LesbarTid($slutttid) . $agewarning . "<br />\n";
			} else {
				$output .= '<input type="checkbox" name="selskift[]" value="' . $objSkift->getId() . '" disabled />';
                $output .= '&nbsp;' . '<span class="skift'. $ageclass .'">' . strtoupper($objSkift->getSkiftOwnerName()) . ' &mdash; ' . $this->LesbarTid($starttid) . ' &ndash; Ikke avsluttet! '; 
				$output .= '(<a href="' . MS_FMR_LINK . '&amp;act=stengskift&amp;skiftid=' . $objSkift->getId() . '">avslutt skift</a>)' . $agewarning . "<br />\n";
			}
            $output .= '</span>';
		}			
					
		$output .=	'
					<input type="submit" name="subvelgskift" class="msbutton" value="Gå videre">
				</form>
				<form name="tilbake" action="' . MS_FMR_LINK . '" method="POST">
					<input type="hidden" name="act" value="show" />
					<input type="submit" class="msbutton" name="subvelgskift" value="Tilbake" />
				</form>
			</fieldset>
			</div></div>';
		
		
		
		return $output;
	}
	
	public static function LesbarTid(DateTime $inntid) {
		$dagensdato = date('Y-m-d');
		$inndato = $inntid->format('Y-m-d');
		
		$ukedager = array('søn.', 'man.', 'tirs.', 'ons.', 'tors.', 'fre.', 'lør.', );
		$uttid = $inntid->format('H:i');
		
		if ($dagensdato != $inndato) {
			$dag = $ukedager[$inntid->format('w')];
			$uttid = $dag . ' ' . $uttid;
		}
		
		return $uttid;
		
	}
	
	public function genSkift(){

		$skiftID = $this->getCurrentSkiftId();
		if ($skiftID === false) return $this->_genNoCurrSkift();
		
		$skiftout .= '<div class="skift_full">';
		
		try{
			$objSkift = SkiftFactory::getSkift($skiftID);
		} catch(Exception $e) {
			die($e->getMessage());
		}

		$skiftcreate = strtotime($objSkift->getSkiftCreatedTime());
		$skiftage = time() - $skiftcreate;
		$skifthours = date('G', $skiftage);
		if ( $skifthours >= 9 ) {
			// Mer enn 9 timer siden skift ble opprettet
			$skiftout .= '<div class="mswarningbar" id="warnoldskift">';
			$skiftout .= 'Skiftet ditt er mer enn ' . $skifthours . ' timer gammelt!<br />';
			$skiftout .= 'Skift lukkes automatisk 14 timer etter de er opprettet.<br />';
			$skiftout .= '</div>'; // warnoldskift
		}

		// Vis notater
		
		$skiftout .= '<div class="notater"><fieldset id="notatfield" class="msfieldset"><legend>Notater</legend>';
		

		if (($this->_msmodulact == 'modnotat') && isset($_REQUEST['notatid'])) {
			$objNotat = $objSkift->notater->getItem($_REQUEST['notatid']);
			
			$skiftout .= $this->genNotat($objNotat, true);
		} else {
			$skiftout .= '</ul>';
			$skiftout .= $this->genNotat(null, true);
		}
		$skiftout .= '</fieldset>' . "\n";
		$skiftout .= '<ul class="msul">' . "\n";
			foreach($objSkift->notater as $objNotat) {
			if ($objNotat->isActive()) $skiftout .= $this->genNotat($objNotat);
		}
		$skiftout .= '</ul></div>';
		
		// Vis tellere
		
		$colTellerNotNull = new TellerCollection();
		$colSecTeller = new TellerCollection();
		$colSecTellerNotNull = new TellerCollection();
		$colUlogget = new TellerCollection();
		$colUloggetNotNull = new TellerCollection();
		
		$skiftout .= '<div class="tellertable">';
		
		if ($objSkift->getNumActiveTellere() == 0) {
			// Ingen aktive tellere
			$skiftout .= '<div class="mswarningbar" id="warningentellere">';
			$skiftout .= 'Ingen aktive tellere!<br /><br />';
			$skiftout .= 'En person med rette adgangsnivå må opprette/aktivere tellere for at de skal vises her.';
			$skiftout .= '</div>'; // warningentellere
		} else {
			$skiftout .= '<fieldset id="tellerfieldset" class="msfieldset"><legend>Tellere</legend>';
			$skiftout .= '<table class="feilmtable"><th class="top">Beskrivelse</th><th class="top" colspan="2">Endre verdi</th>';	
		}

		
		foreach($objSkift->tellere as $objTeller) {
			if (!$objTeller->isActive()) continue;
			
			switch ($objTeller->getTellerType()) {
				case 'TELLER':
					if ($objTeller->getTellerVerdi() > 0) $colTellerNotNull->addItem(clone($objTeller));
									
					$skiftout .= '<tr>' . "\n";
					$skiftout .= '<form action="' . MS_FMR_LINK . '" method="POST">' . "\n";
					$skiftout .= '<td class="feilmtablecols"><div class="feilmtablecols">' . $objTeller->getTellerDesc() . '</div></td>' . "\n"; // Tellerbeskrivelse
					$skiftout .= '<td style="text-align:center;"><input type="text" autocomplete="off" maxlength="2" value="1" id="rapverdi" class="msedit" name="modtellerverdi" /></td>' . "\n"; // Tekstfelt med endringsverdi
					$skiftout .= '<input type="hidden" name="act" value="mod_teller" />' . "\n";
					$skiftout .= '<input type="hidden" name="tellerid" value="' . $objTeller->getId() . '" />' . "\n";
					$skiftout .= '<td><div class="inc_dec"><input type="submit" class="msbutton msbuttonincdec" name="inc_teller" value="+" /><input type="submit" class="msbutton msbuttonincdec" name="dec_teller" value="-" /></div></td>' . "\n";
					$skiftout .= '</form>' . "\n";
					$skiftout .= "</tr>\n\n";
					break;
				case 'ULOGGET':
					$colUlogget->addItem(clone($objTeller));
					if ($objTeller->getTellerVerdi() > 0)$colUloggetNotNull->addItem(clone($objTeller));
					break;
				case 'SECTELLER':
					$colSecTeller->addItem(clone($objTeller));
					if ($objTeller->getTellerVerdi() > 0) $colSecTellerNotNull->addItem(clone($objTeller));
					break;
			}
		}

		
		if ($colSecTeller->length() > 0){
			$skiftout .= '<tr>' . "\n";
			$skiftout .= '<form action="' . MS_FMR_LINK . '" method="POST">' . "\n";
			$skiftout .= '<input type="hidden" name="act" value="mod_teller" />' . "\n";
			$skiftout .= '<td><select name="tellerid" class="msedit tellerdropdown">' . "\n";
			$skiftout .= '<option value="NOSEL">Flere tellere: </option>' . "\n";
			foreach ($colSecTeller as $objTeller) {
				$skiftout .= '<option value="' . $objTeller->getId() . '">' . $objTeller->getTellerDesc() . '</option>' . "\n";
			}
			$skiftout .= '</select></td>' . "\n";
			$skiftout .= '<td style="text-align:center;"><input type="text" autocomplete="off" maxlength="2" value="1" id="rapverdi" class="msedit" name="modtellerverdi" /></td>' . "\n"; // Tekstfelt med endringsverdi
			$skiftout .= '<td><div class="inc_dec"><input type="submit" class="msbutton msbuttonincdec" name="inc_teller" value="+" /><input type="submit" class="msbutton msbuttonincdec" name="dec_teller" value="-" /></div></td>' . "\n";
			$skiftout .= '</form>' . "\n";
			$skiftout .= "</tr>\n\n";
		}		
		
		if ($colUlogget->length() > 0){
			$skiftout .= '<tr>' . "\n";
			$skiftout .= '<form action="' . MS_FMR_LINK . '" method="POST">' . "\n";
			$skiftout .= '<input type="hidden" name="act" value="mod_teller" />' . "\n";
			$skiftout .= '<td><select name="tellerid" class="msedit tellerdropdown">' . "\n";
			$skiftout .= '<option value="NOSEL">Uloggede samtaler: </option>' . "\n";
			foreach ($colUlogget as $objTeller) {
				$skiftout .= '<option value="' . $objTeller->getId() . '">' . $objTeller->getTellerDesc() . '</option>' . "\n";
			}
			$skiftout .= '</select></td>' . "\n";
			$skiftout .= '<td style="text-align:center;"><input type="text" autocomplete="off" maxlength="2" value="1" id="rapverdi" class="msedit" name="modtellerverdi" /></td>' . "\n"; // Tekstfelt med endringsverdi
			$skiftout .= '<td><div class="inc_dec"><input type="submit" class="msbutton msbuttonincdec" name="inc_teller" value="+" /><input type="submit" class="msbutton msbuttonincdec" name="dec_teller" value="-" /></div></td>' . "\n";
			$skiftout .= '</form>' . "\n";
			$skiftout .= "</tr>\n\n";
		}
		
		if ($objSkift->getNumActiveTellere() > 0) {
			$skiftout .= '</table>' . "\n";
        // Nylig aktivitet
            $arLastAkt = $objSkift->getLastAct(6);
                if (count($arLastAkt)) {
                    $skiftout .= '<div class="sisteendringer msclearer">';
                    $skiftout .= '<strong><a href="javascript:;"  onClick="undoAct(\'viewAct\')">Siste endringer</a></strong><br />';
                    $skiftout .= '<div style="display:none;" id="viewAct">';
                    foreach ($arLastAkt as $arAkt) {
                        $skiftout .= '<div class="tellerakt msclearer">';
                        $skiftout .= '<div class="tellerakttekst">';
                        $skiftout .= '<em>Kl. ' . date('H:i:s', strtotime($arAkt['tidspunkt'])) . ":</em>&nbsp;&nbsp;<strong>";
                        $skiftout .= (($arAkt['verdi'] < 0) ? $arAkt['verdi'] : '+' . $arAkt['verdi']) . "</strong><br />\n";
                        $skiftout .= str_replace(' ', '&nbsp;', $arAkt['teller']) . "\n";
                        $skiftout .= '</div>'; // tellerakttekst
                        $skiftout .= '<div class="telleraktbilde">';
                        $skiftout .= '<a href="' . MS_FMR_LINK . '&amp;act=undoakt&amp;aktid=' . $arAkt['id'] . '">' .
                    '<img style="float:right;margin-top:3px;margin-right:3px;" src="' . MS_IMG_PATH . 'trash.png"></a>';
                        $skiftout .= '</div>'; // telleraktbilde
                        $skiftout .= '</div>'; // tellerakt
                    }
                    $skiftout .= '</div>'; // sisteendringer
                    $skiftout .= '</div>';
                }
			$skiftout .= '</fieldset><br /><br />' . "\n";
		}
		
		$skiftout .= '<div class="antalltall">';
		
		if ($colTellerNotNull->length() > 0) {
			$skiftout .= '<p>' . "\n";
			$skiftout .= '<strong>Tellere:</strong><br />' . "\n";
			
			foreach($colTellerNotNull as $objTeller) {
				if ($objTeller->getTellerVerdi() > 0) $skiftout .= $objTeller . '<br />' . "\n";
			}
			$skiftout .= '</p>' . "\n";
		}
		
		if ($colSecTellerNotNull->length() > 0) {
			$skiftout .= '<p>' . "\n";
			$skiftout .= '<strong>Annet:</strong><br />' . "\n";
			
			foreach ($colSecTellerNotNull as $objTeller) {
				$skiftout .= $objTeller . '<br />' . "\n";
			}
			$skiftout .= '</p>' . "\n";
		}
		
		if ($colUloggetNotNull->length() > 0) {
			$skiftout .= '<p>' . "\n";
			$skiftout .= '<strong>Uloggede samtaler:</strong><br />' . "\n";
			
			foreach ($colUloggetNotNull as $objTeller) {
				$skiftout .= $objTeller . '<br />' . "\n";
			}
			$skiftout .= '</p>' . "\n";
		}
        
        $skiftout .= '</div>'; // antalltall
        
		$skiftout .= '</div>'; // tellertable

		// Close skift knapp
		$skiftout .= '<form method="post" action="' . MS_FMR_LINK . '">' . "\n";
		$skiftout .= '<input type="hidden" name="act" value="stengegetskift" />' . "\n";
		$skiftout .= '<input type="submit" class="msbutton" id="avsluttskift" value="Avslutt skift" />' . "\n";
		$skiftout .= '</form>' . "\n";
        

        
		$skiftout .= '</div>' . "\n"; // skift_full
		
		$pre = '<h1>Rapport - Feilmeldingstjenesten</h1><div class="level2">';
        $post = '</div>';
		return $pre . $skiftout . $post;
	
	}
	
	private function _modTellerOrder($orderact) {
		$tellerid = $_GET['tellerid'];
		
		if (!isset($tellerid)) die('TellerID ikke gitt!');
		try {
			$objTeller = SkiftFactory::getTeller($tellerid);
		}
		catch (Exception $e) {
			msg('Klarte ikke å endre tellerrekkefølge: ' . $e->getMessage(), -1);
			return false;
		}
		if (!($objTeller instanceof Teller)) die('Ugyldig TellerID gitt');
		
		try {
			if ($orderact == 'opp') {
				$objTeller->modOrderOpp();
			} elseif ($orderact == 'ned') {
				$objTeller->modOrderNed();
			}
		}
		catch (Exception $e) {
			msg('Klarte ikke å endre tellerrekkefølge: ' . $e->getMessage(), -1);
			return false;
		}
	}
	
	private function _genTellerAdm() {
		$tellercol = SkiftFactory::getAlleTellere(); // type TellerCollection
		$numAktiveTellere = 0;
		$numInaktiveTellere = 0;
		
		foreach ($tellercol as $objTeller) {
			$telleroutput = '';
			$telleroutput .= '<tr>' . "\n";
			$telleroutput .= '<td style="width:15%">' . $objTeller->getTellerType() . '</td>' . "\n";
			$telleroutput .= '<td style="width:30%">' . $objTeller->getTellerName() . '</td>' . "\n";
			$telleroutput .= '<td style="width:40%">' . $objTeller->getTellerDesc() . '</td>' . "\n";
			$telleroutput .= '<td style="width:15%"><a href="' . MS_FMR_LINK . '&amp;act=flipteller&amp;tellerid=' . $objTeller->getId() . '">' . (($objTeller->isActive()) ? '<img src="'.MS_IMG_PATH.'trash.png" title="Deaktiver teller" alt="deaktiver">' : '<img src="'.MS_IMG_PATH.'success.png" title="Aktiver teller" alt="aktiver">' ) . '</a>' . "\n";
			if ($objTeller->isActive()) {
				$telleroutput .= '<a href="'. MS_FMR_LINK.'&amp;act=modtellerorderopp&amp;tellerid='. $objTeller->getId().'"><img src="'.MS_IMG_PATH.'up.png" alt="opp" Title="Flytt oppover"></a>';
				$telleroutput .= '<a href="'. MS_FMR_LINK.'&amp;act=modtellerorderned&amp;tellerid='. $objTeller->getId().'"><img src="'.MS_IMG_PATH.'down.png" alt="ned" title="Flytt nedover"></a>';
			}
			$telleroutput .= '</td></tr>' . "\n";
			
			if ($objTeller->isActive()) {
				$aktivoutput .= $telleroutput;
				$numAktiveTellere++;
			} else {
				$inaktivoutput .= $telleroutput;
				$numInaktiveTellere++;
			}
		}
		
		$headers .= '
			<tr style="background-color: #DEE7EC">
				<td style="width:15%">Tellertype:</td>
				<td style="width:30%">Tellernavn:</td>
				<td style="width:40%">Tellerlabel</td>
				<td style="width:15%">Handlinger</td>
			</tr>';
			
		$notellere = '<div class="mswarningbar warningentemplates" id="ingenlivetemplates">Ingen tellere her.</div>';
		
		$output .= '<h1>Telleradministrasjon</h2><div class="level2">';
		$output .= '<h2>Aktive tellere:</h2><div class="level3">';
		if ($numAktiveTellere == 0) {
			$output .= $notellere;
		} else {
			$output .= "<table style=\"width:75%\">\n";
			$output .= $headers;
			$output .= $aktivoutput;
			$output .= "</table>\n";
		}
		
		$output .= '</div><h2>Inaktive tellere:</h2><div class="level3">';
		if ($numInaktiveTellere == 0) {
			$output .= $notellere;
		} else {
			$output .= "<table style=\"width:75%\">\n";
			$output .= $headers;
			$output .= $inaktivoutput;
			$output .= "</table>\n";
		}
		
		$output .= "</div><h2>Legg til teller:</h2><div class=\"level3\">";
		$output .= '
			<form action="' . MS_FMR_LINK . '&amp;act=nyteller" method="POST">
			<table>
				<tr>
					<td>
						<label for="tellertype" title="Primærtellere har egne +/- knapper, sekundærtellere kommer i dropdown-liste og deler +/- knapper. Ulogget-tellere kommer i annen dropdown med felles +/- knapp">Tellertype: </label>
						<select name="tellertype" class="msedit" id="tellertype">
							<option value="TELLER">Primærteller</option>
							<option value="SECTELLER">Sekundærteller</option>
							<option value="ULOGGET">Uloggetteller</option>
						</select>
					</td>
					<td>
						<label for="tellernavn" title="Kun bokstaver: A-Z. Min 3, maks 20 tegn.">Tellernavn: </label>
						<input type="text" name="tellernavn" id="tellernavn" />
					</td>
					<td>
						<label for="tellerdesc" title="Beskrivelse av teller, vises som label på teller i skift-visning">Tellerlabel: </label>
						<input type="text" name="tellerdesc" id="tellerdesc" />
					</td>
					<td><input type="submit" name="nyteller" value="Lagre" class="msbutton" /></td>
				</tr>
			</table>
			</form>
		';
	
		return $output . '</div></div>'; // close level2 og 3
	}
	
	private function _nyTeller() {
		global $msdb;
		
		
		if (!preg_match('/^[A-Z]{3,20}$/AD', strtoupper($_REQUEST['tellernavn']))) {
			msg('Kan ikke opprette teller, tellernavn er ugyldig. Se hjelpetekst.', -1);
			return false;
		}
		if (!preg_match('/^TELLER|SECTELLER|ULOGGET$/AD', $_REQUEST['tellertype'])) {
			msg('Kan ikke opprette teller, tellertype er ugyldig. Du må gjøre et valg i listen.', -1);
			return false;
		}
		if (strlen(trim($_REQUEST['tellerdesc'])) < 2 || strlen(trim($_REQUEST['tellerdesc'])) > 100) {
			msg('Kan ikke opprette teller, tellerlabel er ugyldig. Se hjelpetekst.', -1);
			return false;
		}
		
		$safetellernavn = $msdb->quote(strtoupper($_REQUEST['tellernavn']));
		$safetellertype = $msdb->quote($_REQUEST['tellertype']);
		$safetellerdesc = $msdb->quote(htmlspecialchars(trim($_REQUEST['tellerdesc'])));
		
		$sql = "INSERT INTO feilrap_teller (tellernavn, tellerdesc, tellertype, isactive, tellerorder) VALUES ($safetellernavn, $safetellerdesc, $safetellertype, '0', NULL);";
		$result = $msdb->exec($sql);
		
		if ($result === 1) {
			msg('Opprettet ny teller: ' . $safetellernavn, 1);
			return true;
		} else {
			msg('Klarte ikke å opprette teller', -1);
			return false;
		}
		
	
	}
	
	private function _flipTeller() {
		global $msdb;
		
		$tellerid = $_REQUEST['tellerid'];
		if (preg_match('/^[0-9]{1,4}$/AD', $tellerid)) {
			
			$objTeller = SkiftFactory::getTeller($tellerid);
			if (!($objTeller instanceof Teller)) {
				msg('Kan ikke endre teller, ugyldig tellerid gitt', -1);
				return false;
			}
			
			try {
				$resultat = $objTeller->modIsActive();
			}
			catch (Exception $e) {
				msg('Klarte ikke å endre teller: ' . $e->getMessage());
				return false;
			}
			
			
			if ($resultat) {
				msg('Endret status på teller', 1);
				return true;
			} else {
				msg('Klarte ikke å endre tellerstatus', -1);
				return false;
			}
			
		} else {
			msg('Kan ikke endre teller, ugyldig tellerid gitt', -1);
			return false;
		}
	}
	
	public function getCurrentSkiftId($userid = false) {
		if ($userid === false) $userid = $this->_userId;
		if (isset($this->_currentSkiftId) && $userid == $this->_userId) return $this->_currentSkiftId;
		
		global $msdb;
		$userid = $msdb->quote($userid);

		$result = $msdb->num("SELECT skiftid FROM feilrap_skift WHERE israpportert='0' AND skiftclosed IS NULL AND userid=$userid AND skiftcreated > (now() - INTERVAL 14 HOUR) ORDER BY skiftid DESC LIMIT 1;");
	
		if (is_numeric($result[0][0])) {
			$this->_currentSkiftId = $result[0][0];
			return $result[0][0];
		} else {
			return false;
		}
	}
	
	private function _undoAkt() {
		global $msdb;
		$skiftid = $this->getCurrentSkiftId();
		$safetelleraktid = $msdb->quote($_REQUEST['aktid']);
        
        $sql = "SELECT skiftid FROM feilrap_tellerakt WHERE telleraktid=$safetelleraktid LIMIT 1;";
        $data = $msdb->num($sql);
        if($data[0][0] === $skiftid) {
            $sql = "DELETE FROM feilrap_tellerakt WHERE telleraktid=$safetelleraktid LIMIT 1;";
            $res = $msdb->exec($sql);
            if($res) {
                msg('Aktivitet slettet.', 1);
                return;
            } else {
                msg('Klarte ikke å angre aktivitet.', -1);
                return;
            }
        } else {
            msg('Kan ikke angre aktivitet som ikke tilhører ditt aktive skift.');
            return;
        }
		
	}
	
	private function _changeTeller() {

			$inputverdi = $_REQUEST['modtellerverdi'];

		if (!isset($inputverdi)) {
			msg('Klarte ikke å endre teller: Ingen verdi angitt', -1);
			return false;
		}
		
		$skiftid = $this->getCurrentSkiftId();
		if ($skiftid === false) die('Forsøk på å endre teller uten å ha et aktivt skift!');
		
		if ($_REQUEST['tellerid']) {
			$tellerid = $_REQUEST['tellerid'];
		} else {
			msg('Kan ikke endre teller: ingen teller valgt.', -1);
			return false;
		}
		
		if (array_key_exists('inc_teller', $_REQUEST)) {
			$decrease = false;
		} elseif (array_key_exists('dec_teller', $_REQUEST)) {
			$decrease = true;
		} else {
			die('Verken increase eller decrease teller er gitt.');
		}
		
		if ($tellerid == 'NOSEL') {
			msg('Du må gjøre et valg i listen!', -1);
			return false;
		}
		
		try {
			$objTeller = SkiftFactory::getTellerForSkift($tellerid, $skiftid);
			$objTeller->modTeller($inputverdi, $decrease);
		} 
		catch (Exception $e){
			msg('Klarte ikke å endre teller: ' . $e->getMessage(), -1);
			return false;
		}
		
		return true;
	}
	
	private function _lagreNotat() {
		if ($_REQUEST['lagre'] == 'Avbryt') {
			return false;
		}
		$skiftid = $this->getCurrentSkiftId();
		$notattype = 'ANNET';
		$notattekst = $_POST['notattekst'];
		$notatid = $_POST['notatid'];
		
		if (isset($notatid)) {
			try {
				$objNotat = SkiftFactory::getNotat($notatid);
			} catch(Exception $e) {
				die($e->getMessage());
			}
		} else {
			$objNotat = new Notat(null, $skiftid, $notattype, '', false);
		}
		
		if ($objNotat->getSkiftId() == $skiftid) {
			try {
				$objNotat->setNotatTekst($notattekst);
			}
			catch (Exception $e) {
				msg($e->getMessage(),-1);
				return false;
			}
		}
		
		unset($objNotat);
		return true;
		
	}
	
	private function _slettNotat() {
		$notatid = $_REQUEST['notatid'];
		
		if (isset($notatid)) {
			try {
				$objNotat = SkiftFactory::getNotat($notatid);
			} 
			catch(Exception $e) {
				die($e->getMessage());
			}
			
			if (!($objNotat instanceof Notat)) {
				msg('Kan ikke slette notat, notat finnes ikke', -1);
				return false;
			}
			
			if ($objNotat->getSkiftId() == $this->getCurrentSkiftId()) {
				try {
					$objNotat->modActive(false);
				}
				catch(Exception $e) {
					msg('Klarte ikke å slette notat: ' . $e->getMessage());
					return false;
				}
				
				return true;
			} else {
				msg('Kan ikke slette notat som ikke tilhører ditt aktive skift.', -1);
				return false;
			}
		} else {
			msg('Kan ikke slette notat, notatid ikke angitt',-1);
			return false;
		}
	}
		
	private function _genNoCurrSkift() {
	
		$output .= '<div class="noskift">';
		$output .= '<p>Du har ikke noe aktivt skift. Det kan være flere årsaker til dette:</p>';
		$output .= '<ul class="msul">';
		$output .= '<li>Du har avsluttet skiftet ditt</li>';
		$output .= '<li>Skiftet ditt har blitt inkludert i en rapport</li>';
		$output .= '<li>Skiftet ditt er utløpt &ndash; det har gått mer enn 14 timer siden det ble opprettet</li>';
		$output .= '</ul><br/>';
		$output .= '<form method="post" action="' . MS_FMR_LINK . '">';
		$output .= '<input type="hidden" name="act" value="nyttskift" />';
		$output .= '<input type="submit" class="msbutton" value="Start nytt skift!" />';
		$output .= '</form>';
		$output .= '</div>';
		
        $pre = '<h1>Rapport - Feilmeldingstjenesten</h1><div class="level2">';
        $post = '</div>';
		return $pre . $output . $post;
	
	}
	
	private function _createNyttSkift() {
		global $msdb;
		if (!$this->getCurrentSkiftId() === false) { die('Kan ikke opprette nytt skift når det allerede finnes et aktivt skift.'); }
		
		$result = $msdb->exec("INSERT INTO feilrap_skift (skiftcreated, israpportert, userid, skiftlastupdate) VALUES (now(), '0', " . $msdb->quote($this->_userId) . ", now());");
		if ($result != 1) {
			die('Klarte ikke å opprette skift!');
		} else {
			if (isset($this->_currentSkiftId)) { unset($this->_currentSkiftId); }
			return true;
		}


		
	}
	
	private function _closeCurrSkift() {
		if ($this->getCurrentSkiftId() === false) { die('Kan ikke avslutte skift: Finner ikke aktivt skift.'); }
		
		if (!$this->_closeSkift($this->getCurrentSkiftId())) {
			msg('Klarte ikke å lukke skift med id: ' . $this->getCurrentSkiftId());
			return false;
		} else {
			if (isset($this->_currentSkiftId)) { unset($this->_currentSkiftId); }
			return true;
		}
	
	}
	
	private function _closeSkift($skiftid) {	
		try {
			$objSkift = SkiftFactory::getSkift($skiftid);
		}
		catch (Exception $e) {
			msg($e->getMessage(), -1);
			return false;
		}
		
		if ($objSkift->isClosed()) {
			msg('Skift er allerede avsluttet', -1);
			return false;
		}
		
		if ($objSkift->getSkiftOwnerId() != $this->_userId) {
			if ($this->_accessLvl < MSAUTH_2) {
				msg('Du har ikke adgang til å lukke skift som ikke er ditt eget.', -1);
				return false;
			}
			
			return $objSkift->closeSkift();
			
		} else {
			return $objSkift->closeSkift();
		}
		
	
	}
	
	private function _saveRapportTemplate() {
		
		$templateid = (int) $_REQUEST['templateid'];
		$inputtekst = $_REQUEST['inputtpl'];
		
		if ((!isset($templateid)) || ($templateid == 0)) die('Kan ikke lagre template: TemplateID ikke gitt');
		if (!isset($inputtekst)) {
			msg('Kan ikke lagre template: Ingen input gitt', -1);
			return false;
		}
		
		$objTemplate = RapportTemplateFactory::getTemplate($templateid);
		
		if (!($objTemplate instanceof RapportTemplate)) {
			msg('Kan ikke lagre template: Ugyldig templateid.', -1);
			return false;
		}
		
		try {
			$resultat = $objTemplate->saveTemplate($inputtekst);
		}
		catch (Exception $e) {
			msg('Klarte ikke å endre template: ' . $e->getMessage(), -1);
			return false;
		}
		
		if ($resultat) {
			msg('Endret template med id: ' . $objTemplate->getId() . '.', 1);
			return true;
		} else {
			msg('Klarte ikke å endre template med id: ' . $objTemplate->getId() . '.', -1);
			return false;
		}
		
		
	}
	
	private function _genModRapportTemplates() {
	
		$output .= '<h1>Templateadministrasjon</h1><div class="level2">';
		
		$colTemplates = RapportTemplateFactory::getTemplates();
		$colActiveTemplates = new RapportTemplateCollection();
		$colInactiveTemplates = new RapportTemplateCollection();
		
		foreach ($colTemplates as $objTemplate) {
			if ($objTemplate->isActive()) {
				$colActiveTemplates->addItem($objTemplate);
			} else {
				$colInactiveTemplates->addItem($objTemplate);
			}		
		}
		
		// LIVE
		
		$output .= '<h2>Live templates:</h2><div class="level3">';
		if ($colActiveTemplates->length() > 0) {
			$output .= '<table class="mstemplatelist">' . "\n";
			$output .= '<tr><th>ID:</th><th>Live siden:</th><th>Ant. rapp.:</th><th>Handlinger:</th></tr>' . "\n";
			foreach ($colActiveTemplates as $objTemplate) {
				$output .= '<tr>' . "\n";
				$output .= '<td>' . $objTemplate->getId() . '</td>' . "\n";
				$output .= '<td>' . date('j.n.y \k\l\. H:i', $objTemplate->getLiveDate()) . '</td>' . "\n";
				$output .= '<td>' . $objTemplate->getNumRapporter() . '</td>' . "\n";
				$output .= '<td>'; // handlinger start
				$output .= '<a href="' . MS_FMR_LINK . '&amp;act=showtplmarkup&amp;templateid=' . $objTemplate->getId() . '"><img src="' . MS_IMG_PATH . 'magnifier.png" height="16" width="16" alt="Kode" title="Se template markup" /></a>';
				$output .= '<a href="' . MS_FMR_LINK . '&amp;act=showtplpreview&amp;templateid=' . $objTemplate->getId() . '"><img src="' . MS_IMG_PATH . 'page.png" height="16" width="16" alt="Vis" title="Forhåndsvis template" /></a>';
				$output .= '</td>' . "\n"; // handlinger slutt
				$output .= '</tr>' . "\n";
			}
			$output .= '</table>' . "\n";
		} else {
			// Ingen live templates
			$output .= '<div class="mswarningbar warningentemplates" id="ingenlivetemplates">';
			$output .= 'Du har ikke noe live template.<br /> Rapportering vil ikke fungere før du oppretter et.';
			$output .= '</div>'; // mswarningbar
		}
		
		
		// DRAFT
		
		$output .= '</div><h2>Draft templates:</h2><div class="level3">';
		if ($colInactiveTemplates->length() > 0) {
			$output .= '<table class="mstemplatelist">' . "\n";
			$output .= '<tr><th>ID:</th><th>Opprettet:</th><th>Handlinger:</th></tr>' . "\n";
			foreach ($colInactiveTemplates as $objTemplate) {
				$output .= '<tr>' . "\n";
				$output .= '<td>' . $objTemplate->getId() . '</td>' . "\n";
				$output .= '<td>' . date('j.n.y \k\l\. H:i', $objTemplate->getCreateDate()) . '</td>' . "\n";
				$output .= '<td>'; // handlinger start
				$output .= '<a href="' . MS_FMR_LINK . '&amp;act=showtplmarkup&amp;templateid=' . $objTemplate->getId() . '"><img src="' . MS_IMG_PATH . 'magnifier.png" height="16" width="16" alt="Kode" title="Se template markup" /></a>';
				$output .= '<a href="' . MS_FMR_LINK . '&amp;act=showtplpreview&amp;templateid=' . $objTemplate->getId() . '"><img src="' . MS_IMG_PATH . 'page.png" height="16" width="16" alt="Vis" title="Forhåndsvis template" /></a>';
				$output .= '<a href="' . MS_FMR_LINK . '&amp;act=genmodtpl&amp;templateid=' . $objTemplate->getId() . '"><img src="' . MS_IMG_PATH . 'pencil.png" height="16" width="16" alt="Rediger" title="Rediger template" /></a>';
				$output .= '<a href="' . MS_FMR_LINK . '&amp;act=sletttpl&amp;templateid=' . $objTemplate->getId() . '"><img src="' . MS_IMG_PATH . 'trash.png" height="16" width="16" alt="Slett" title="Slett template" /></a>';
				$output .= '<a href="' . MS_FMR_LINK . '&amp;act=modtpllive&amp;templateid=' . $objTemplate->getId() . '"><img src="' . MS_IMG_PATH . 'success.png" height="16" width="16" alt="Live" title="Go Live! Denne templaten vil bli brukt på nye rapporter" /></a>';
				$output .= '</td>' . "\n"; // handlinger slutt
				$output .= '</tr>' . "\n";
			}
			$output .= '</table>' . "\n";
		} else {
			// Ingen draft templates
			$output .= '<div class="mswarningbar warningentemplates" id="ingendrafttemplates">';
			$output .= 'Ingen draft templates her!';
			$output .= '</div>'; // mswarningbar
		}
		
		$output .= '</div>' . "\n";
	
		$output .= '<form action="' . MS_FMR_LINK . '" method="POST">';
		$output .= '<input type="hidden" name="act" value="nyraptpl" />';
		$output .= '<input type="submit" class="msbutton" name="savetpl" value="Opprett nytt template" />';
		$output .= '</form>';
		
		return $output . '</div>';
	}
	
	private function _modTemplateLive() {
		if (isset($_REQUEST['templateid'])) {
			$templateid = $_REQUEST['templateid'];
		} else {
			msg('Klarte ikke å markere template som live: Template ID ikke angitt.', -1);
			return false;
		}
		
		$objTemplate = RapportTemplateFactory::getTemplate($templateid);
		
		if (!($objTemplate instanceof RapportTemplate)) {
			msg('Klarte ikke å markere template som live: Ugyldig templateid.', -1);
			return false;
		}
		
		try {
			$resultat = $objTemplate->goLive();
		}
		catch (Exception $e) {
			msg('Klarte ikke å markere template som live: ' . $e->getMessage(), -1);
			return false;
		}
		
		if ($resultat) {
			msg('Template med id: ' . $objTemplate->getId() . ' er nå live!', 1);
			return true;
		} else {
			msg('Klarte ikke å markere template som live.', -1);
			return false;
		}
	
	}
	
	private function _slettTemplate() {
		if (isset($_REQUEST['templateid'])) {
			$templateid = $_REQUEST['templateid'];
		} else {
			msg('Klarte ikke å slette template: Template ID ikke angitt.', -1);
			return false;
		}
		
		$objTemplate = RapportTemplateFactory::getTemplate($templateid);
		
		if (!($objTemplate instanceof RapportTemplate)) {
			msg('Klarte ikke å slette template: Ugyldig templateid.', -1);
			return false;
		}
		
		try {
			$resultat = $objTemplate->slettTemplate();
		}
		catch (Exception $e) {
			msg('Klarte ikke å slette template: ' . $e->getMessage(), -1);
			return false;
		}
		
		if ($resultat) {
			msg('Slettet template med id: ' . $objTemplate->getId(), 1);
			return true;
		} else {
			msg('Klarte ikke å slette template', -1);
			return false;
		}
	
	}
	
	private function _genModTemplate($mode) {
		if (isset($_REQUEST['templateid'])) {
			$templateid = $_REQUEST['templateid'];
		} else {
			msg('Kan ikke vise template, templateid ikke gitt.', -1);
			return false;
		}
		
		$objTemplate = RapportTemplateFactory::getTemplate($templateid);
		
		if (!($objTemplate instanceof RapportTemplate)) {
			msg('Ugyldig templateid.', -1);
			return false;
		}


		$output .= '<span class="actheader">Templateadministrasjon</span><br />';
		
		if ($mode == 'markup') {
			$output .= '<br /><span class="subactheader">Markup for templateid ' . $objTemplate->getId() . ':</span><br />' . "\n";
			$output .= '<p class="templatemarkup"><pre>' . htmlspecialchars($objTemplate->getTemplateTekst()) . '</pre></p>';
		} elseif ($mode == 'preview') {
			$colskift = new SkiftCollection();
			$colskift->addItem(new Skift(0, date('Y-m-d H:i:s'), 0));
			$objRapport = new Rapport($this->_userId, null, null, false, $templateid, null);
			$objRapport->skift = $colskift;
			$output .= '<br /><span class="subactheader">Preview for templateid ' . $objTemplate->getId() . ':</span><br />' . "\n";
			$output .= '<p class="templatemarkup">' . $objRapport->genRapportTemplate() . '</p>';
		} elseif ($mode == 'edit') {
			$output .= '<br /><span class="subactheader">Redigerer templateid ' . $objTemplate->getId() . ':</span><br />' . "\n";
			$output .= '<form action="' . MS_FMR_LINK . '" method="POST">';
			$output .= '<input type="hidden" name="act" value="modraptpl" />';
			$output .= '<input type="hidden" name="templateid" value="' . $objTemplate->getId() . '" />';
			$output .= '<textarea name="inputtpl" cols="80" rows="40">' . $objTemplate->getTemplateTekst() . '</textarea>';
			$output .= '<br /><input type="submit" class="msbutton" name="savetpl" value="Lagre endringer" />';
			$output .= '</form>';
		}
		
		$output .= '
			<form name="tilbake" action="' . MS_FMR_LINK . '" method="POST">
				<input type="hidden" name="act" value="genmodraptpl" />
				<input type="submit" class="msbutton" value="Tilbake" />
			</form>
			';
		
		return $output;
	}
	
	private function _nyRapportTemplate() {
		global $msdb;
		
		$sql = "INSERT INTO feilrap_raptpl (templatetekst, tplisactive, createdate) VALUES ('Ikke noe innhold i rapport-template enda.', '0', now());";
		$resultat = $msdb->exec($sql);
		
		if ($resultat === 1) {
			msg('Ny rapport-template opprettet', 1);
			return true;
		} else {
			msg('Klarte ikke å opprette ny rapport-template', -1);
			return false;
		}
	
	}	

	public function registrer_meny(MenyitemCollection &$meny){
		$lvl = $this->_accessLvl;
		$act = $this->getMsmodulact();
        
		if ($lvl > MSAUTH_NONE) { 
		
			$toppmeny = new Menyitem('FeilM Rapport','&amp;page=feilmrapport');
            
            if (isset($act) && array_search('notoc', (array) $this->_msmodulvars) === false) {
                $telleradmin = new Menyitem('Rediger tellere','&amp;page=feilmrapport&amp;act=telleradm');
                $genrapport = new Menyitem('Lag rapport','&amp;page=feilmrapport&amp;act=genrapportsel');
                $rapportarkiv = new Menyitem('Rapportarkiv','&amp;page=feilmrapport&amp;act=rapportarkiv');
                $tpladmin = new Menyitem('Rapporttemplates','&amp;page=feilmrapport&amp;act=genmodraptpl');
                
                switch($act) {
                    case 'stengskift':
                    case 'genrapportsel':
                    case 'gensaverapport':
                    case 'genrapportmod':
                        $objSelected = $genrapport;
                        break;
                    case 'rapportarkiv':
                    case 'visrapport':
                        $objSelected = $rapportarkiv;
                        break;
                    case 'nyteller':
                    case 'flipteller':
                    case 'modtellerorderned':
                    case 'modtellerorderopp':
                    case 'telleradm':
                        $objSelected = $telleradmin;
                        break;
                    case 'modtpllive':
                    case 'sletttpl':
                    case 'showtplmarkup':
                    case 'showtplpreview':
                    case 'genmodtpl':
                    case 'nyraptpl':
                    case 'genmodraptpl':
                    case 'modraptpl':
                        $objSelected = $tpladmin;
                        break;
                    case 'stengegetskift':
                    case 'nyttskift':
                    case 'savenotat':
                    case 'delnotat':
                    case 'undoakt':
                    case 'mod_teller':
                    case 'show':
                    default:                    
                        $objSelected = $toppmeny;
                        break;
                }
                if($objSelected instanceof Menyitem) {
                    $selectedtekst = '<span class="selected">' . $objSelected->getTekst() . '</span>';
                    $objSelected->setTekst($selectedtekst);
                }
            
                if ($lvl >= MSAUTH_2) {
                    $toppmeny->addChild($genrapport);
                    $toppmeny->addChild($rapportarkiv);
                }
                if ($lvl >= MSAUTH_5) {
                    $toppmeny->addChild($telleradmin);
                    $toppmeny->addChild($tpladmin);
                }
            }
			
			$meny->addItem($toppmeny); 
			
		}
		
	}
	
}
