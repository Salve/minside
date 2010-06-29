<?php
if(!defined('MS_INC')) die();
define('MS_FMR_LINK', MS_LINK . "&page=feilmrapport");
require_once('class.feilmrapport.skift.php');
require_once('class.feilmrapport.teller.php');
require_once('class.feilmrapport.notat.php');
require_once('class.feilmrapport.rapport.php');
require_once('class.feilmrapport.skiftfactory.php');
require_once('class.feilmrapport.rappvalidator.php');
require_once('class.feilmrapport.rapporttemplate.php');
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
				if ($this->_accessLvl >= MSAUTH_2) $this->_frapout .= $this->_genRapportArkiv();
				break;
			case "visrapport":
				if ($this->_accessLvl >= MSAUTH_2) $this->_frapout .= $this->_genRapport();
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
			case "mod_teller":
				try {
					if(array_key_exists('inc_teller', $_REQUEST)) {
						$this->_changeTeller($_REQUEST['tellerid'], false);
					} elseif(array_key_exists('dec_teller', $_REQUEST)) {
						$this->_changeTeller($_REQUEST['tellerid'], true);
					}
				}
				catch (Exception $e) {
					msg($e->getMessage(), -1);
				}
			case "show":
			default:
				$this->_frapout .= $this->genSkift();
		}
						
		return $this->_frapout;
	
	}
	
	private function genNotat($objNotat, $edit = false) {
		if ($edit) {
			$output .= '<p>';
			$output .= '<form action="' . MS_FMR_LINK . '" method="POST">';
			$output .= '<input type="hidden" name="act" value="savenotat" />';
			if ($objNotat instanceof Notat) {
				$output .= '<input type="hidden" name="notatid" value="' .  $objNotat->getId() . '" />';
			}
			$output .= '<textarea id="notattekst" class="msedit" style="left:0px;" name="notattekst" rows="3" cols="40">';
			$output .= $objNotat;
			$output .= '</textarea>';
			$output .= '<input type="submit" name="lagre" value="lagre" class="msbutton">';
			$output .= '<input type="submit" name="lagre" value="angre" class="msbutton">';
			$output .= '</form>';
			$output .= '</p>';
		} else {
			if ($objNotat instanceof Notat) {
				$stredit = ' <a href="' . MS_FMR_LINK . '&act=modnotat&notatid=' . $objNotat->getId() . '"><img src="/lib/plugins/minside/./minside/bilder/pencil.png"></a>';
				$strslett = ' <a href="' . MS_FMR_LINK . '&act=delnotat&notatid=' . $objNotat->getId() . '"><img src="/lib/plugins/minside/./minside/bilder/trash.png"></a>';
			}
			$output .= '<li>' . $objNotat . $stredit . $strslett . '</li>';
		}		
		
		return $output;
	}
	
	private function _genRapportArkiv() {
		
		$output .= "\n\n" . '<div class="rapportarkiv">';
		
		if ( ($this->_accessLvl < MSAUTH_3) || ( !isset($_REQUEST['arkivmnd']) ) ) {
		
			$rapportcol = SkiftFactory::getNyligeRapporter(); // Returnerer en RapportCollection
			
			
			$output .= '<p><strong>Rapporter opprettet det siste døgnet:</strong></p>' . "\n";
			
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
			$output .= '<p><strong>Rapporter fra ' . self::$monthnames["$inputmonth"] . ' ' . $inputyear . ':</strong></p>' . "\n";		
			
			$output .= $this->_genRapportListe($rapportcol, true);
			
		}
		
		
		
		if ($this->_accessLvl >= MSAUTH_3) $output .= $this->_genRapportArkivMenu();
		
		$output .= '</div> <!-- rapportarkiv -->' . "\n"; // rapportarkiv
		
		return $output;
	
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
			$rapporttime = date('G', $createtime); // 0-23
			if ($rapporttime >= 6 && $rapporttime < 10) {
				$skifttype = 'nskift';
			} elseif ($rapporttime >= 14 && $rapporttime < 18) {
				$skifttype = 'dskift';
			} elseif ($rapporttime >= 22) {
				$skifttype = 'eskift';
			} else {
				$skifttype = 'ukjentskift';
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
				
				$output .= '<span class="rapportnavn ' . $skifttype . ' ' . $rapportspanclass . '"><a href="' . MS_FMR_LINK . '&act=visrapport&rapportid=' . $objRapport->getId() . '">';
				$output .= date('H:i', $createtime) . ' &mdash; ' . $objRapport->getRapportOwnerName(); // . ' ' . strtoupper(substr($skifttype, 0, 1))
				$output .= '</a></span><br />' . "\n";
				
			} else { 
				
				$rapportcounter++;
				$rapportspanclass = ($rapportcounter & 1) ? 'rapone' : 'raptwo';
	
				$output .= '<span style="font-size:1em;" class="rapportnavn ' . $skifttype . ' ' . $rapportspanclass . '">';
				$output .= '<a href="' . MS_FMR_LINK . '&act=visrapport&rapportid=' . $objRapport->getId() . '">';
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
		
		$output = '<p><strong>Rapportarkiv:</strong></p>' . "\n";
	
		if(is_array($data) && sizeof($data)) {
			foreach ($data as $datum) {
			
				$arMonths = explode(',', $datum['MONTHS']);
				
				$year = $datum['YEAR'];
				$monthlist = '';
				foreach ($arMonths as $month) {
					$monthlist .= '<a href="' . MS_FMR_LINK . '&act=rapportarkiv&arkivmnd=' . $year . '-' . $month . '">' . substr(self::$monthnames["$month"], 0, 3) . '</a> ' . "\n";
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
	
		$output = '<div class="rapportarkivmeny">' . $output . '</div>';
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
			
			return $output;
			
		} else {
			msg('Rapport id ikke definert.', -1);
			return;
		}
	}
	
	private function _genModRapport(){
			$skiftcol = new SkiftCollection();
			
			
			$validationerrors = 0;
			
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
				if ($skiftcol->length() == 0) return 'Ingen skift valgt.'; // selskift inneholder noe, men ingen valid skift
			} else {
				return 'Ingen skift valgt.';
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
			
			} elseif ($submitsave) {
				$rappoutput = '<div class="rapporthaserrors">Rapporten kunne ikke genereres da ett eller flere av inputfeltene inneholder ugyldig data, eller er tomme.</div><br />' . "\n";
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
			
			
			
			
			
			// Enkel mail funksjon
			/*
			if ($validsave) {
								
				$mailto = $INFO['userinfo']['mail'];
				$subject = 'Rapport feilmeldingstjenesten ' . date('d.m.Y');
				$headers = 'From: ' . $INFO['userinfo']['mail'] . "\r\n" .
					'Reply-To: noreply@lyse.no' . "\r\n" .
					'X-Mailer: PHP/LyseWiki/MinSide/FeilMRapport' . "\r\n" .
					'MIME-Version: 1.0' . "\r\n" .
					'Content-type: text/html; charset="utf-8"' . "\r\n";
					
				mail($mailto, $subject, $tmpOutput, $headers);
			
			}
			*/
			
			
			return $output;
			
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
			
			if ($objSkift->isClosed()) {			
				
				
				$output .= '<input type="checkbox" name="selskift[]" value="' . $objSkift->getId() . '" />';
				$output .= '&nbsp;' . strtoupper($objSkift->getSkiftOwnerName()) . ' &mdash; ' . $this->LesbarTid($starttid) . ' &ndash; ' . $this->LesbarTid($slutttid) . "<br />\n";
			} else {
				$output .= '<input type="checkbox" name="selskift[]" value="' . $objSkift->getId() . '" disabled />';
				$output .= '&nbsp;' . strtoupper($objSkift->getSkiftOwnerName()) . ' &mdash; ' . $this->LesbarTid($starttid) . ' &ndash; Ikke avsluttet! '; 
				$output .= '(<a href="' . MS_FMR_LINK . '&act=stengskift&skiftid=' . $objSkift->getId() . '">avslutt skift</a>)' . "<br />\n";
			}
		}			
					
		$output .=	'
					<input type="submit" name="subvelgskift" class="msbutton" value="Gå videre">
				</form>
				<form name="tilbake" action="' . MS_FMR_LINK . '" method="POST">
					<input type="hidden" name="act" value="show" />
					<input type="submit" class="msbutton" name="subvelgskift" value="Tilbake" />
				</form>
			</fieldset>
			</div>';
		
		
		
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
	

	public function getEndreteller() {
		$tellerid = $_POST['tellerid'];
		$_endreverdi = $_POST[$tellerid];
		return $_endreverdi;
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
		
		$skiftout .= '&nbsp;<br />';
		$skiftout .= '&nbsp;<br />';
		$skiftout .= '&nbsp;<br />';
		
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
		
		$skiftout .= '<fieldset id="notatfield" class="msfieldset"><legend>Notater</legend><ul class="msul">';
		
		foreach($objSkift->notater as $objNotat) {
			if ($objNotat->isActive()) $skiftout .= $this->genNotat($objNotat);
		}
		if (($this->_msmodulact == 'modnotat') && isset($_REQUEST['notatid'])) {
			$objNotat = $objSkift->notater->getItem($_REQUEST['notatid']);
			$skiftout .= '</ul>';
			$skiftout .= $this->genNotat($objNotat, true);
		} else {
			$skiftout .= '</ul>';
			$skiftout .= $this->genNotat(null, true);
		}
		$skiftout .= '</fieldset>' . "\n";
		
		// Vis tellere
		
		$colUlogget = new TellerCollection();
		$arUlogget = array();
		
		$colSecTeller = new TellerCollection();
		$arSecTeller = array();
		
		$skiftout .= '<table class="feilmtable"><th class="top">Teller</th><th class="top">Verdi</th><th class="top">Endre</th>';	
		foreach($objSkift->tellere as $objTeller) {
			if (!$objTeller->isActive()) continue;
			
			switch ($objTeller->getTellerType()) {
				case 'TELLER':
					$skiftout .= '<tr>' . "\n";
					$skiftout .= '<form action="' . MS_FMR_LINK . '" method="POST">' . "\n";
					$skiftout .= '<td class="feilmtablecols">' . $objTeller->getTellerDesc() . ':</td><td style="text-align:center;"><input type="text" value="1" id="rapverdi" class="msedit" name="'.$objTeller->getId().'">' /*. $objTeller->getTellerVerdi() */. '</td>' . "\n";
					$skiftout .= '<input type="hidden" name="act" value="mod_teller" />' . "\n";
					$skiftout .= '<input type="hidden" name="tellerid" value="' . $objTeller->getId() . '" />' . "\n";
					$skiftout .= '<td><div class="inc_dec"><input type="submit" class="msbutton" name="inc_teller" value="+" /><input type="submit" class="msbutton" name="dec_teller" value="-" /></div></td>' . "\n";
					$skiftout .= '</form>' . "\n";
					$skiftout .= "</tr>\n\n";
					break;
				case 'ULOGGET':
					if ($objTeller->getTellerVerdi() > 0) $colUlogget->addItem(clone($objTeller));
					$arUlogget[$objTeller->getId()] = $objTeller->getTellerDesc();
					break;
				case 'SECTELLER':
					if ($objTeller->getTellerVerdi() > 0) $colSecTeller->addItem(clone($objTeller));
					$arSecTeller[$objTeller->getId()] = $objTeller->getTellerDesc();
					break;
			}
		}
		
		if (!empty($arSecTeller)){
			$skiftout .= '<tr>' . "\n";
			$skiftout .= '<form action="' . MS_FMR_LINK . '" method="POST">' . "\n";
			$skiftout .= '<input type="hidden" name="act" value="mod_teller" />' . "\n";
			$skiftout .= '<td><select name="tellerid" class="msedit" style="width:100%;">' . "\n";
			$skiftout .= '<option value="NOSEL">Annet: </option>' . "\n";
			foreach ($arSecTeller as $tellerid => $tellerdesc) {
				$skiftout .= '<option value="' . $tellerid . '">' . $tellerdesc . '</option>' . "\n";
			}
			$skiftout .= '</select></td><td></td>' . "\n";
			$skiftout .= '<td><div class="inc_dec"><input type="submit" class="msbutton" name="inc_teller" value="+" /><input type="submit" class="msbutton" name="dec_teller" value="-" /></div></td>' . "\n";
			$skiftout .= '</form>' . "\n";
			$skiftout .= "</tr>\n\n";
		}		
		
		if (!empty($arUlogget)){
			$skiftout .= '<tr>' . "\n";
			$skiftout .= '<form action="' . MS_FMR_LINK . '" method="POST">' . "\n";
			$skiftout .= '<input type="hidden" name="act" value="mod_teller" />' . "\n";
			$skiftout .= '<td><select name="tellerid" class="msedit" style="width:100%;">' . "\n";
			$skiftout .= '<option value="NOSEL">Ulogget: </option>' . "\n";
			foreach ($arUlogget as $tellerid => $tellerdesc) {
				$skiftout .= '<option value="' . $tellerid . '">' . $tellerdesc . '</option>' . "\n";
			}
			$skiftout .= '</select></td><td></td>' . "\n";
			$skiftout .= '<td><div class="inc_dec"><input type="submit" class="msbutton" name="inc_teller" value="+" /><input type="submit" class="msbutton" name="dec_teller" value="-" /></div></td>' . "\n";
			$skiftout .= '</form>' . "\n";
			$skiftout .= "</tr>\n\n";
		}
		
		$skiftout .= '</table><br /><br />' . "\n";
		
		$skiftout .= '<div class="antalltall">';
		$skiftout .= "<table> \n";
		$skiftout .= "<tr>";
		foreach($objSkift->tellere as $objTeller) {
			
			if (!$objTeller->isActive()) continue;
			
			if ($objTeller->getTellerType() == 'TELLER') {
				$skiftout .= '<td>' . $objTeller->getTellerDesc() . ':</td><td>' . $objTeller->getTellerVerdi() . "</td></tr>\n";
			}
		}
		$skiftout .= "</table>\n";
		if ($colSecTeller->length() > 0) {
			$skiftout .= '<p>' . "\n";
			$skiftout .= '<strong>Annet:</strong><br />' . "\n";
			
			foreach ($colSecTeller as $objTeller) {
				if ($objTeller->getTellerVerdi() > 0) $skiftout .= $objTeller . '<br />' . "\n";
			}
			$skiftout .= '</p>' . "\n";
		}
		
		if ($colUlogget->length() > 0) {
			$skiftout .= '<p>' . "\n";
			$skiftout .= '<strong>Uloggede samtaler:</strong><br />' . "\n";
			
			foreach ($colUlogget as $objTeller) {
				$skiftout .= $objTeller . '<br />' . "\n";
			}
			$skiftout .= '</p>' . "\n";
		}
		$skiftout .= '</div>';
		// Close skift knapp
		$skiftout .= '<form method="post" action="' . MS_FMR_LINK . '">' . "\n";
		$skiftout .= '<input type="hidden" name="act" value="stengegetskift" />' . "\n";
		$skiftout .= '<input type="submit" class="msbutton" id="avsluttskift" value="Avslutt skift" />' . "\n";
		$skiftout .= '</form>' . "\n";
		
		$skiftout .= '</div>' . "\n"; // skift_full
		
		
		return $skiftout;
	
	}
	
	private function _genTellerAdm() {
	
		$tellercol = SkiftFactory::getAlleTellere(); // type TellerCollection
		
		foreach ($tellercol as $objTeller) {
			$telleroutput = '';
			$telleroutput .= '<tr>' . "\n";
			$telleroutput .= '<td style="width:15%">' . $objTeller->getTellerType() . '</td>' . "\n";
			$telleroutput .= '<td style="width:30%">' . $objTeller->getTellerName() . '</td>' . "\n";
			$telleroutput .= '<td style="width:40%">' . $objTeller->getTellerDesc() . '</td>' . "\n";
			$telleroutput .= '<td style="width:15%"><a href="' . MS_FMR_LINK . '&act=flipteller&tellerid=' . $objTeller->getId() . '">' . (($objTeller->isActive()) ? 'deaktiver' : 'aktiver' ) . '</a></td>' . "\n";
			$telleroutput .= '</tr>' . "\n";
			
			if ($objTeller->isActive()) {
				$aktivoutput .= $telleroutput;
			} else {
				$inaktivoutput .= $telleroutput;
			}
		}
		
		$headers .= '<tr style="background-color: #DEE7EC">' . "\n";
		$headers .= '<td style="width:15%">Tellertype:</td>' . "\n";
		$headers .= '<td style="width:30%">Tellernavn:</td>' . "\n";
		$headers .= '<td style="width:40%">Tellerlabel</td>' . "\n";
		$headers .= '<td style="width:15%">Aktiver/deaktiver</td>' . "\n";
		$headers .= '</tr>' . "\n";
		
		$output .= "<p><strong>Telleradministrasjon</strong></p>\n";
		$output .= "<p><strong>Aktive tellere:</strong></p>\n";
		$output .= "<table style=\"width:75%\">\n";
		$output .= $headers;
		$output .= $aktivoutput;
		$output .= "</table>\n";
		$output .= "<p><strong>Inaktive tellere:</strong></p>\n";
		$output .= "<table style=\"width:75%\">\n";
		$output .= $headers;
		$output .= $inaktivoutput;
		$output .= "</table>\n";
		
		$output .= "<p><strong>Legg til teller:</strong></p>\n";
		$output .= '
			<form action="' . MS_FMR_LINK . '&act=nyteller" method="POST">
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
	
		return $output;
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
		
		$sql = "INSERT INTO feilrap_teller (tellernavn, tellerdesc, tellertype, isactive) VALUES ($safetellernavn, $safetellerdesc, $safetellertype, '1');";
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
		
		if (preg_match('/^[0-9]{1,4}$/AD', $_REQUEST['tellerid'])) {
			
			$safetellerid = $msdb->quote($_REQUEST['tellerid']);
			$sql = "UPDATE feilrap_teller SET isactive = NOT isactive WHERE tellerid=$safetellerid LIMIT 1;";
			$result = $msdb->exec($sql);
			
			if ($result === 1) {
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
	
	public function getParamValue($param, &$fromrequest = false){
		if (array_key_exists($param, $this->_msmodulvars)) {
			$fromrequest = false;
			return $this->_msmodulvars[$param];
		} else if (array_key_exists($param, $_REQUEST)) {
			$fromrequest = true;
			return $_REQUEST[$param];
		} else {
			return false;
		}
	
	}
	
	private function _changeTeller($id, $decrease = false) {
		
		$tellerid = $id;
		$skiftid = $this->getCurrentSkiftId();
		
		if ($skiftid === false) die('Forsøk på å endre teller uten å ha et aktivt skift!');
		
		if ($id == 'NOSEL') {
			throw new Exception('Du må gjøre et valg i listen!');
			return false;
		}
		
		try {
			$objTeller = SkiftFactory::getTeller($tellerid, $skiftid);
			$objTeller->modTeller($decrease);
		} 
		catch (Exception $e){
			throw new Exception($e->getMessage());
			return false;
		}
		
		return true;
	}
	
	private function _lagreNotat() {
		if ($_REQUEST['lagre'] == 'angre') {
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
		
	
		return $output;
	
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
			if ($this->_accessLvl < MSAUTH_3) {
				msg('Du har ikke adgang til å lukke skift som ikke er ditt eget.', -1);
				return false;
			}
			
			return $objSkift->closeSkift();
			
		} else {
			return $objSkift->closeSkift();
		}
		
	
	}
	
	private function _saveRapportTemplate() {
		if (isset($_REQUEST['inputtpl'])) {
			RapportTemplate::saveTemplate($_REQUEST['inputtpl'], RapportTemplate::getCurrentTplId());
		}
	}
	
	private function _genModRapportTemplates() {
	
		if (RapportTemplate::getCurrentTplId() != false) {
	
			$output .= '<form action="' . MS_FMR_LINK . '" method="POST">';
			$output .= '<input type="hidden" name="act" value="modraptpl" />';
			$output .= '<textarea name="inputtpl" cols="80" rows="40" wrap="off">' . RapportTemplate::getRawTemplate() . '</textarea>';
			$output .= '<br /><input type="submit" class="msbutton" name="savetpl" value="Lagre endringer" />';
			$output .= '<br /><br /><p>Endringer her vil påvirke alle rapporter som er opprettet siden sist gang<br /> "NYTT TEMPLATE" knappen ble trykket på, samt rapporter som ikke er opprettet enda</p>';
			$output .= '</form>';
			
			$output .= '<br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br />';
			$output .= '<br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br />';
	
		}

		$output .= '<fieldset>';
		$output .= '<legend>Opprett nytt default template</legend>';
		$output .= '<form action="' . MS_FMR_LINK . '" method="POST">';
		$output .= '<input type="hidden" name="act" value="nyraptpl" />';
		$output .= '<input type="submit" class="msbutton" name="savetpl" value="*FARLIG* NYTT TEMPLATE *FARLIG*" />';
		$output .= '</form><br /><br />';
		$output .= '<p>Denne knappen arkiverer nåværende template, det vil ikke være mulig å gjøre videre endringer på dette.</p>';
		$output .= '<p>Nytt template vil ikke påvirke rapporter som allerede er opprettet.</p>';
		$output .= '<p>Husk å kopiere innholdet fra nåværende template om du vil jobbe videre med dette.</p>';
		$output .= '</fieldset>';
		
		return $output;
	}
	
	private function _nyRapportTemplate() {
		global $msdb;
		
		$sql = "INSERT INTO feilrap_raptpl (templatetekst) VALUES ('Ikke noe innhold i rapport-template enda.');";
		$resultat = $msdb->exec($sql);
		
		if ($resultat === 1) {
			msg('Ny rapport-template opprettett', 1);
			return true;
		} else {
			msg('Klarte ikke å opprette ny rapport-template', -1);
			return false;
		}
	
	}	

	public function registrer_meny(MenyitemCollection &$meny){
		$lvl = $this->_accessLvl;
		
		if ($lvl > MSAUTH_NONE) { 
		
			$toppmeny = new Menyitem('FeilM Rapport','&page=feilmrapport');
			$telleradmin = new Menyitem('Rediger tellere','&page=feilmrapport&act=telleradm');
			$genrapport = new Menyitem('Lag rapport','&page=feilmrapport&act=genrapportsel');
			$rapportarkiv = new Menyitem('Rapportarkiv','&page=feilmrapport&act=rapportarkiv');
			$tpladmin = new Menyitem('Rediger rapport-templates','&page=feilmrapport&act=genmodraptpl');
				
			if (($lvl >= MSAUTH_2) && isset($this->_msmodulact)) {
				$toppmeny->addChild($genrapport);
			}
			if (($lvl >= MSAUTH_2) && isset($this->_msmodulact)) {
				$toppmeny->addChild($rapportarkiv);
			}
			if (($lvl >= MSAUTH_5) && isset($this->_msmodulact)) {
				$toppmeny->addChild($telleradmin);
			}
			if (($lvl >= MSAUTH_5) && isset($this->_msmodulact)) {
				$toppmeny->addChild($tpladmin);
			}
			
			$meny->addItem($toppmeny); 
			
		}
		
	}
	
}



