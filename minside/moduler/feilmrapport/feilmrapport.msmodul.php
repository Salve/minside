<?php
if(!defined('MS_INC')) die();
define(MS_FMR_LINK, MS_LINK . "&page=feilmrapport");
require_once('class.feilmrapport.skift.php');
require_once('class.feilmrapport.teller.php');
require_once('class.feilmrapport.notat.php');
require_once('class.feilmrapport.rapport.php');
require_once('class.feilmrapport.skiftfactory.php');
require_once('class.feilmrapport.rapporttemplate.php');
require_once('class.feilmrapport.tellercollection.php');
require_once('class.feilmrapport.notatcollection.php');
require_once('class.feilmrapport.skiftcollection.php');

class msmodul_feilmrapport implements msmodul{

	private $_msmodulact;
	private $_msmodulvars;
	private $_frapout;
	private $_userId;
	private $_accessLvl;
	private $_currentSkiftId;
	
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
		
		$this->_frapout .= 'Output fra feilmrapport: act er: '. $this->_msmodulact . ', userid er: ' . $this->_userId . '<br />';
		
		switch($this->_msmodulact) {
			case "stengskift":
				if (isset($_REQUEST[skiftid])) $this->_closeSkift($_REQUEST[skiftid]);
			case "genrapportsel":
				if ($this->_accessLvl >= MSAUTH_3) $this->_frapout .= $this->_genRapportSelectSkift();
				break;
			case "genrapportmod":
				if ($this->_accessLvl >= MSAUTH_3) $this->_frapout .= $this->_genRapportSelectNotat();
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
			case "telleradm":
				if ($this->_accessLvl >= MSAUTH_5) $this->_frapout .= $this->_genTellerAdm();
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
		
		//$this->_frapout .= '<br /><img width="600" height="200" src="lib/plugins/minside/minside/moduler/diff/chartimg.php">';
						
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
			$output .= '<textarea name="notattekst" rows="3" cols="40">';
			$output .= $objNotat;
			$output .= '</textarea>';
			$output .= '<input type="submit" name="lagre" value="lagre" class="button">';
			$output .= '<input type="submit" name="lagre" value="angre" class="button">';
			$output .= '</form>';
			$output .= '</p>';
		} else {
			if ($objNotat instanceof Notat) {
				$stredit = ' (<a href="' . MS_FMR_LINK . '&act=modnotat&notatid=' . $objNotat->getId() . '">rediger</a>)';
				$strslett = ' (<a href="' . MS_FMR_LINK . '&act=delnotat&notatid=' . $objNotat->getId() . '">slett</a>)';
			}
			$output .= '<li>' . $objNotat . $stredit . $strslett . '</li>';
		}		
		
		return $output;
	}
	
	private function _genRapportSelectNotat(){
			global $INFO;
			$skiftcol = new SkiftCollection();
			$totaltellere = array();
			$brukertellere = array();
			
			$tellercol = new TellerCollection();
			$notatcol = new NotatCollection();
			
			$tplErstatter = new Erstatter();
			
			foreach ($_POST['selskift'] as $skiftid) {
				try {
					$objSkift = SkiftFactory::getSkift($skiftid);
					$skiftcol->addItem($objSkift);
				}
				catch (Exception $e) {
					msg($e->getMessage,-1);
					return false;
				}
			}
			
			foreach ($skiftcol as $objSkift) {
				foreach ($objSkift->notater as $objNotat) {
					if ($objNotat->isActive()) {
						$notatoutput .= '<input type="checkbox" name="selnotat[]" value="' . $objNotat->getId() . '" /> ';
						$notatoutput .= $objNotat . ' (' . $objSkift->getSkiftOwnerName() . ")<br />\n";
					}
				}
				foreach ($objSkift->tellere as $objTeller) {
					if ($objTeller->getTellerVerdi() > 0) {
						if ($tellercol->exists($objTeller->getTellerName())) {
							$objColTeller = $tellercol->getItem($objTeller->getTellerName());
							$objColTeller->setTellerVerdi($objColTeller->getTellerVerdi() + $objTeller->getTellerVerdi());
						} else {
							$objColTeller = new Teller($objTeller->getId(), $objSkift->getId(), $objTeller->getTellerName(), $objTeller->getTellerDesc(), $objTeller->getTellerType(), $objTeller->getTellerVerdi());
							$tellercol->addItem($objColTeller, $objTeller->getTellerName());
						}
						
						if ($objTeller->getTellerType() == 'ULOGGET') {
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
						$brukertellernotater[$tellername] .= $brukernavn . ': ' . $brukertotal . ' (' . round($brukertotal / $tellertotaler[$tellername] * 100) . '%) ';
					}
				}
				
				if ($uloggettotaler[$tellername] > 0) {
					$objTeller = $tellercol->getItem($tellername);
					$uloggetoutput .= '<span title="' . $brukertellernotater[$tellername] . '">' . $objTeller->getTellerDesc() . ': ' . $objTeller->getTellerVerdi() . "</span><br />\n";
				}
						
			}
			
			
			$tpldata['fulltnavn'] = $INFO['userinfo']['name'];
			$tpldata['datotid'] = date('dmy \&\n\d\a\s\h\; H:i');
			
			
			$tplErstatter->addErstattning('/\[\[notater:[A-Za-z]+\]\]/u', $notatoutput);
			$tplErstatter->addErstattning('/\[\[teller:([A-Z]+)\]\]/ue', '\'<span title="\' . $brukertellernotater[$1] . \'">\' . ((@call_user_func(array($tellercol->getItem($1), getTellerVerdi)))?:\'0\') . \'</span>\''); // FIXME dette funker, men call_user_func gir error (suppressed nå)
			$tplErstatter->addErstattning('/\[\[inputbool:([A-Za-z]+)\]\]/u', '<select name="rappinn[$1]"><option>Velg:</option><option>Ja</option><option>Nei</option></select>');
			$tplErstatter->addErstattning('/\[\[inputtekst:([A-Za-z]+)\]\]/u', '<input type="text" maxlength="250" name="rappinn[$1]">');
			$tplErstatter->addErstattning('/\[\[inputlitetall:([A-Za-z]+)\]\]/u', '<input type="text" maxlength="3" size="2" name="rappinn[$1]">');
			$tplErstatter->addErstattning('/\[\[data:([A-Za-z]+)\]\]/ue', '$tpldata[\'$1\']');			
			$tplErstatter->addErstattning('/\[\[ulogget\]\]/u', $uloggetoutput);
				
			
			$output .= '<form name="velgskift" action="' . MS_FMR_LINK . '" method="POST">';
			$output .= '<input type="hidden" name="act" value="genrapport" />';
			
			$output .= preg_replace($tplErstatter->getPatterns(), $tplErstatter->getReplacements(), RapportTemplate::getRawTemplate());
			
			$output .= '<input type="submit" class="button" name="genrap" value="Generer rapport" DISABLED />';
			$output .= '<input type="submit" class="button" name="genrap" value="Nullstill" DISABLED />';
			$output .= '<input type="submit" class="button" name="genrap" value="Tilbake" DISABLED />';
			$output .= '</form>';
			
			
			return $output;
			
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
			$output .= '<input type="checkbox" name="selskift[]" value="' . $objSkift->getId() . '"' . (($objSkift->isClosed()) ? '' : 'disabled') . ' />';
			$output .= $objSkift . '(' . $objSkift->getSkiftOwnerName() . ') ';
			$output .= '(' . (($objSkift->isClosed()) ? 'avsluttet' : '<a href="' . MS_FMR_LINK . '&act=stengskift&skiftid=' . $objSkift->getId() . '">steng</a>') . ")<br />\n";
		}			
					
		$output .=	'
					<input type="submit" name="subvelgskift" class="button" value="Gå videre">
				</form>
			</fieldset>
			</div>';
		
		
		
		return $output;
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
		
		$skiftout .= 'Output fra genSkift: ' . $objSkift . '<br />';
		
		$skiftout .= '<p>Skift opprettet: ' . $objSkift->getSkiftCreatedTime() . '</p>';
		
		
		// Vis notater
		
		$skiftout .= '<fieldset style="float:right;text-align:left;"><legend>Notater</legend><ul>';
		
		foreach($objSkift->notater as $objNotat) {
			if ($objNotat->isActive()) $skiftout .= $this->genNotat($objNotat);
		}
		if (($this->_msmodulact == 'modnotat') && isset($_REQUEST['notatid'])) {
			$objNotat = $objSkift->notater->getItem($_REQUEST['notatid']);
			$skiftout .= $this->genNotat($objNotat, true);
		} else {
			$skiftout .= $this->genNotat(null, true);
		}
		$skiftout .= '</ul></fieldset>';
		
		// Vis tellere
		
		$colUlogget = new TellerCollection();
		$arUlogget = array();
		
		$colSecTeller = new TellerCollection();
		$arSecTeller = array();
		
		$skiftout .= '<table>';	
		foreach($objSkift->tellere as $objTeller) {
			switch ($objTeller->getTellerType()) {
				case 'TELLER':
					$skiftout .= '<tr>';
					$skiftout .= '<form action="' . MS_FMR_LINK . '" method="POST">';
					$skiftout .= '<td>' . $objTeller->getTellerDesc() . ':</td><td>' . $objTeller->getTellerVerdi() . '</td>';
					$skiftout .= '<input type="hidden" name="act" value="mod_teller" />';
					$skiftout .= '<input type="hidden" name="tellerid" value="' . $objTeller->getId() . '" />';
					$skiftout .= '<td><div class="inc_dec"><input type="submit" class="button" name="inc_teller" value="+" /><input type="submit" class="button" name="dec_teller" value="-" /></div></td>';
					$skiftout .= '</form>';
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
			$skiftout .= '<tr>';
			$skiftout .= '<form action="' . MS_FMR_LINK . '" method="POST">';
			$skiftout .= '<input type="hidden" name="act" value="mod_teller" />';
			$skiftout .= '<td><select name="tellerid">';
			$skiftout .= '<option value="NOSEL">Annet: </option>';
			foreach ($arSecTeller as $tellerid => $tellerdesc) {
				$skiftout .= '<option value="' . $tellerid . '">' . $tellerdesc . '</option>';
			}
			$skiftout .= '</select></td><td></td>';
			$skiftout .= '<td><div class="inc_dec"><input type="submit" class="button" name="inc_teller" value="+" /><input type="submit" class="button" name="dec_teller" value="-" /></div></td>';
			$skiftout .= '</form>';
			$skiftout .= "</tr>\n\n";
		}		
		
		if (!empty($arUlogget)){
			$skiftout .= '<tr>';
			$skiftout .= '<form action="' . MS_FMR_LINK . '" method="POST">';
			$skiftout .= '<input type="hidden" name="act" value="mod_teller" />';
			$skiftout .= '<td><select name="tellerid">';
			$skiftout .= '<option value="NOSEL">Ulogget: </option>';
			foreach ($arUlogget as $tellerid => $tellerdesc) {
				$skiftout .= '<option value="' . $tellerid . '">' . $tellerdesc . '</option>';
			}
			$skiftout .= '</select></td><td></td>';
			$skiftout .= '<td><div class="inc_dec"><input type="submit" class="button" name="inc_teller" value="+" /><input type="submit" class="button" name="dec_teller" value="-" /></div></td>';
			$skiftout .= '</form>';
			$skiftout .= "</tr>\n\n";
		}
		
		$skiftout .= '</table><br /><br />';

		if ($colSecTeller->length() > 0) {
			$skiftout .= '<p>';
			$skiftout .= '<strong>Annet:</strong><br />';
			
			foreach ($colSecTeller as $objTeller) {
				$skiftout .= $objTeller . '<br />';
			}
			$skiftout .= '</p>';
		}
		
		if ($colUlogget->length() > 0) {
			$skiftout .= '<p>';
			$skiftout .= '<strong>Uloggede samtaler:</strong><br />';
			
			foreach ($colUlogget as $objTeller) {
				$skiftout .= $objTeller . '<br />';
			}
			$skiftout .= '</p>';
		}

		// Close skift knapp
		$skiftout .= '<form method="post" action="' . MS_FMR_LINK . '">';
		$skiftout .= '<input type="hidden" name="act" value="stengegetskift" />';
		$skiftout .= '<input type="submit" class="button" value="Avslutt skift" />';
		$skiftout .= '</form>';
		
		$skiftout .= '</div>'; // skift_full
		
		
		return $skiftout;
	
	}
	
	private function _genTellerAdm() {
		$output .= 'Teller-administrasjon ikke implementert enda :(';
	
		return $output;
	}
	
	public function getCurrentSkiftId($userid = false) {
		if ($userid === false) $userid = $this->_userId;
		if (isset($this->_currentSkiftId) && $userid == $this->_userId) return $this->_currentSkiftId;
		
		global $msdb;
		$userid = $msdb->quote($userid);

		$result = $msdb->num("SELECT skiftid FROM feilrap_skift WHERE israpportert='0' AND skiftclosed IS NULL AND userid=$userid ORDER BY skiftid DESC LIMIT 1;");
	
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
		$output .= '<ul>';
		$output .= '<li>Du har avsluttet skiftet ditt</li>';
		$output .= '<li>Skiftet ditt har blitt inkludert i en rapport</li>';
		//$output .= '<li>Skiftet ditt har ikke blitt oppdatert på over 9 timer og er utløpt</li>'; // Ikke implementert
		$output .= '</ul><br/>';
		$output .= '<form method="post" action="' . MS_FMR_LINK . '">';
		$output .= '<input type="hidden" name="act" value="nyttskift" />';
		$output .= '<input type="submit" value="Start nytt skift!" />';
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
			RapportTemplate::saveTemplate($_REQUEST['inputtpl'], 1);
		}
	}
	
	private function _genModRapportTemplates() {
	
		$output .= '<form action="' . MS_FMR_LINK . '" method="POST">';
		$output .= '<input type="hidden" name="act" value="modraptpl" />';
		$output .= '<textarea name="inputtpl" cols="60" rows="40" wrap="off">' . RapportTemplate::getRawTemplate() . '</textarea>';
		$output .= '<input type="submit" class="button" name="savetpl" value="Lagre" />';
		$output .= '</form>';
	
	
		return $output;
	}
	
	

	public function registrer_meny(MenyitemCollection &$meny){
		$lvl = $this->_accessLvl;
		
		$toppmeny = new Menyitem('FeilM Rapport','&page=feilmrapport');
		$telleradmin = new Menyitem('Rediger tellere','&page=feilmrapport&act=telleradm');
		$genrapport = new Menyitem('Lag rapport','&page=feilmrapport&act=genrapportsel');
		$tpladmin = new Menyitem('Rediger rapport-templates','&page=feilmrapport&act=genmodraptpl');
		
		if ($lvl > MSAUTH_NONE) { 
			if (($lvl >= MSAUTH_3) && isset($this->_msmodulact)) {
				$toppmeny->addChild($genrapport);
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


