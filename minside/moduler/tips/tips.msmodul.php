<?php
if(!defined('MS_INC')) die();

class msmodul_tips implements msmodul{

	private $_msmodulAct;
	private $_msmodulVars;
	private $_userID;
	private $_adgang;
	
	
	public function __construct($UserID, $adgang) {
	
		$this->_userID = $UserID;
		$this->_adgang = $adgang;
	}
	
	public function gen_msmodul($act, $vars){
		global $INFO;
		$this->_msmodulAct = $act;
		$this->_msmodulVars = $vars;
		$tipsknapp = $_POST['tipsknapp'];
		$tipsbody = $_POST['tipsbody'];		

		if ($this->_msmodulAct == 'sendtips') { 
			$dato = date("d/m/Y H:i");
			$tipsbody = 'Sendt via WikiTips '. $dato . '.<br><br>'. $tipsbody;
			$this->_tipsOut .= $tipsbody;
			try {
				if (send_mail($tipsbody)) {
					return $this->_tipsOut;
				}
			}
			catch (Exeption $e) {
				die ($e->getMessage());
			}		
		}
		
		else {
			$this->_tipsOut .= '
				<fieldset style="width:250px;"><label>Tips til wiki</label>
				<form action="' . MS_LINK . '&page=tips" method="POST">
					<textarea name="tipsbody" class="edit" style="width:245px;"></textarea>
					<br>
					<input type="hidden" name="act" value="sendtips">
					<input type="submit" name="tipsknapp" class="button" value="Send">
				</form>
				</fieldset>';
		}
		return $this->_tipsOut;
	}
	
	public function send_mail($tipstext){
		echo mail('njal.kollbotn@lyse.net','WikiTips', $tipstext);	
	}
	
	public function registrer_meny(MenyitemCollection &$meny){
		if ($this->_adgang > MSAUTH_NONE) { $meny->addItem(new Menyitem('WikiTips','&page=tips')); }
	}
	
	
}
