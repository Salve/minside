<?php
if(!defined('MS_INC')) die();

class msmodul_nyhetgen implements msmodul{

	private $_msmodulAct;
	private $_msmodulVars;
	private $_userID;
	private $_adgang;
	
	
	public function __construct($UserID, $adgang) {
	
		$this->_userID = $UserID;
		$this->_adgang = $adgang;
	}
	
	public function gen_msmodul($act, $vars){
		$this->_msmodulAct = $act;
		$this->_msmodulVars = $vars;
		global $INFO;
		$submit = $_POST['sendnyhet'];
		$reset = $_POST['reset'];		
	if (isset($submit)) { 

		$dato = date("d/m/Y H:i");
		$overskrift = $_POST['overskrift'];
		$bread = $_POST['ingress'];
		$tekst = $_POST['tekst'];
		$signatur = '---//[['.$INFO['userinfo']['mail'].'|'.$INFO['userinfo']['name'].']] '.$dato.' // ';

		$this->_nyhetgenOut .= nl2br('&lt;hidden initialState="visible" onHidden="**### '.$overskrift.' ###** '.$signatur.'\\\ **'.$bread.'**" onVisible="**### '.$overskrift.' ###**\\\ **'.$bread.'**"&gt;'."\n\n".$tekst.' \\\ '."\n\n\n\n".$signatur."\n\n".'&lt;/hidden&gt;'."\n\n".'\\\ '."\n\n");
		$this->_nyhetgenOut .= '
			<fieldset style="width: 600px;">
				<legend>
					Nyhetsgenerator
				</legend>
				<form name="nyhetsgenerator" action="' . MS_LINK . '&page=nyhetgen" method="POST">
					Overskrift: <input type="text" name="overskrift" value="'.$overskrift.'"><br>
					Ingress: <br><textarea name="ingress" cols="50" class="edit">'.$bread.'</textarea><br>
					Tekst: <br><textarea name="tekst" class="edit" cols="50" rows="20">'.$tekst.'</textarea><br>
					<input type="submit" name="sendnyhet" class="button" value="Generer nyhet">
					<input type="submit" name="reset" class="button" value="Nullstill">
				</form>
			</fieldset>';
	}
	else if (isset($reset)) {
		$this->_nyhetgenOut = '
			<fieldset style="width: 600px; text-align: left;">
				<legend>
					Nyhetsgenerator
				</legend>
				<form name="nyhetsgenerator" action="' . MS_LINK . '&page=nyhetgen" method="POST">
					Overskrift: <br><input type="text" class="edit" name="overskrift" size="60"><br>
					Ingress: <br><textarea name="ingress" style="width: 550px;" cols="50" class="edit"></textarea><br>
					Tekst: <br><textarea name="tekst" style="width: 550px" cols="50" class="edit" rows="20"></textarea><br>
					<input type="submit" class="button" name="sendnyhet" value="Generer nyhet">
					<input type="reset" class="button" value="Nullstill">
				</form>
			</fieldset>
			';
	}
	else {
	
		$this->_nyhetgenOut = '
			<fieldset style="width: 600px; text-align: left;">
				<legend>
					Nyhetsgenerator
				</legend>
				<form name="nyhetsgenerator" action="' . MS_LINK . '&page=nyhetgen" method="POST">
					Overskrift: <br><input type="text" class="edit" name="overskrift" size="60"><br>
					Ingress: <br><textarea name="ingress" style="width: 550px;" cols="50" class="edit"></textarea><br>
					Tekst: <br><textarea name="tekst" style="width: 550px" cols="50" class="edit" rows="20"></textarea><br>
					<input type="submit" class="button" name="sendnyhet" value="Generer nyhet">
					<input type="reset" class="button" value="Nullstill">
				</form>
			</fieldset>
			';
	}

		return $this->_nyhetgenOut;
	
	}
	
	
	public function registrer_meny(MenyitemCollection &$meny){
		if ($this->_adgang > MSAUTH_NONE) { $meny->addItem(new Menyitem('Nyhetsgenerator','&page=nyhetgen')); }
	}
	
	
}
