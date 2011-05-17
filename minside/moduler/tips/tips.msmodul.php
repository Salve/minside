<?php
if(!defined('MS_INC')) die();

class msmodul_tips implements msmodul{

	private $_msmodulAct;
	private $_msmodulVars;
	private $_userID;
	private $_adgang;
	private $_cfg=array();
	private $_cfgLoaded = false;
	
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
		$epostadr = $_POST['epost'];
		$settepost = $_POST['lagreepost'];
		$editrowid = $_GET['tipsrowid'];	
		$newrow = $_POST['newrow'];
		$kategori = $_POST['kategori'];
		$setnewrow = $_POST['insertnew'];
		$selectkat =$_POST['selectkat'];
		$delrowid = $_GET['delrowid'];

		switch ($this->_msmodulAct) { 
			case 'sendtips':
			$whosend = $this->getOneSetting($selectkat);
			$eposttil = $whosend['value'];
			$dato = date("d/m/Y H:i");
			$tipsbody = 'Sendt via WikiTips '. $dato . '.<br><br>'. $tipsbody.'<br><br>Sendt av: '.$INFO['userinfo']['name'];
			$this->_tipsOut .= $tipsbody;
			try {
				if ($this->send_mail($tipsbody, $eposttil)) {
					return $this->_tipsOut;
				}
			}
			catch (Exeption $e) {
				die ($e->getMessage());
			}
			break;
			
			case 'tipsadmshw':
			$lvl = $this->_adgang;
			if ($lvl == MSAUTH_ADMIN) {
				if (isset($settepost)) {
					if (isset($setnewrow)){
					    $this->newSetting($kategori, $epostadr);
					}
					else {
					    $this->setSettings($editrowid,$kategori, $epostadr);
					}
				}
				if (isset($delrowid)) $this->delSetting($delrowid);
				$this->getSettings();
                $this->_tipsOut .= '
					<fieldset style=""><legend>WikiTips Admin</legend>
					<form action"'. MS_LINK . '"&amp;page=tips" method="POST"><table class="inline" style="width:100%"><tr><th>Kategori</th><th>E-post</th><th>Slett</th></tr>';
			    foreach ($this->_cfg as $test) {
			        $this->_tipsOut .= '<tr><td><a href="'.MS_LINK.'&amp;page=tips&amp;act=tipsadmshw&amp;tipsrowid='.$test['id'].'">'.$test['setting'].'</a></td><td>' .$test['value'].'</td><td><a href="'.MS_LINK.'&amp;page=tips&amp;act=tipsadmshw&amp;delrowid='.$test['id'].'"><img src="'.MS_IMG_PATH.'trash.png"></a></td></tr>';
			    }
					$this->_tipsOut .= '</table>
					<input type="hidden" name="act" value="tipsadmshw">';
					if (!isset($editrowid) || !isset($newrow)) $this->_tipsOut .= '<input type="submit" name="newrow" class="button" value="Ny">';					    
					if (isset($editrowid) || isset($newrow)) {
					    $this->_tipsOut .= '<input type="text" class="edit" style="width:30%" name="kategori" value="';
					    if(isset($editrowid)){ 
                            $test = $this->getOneSetting($editrowid);
                            $this->_tipsOut .= $test['setting'];
					    }
					    $this->_tipsOut .= '"><input type="text" class="edit" style="width:30%" name="epost" value="';
					    if(isset($editrowid)) $this->_tipsOut .= $test['value'];
					    $this->_tipsOut .= '">';
					    if(isset($newrow)) $this->_tipsOut .= '<input type="hidden" name="insertnew" value="true">';
						$this->_tipsOut .= '<input type="submit" name="lagreepost" class="button" value="Lagre">';
					}
					$this->_tipsOut .= '</form>
					</fieldset>
					';
				}
				else $this->_tipsOut .= 'Ingen adgang';
			break;

			default:
				$this->_tipsOut .= '
					<fieldset style="width:400px;"><legend>Tips til wiki</legend>
					<form action="' . MS_LINK . '&amp;page=tips" method="POST">
					    <pre>Kategori for henvendelse: <select name="selectkat" class="edit">';
			    $this->getSettings();
			    foreach ($this->_cfg as $test){
			        $this->_tipsOut .= '<option value="'.$test['id'].'">'.$test['setting'].'</option>';
			    }
				$this->_tipsOut .= '</select></pre>
						<textarea name="tipsbody" class="edit" style="width:400spx;"></textarea>
						<br>
						<input type="hidden" name="act" value="sendtips">
						<input type="submit" name="tipsknapp" class="button" value="Send">
					</form>
					</fieldset>';
			}
		return $this->_tipsOut;
	}
	
	public function send_mail($tipstext, $tipsemail){
		mail($tipsemail,'WikiTips', $tipstext, 'From: wikitips@igrudge.net');	
	}
	
	public function getSettings(){
		global $msdb;
		$sql = 'SELECT id, setting, value FROM wikitips';
		$resultat = $msdb->assoc($sql);
		foreach ($resultat as $datum){
			$this->_cfg[$datum['id']] = $datum;
		}
		$this->_cfgLoaded = true;
	}
	
	public function getOneSetting($rowid){
	    if (!$this->_cfgLoaded) $this->getSettings();
	    return $this->_cfg[$rowid];
	}
	public function setSettings($rowid, $kat, $val){
		global $msdb;
		$sql = 'UPDATE wikitips SET setting="'.$kat.'", value="'.$val.'" WHERE id="'.$rowid.'"';
		$msdb->exec($sql);
		$this->_tipsOut .= 'Lagret';
	}
	
	public function delSetting($delrow) {
	    global $msdb;
	    $sql = 'DELETE FROM wikitips WHERE id='.$delrow.';';
	    $msdb->exec($sql);
	    $this->_tipsOut .= 'Slettet';
	}
	
	public function newSetting($kat,$val) {
	    global $msdb;
	    $sql ='INSERT INTO wikitips (setting, value)VALUES ("'.$kat.'", "'.$val.'");';
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
		$toppmeny = new Menyitem($menynavn,'&amp;page=tips');
		$tipsadmin = new Menyitem($adminnavn,'&amp;page=tips&amp;act=tipsadmshw');
		if ($lvl > MSAUTH_NONE) { 
			if (($lvl == MSAUTH_ADMIN) && isset($this->_msmodulAct)) {
				$toppmeny->addChild($tipsadmin);
			}
			$meny->addItem($toppmeny); 
		}
	}
}
