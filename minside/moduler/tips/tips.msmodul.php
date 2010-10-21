<?php
if(!defined('MS_INC')) die();

class msmodul_tips implements msmodul{

	private $_msmodulAct;
	private $_msmodulVars;
	private $_userID;
	private $_adgang;
	private $_cfg=array();
	
	
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
		$epostadr = $_POST['admepost'];
		$settepost = $_POST['lagreepost'];		

		switch ($this->_msmodulAct) { 
			case 'sendtips':
			$dato = date("d/m/Y H:i");
			$tipsbody = 'Sendt via WikiTips '. $dato . '.<br><br>'. $tipsbody.'<br><br>Sendt av: '.$INFO['userinfo']['name'];
			$this->_tipsOut .= $tipsbody;
			try {
				if ($this->send_mail($tipsbody)) {
					return $this->_tipsOut;
				}
			}
			catch (Exeption $e) {
				die ($e->getMessage());
			}
			break;
			case 'tipsadmshw':
				if (isset($settepost)) {
					$this->setSettings($epostadr);
				}
					$this->getSettings();
					$this->_tipsOut .= '
					<fieldset style="width:350px;"><legend>WikiTips Admin</legend>
					<form action"'. MS_LINK . '"&page=tips" method="POST">
						<textarea name="admepost" class="edit">';
					$this->_tipsOut .= $this->_cfg['epost'];
					$this->_tipsOut .= '</textarea>
					<input type="hidden" name="act" value="tipsadmshw">
						<input type="submit" name="lagreepost" class="button" value="Lagre">
					</form>
					</fieldset>
					';
			break;
			default:
				$this->_tipsOut .= '
					<fieldset style="width:250px;"><legend>Tips til wiki</legend>
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
		$this->getSettings();
		mail($this->_cfg['epost'],'WikiTips', $tipstext, 'From: wikitips@igrudge.net');	
	}
	
	public function getSettings(){
		global $msdb;
		$sql = 'SELECT setting, value FROM wikitips';
		$resultat = $msdb->assoc($sql);
		foreach ($resultat as $cfg){
			$this->_cfg[$cfg['setting']]=$cfg['value'];
		}
	}
	public function setSettings($epostadr){
		global $msdb;
		$sql = 'UPDATE wikitips SET value="'.$epostadr.'" WHERE setting="epost"';
		$msdb->exec($sql);
		$this->_tipsOut .= 'Lagret';
	}
	
	public function registrer_meny(MenyitemCollection &$meny){
		$lvl = $this->_adgang;
        
        $menynavn = 'WikiTips';
        $adminnavn = 'Admin';
        if ($this->_msmodulAct == 'tipsadmshw') {
            $adminnavn = '<span class="selected">'.$adminnavn.'</span>';
        } elseif (isset($this->_msmodulAct)) {
            $menynavn = '<span class="selected">'.$menynavn.'</span>';
        }
        
		$toppmeny = new Menyitem($menynavn,'&page=tips');
		$tipsadmin = new Menyitem($adminnavn,'&page=tips&act=tipsadmshw');
		
		if ($lvl > MSAUTH_NONE) { 
			if (($lvl == MSAUTH_ADMIN) && isset($this->_msmodulAct)) {
				$toppmeny->addChild($tipsadmin);
			}
			$meny->addItem($toppmeny); 
		}
	}
	
	
}
