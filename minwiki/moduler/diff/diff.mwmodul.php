<?php
if(!defined('MW_INC')) die();
include_once "Text/Diff.php";
include_once "Text/Diff/Renderer.php";
include_once "Text/Diff/Renderer/inline.php";

class mwmodul_diff implements mwmodul{

	private $_mwmodulAct;
	private $_mwmodulVars;
	private $_diffOut;
	private $_userID;
	private $_adgang;
	
	public function __construct($UserID, $adgang) {
	
		$this->_userID = $UserID;
		$this->_adgang = $adgang;
	}
	
	public function gen_mwmodul($act, $vars){
		$this->_mwmodulAct = $act;
		$this->_mwmodulVars = $vars;

		/*
		if (isset($_POST['submit'])) {
			$text1 = explode ("\n", $_POST['difftext1']);
			$text2 = explode ("\n", $_POST['difftext2']);
			$diff = &new Text_Diff($text1, $text2);
			$renderer = &new Text_Diff_Renderer_inline();
			echo "<pre>".$renderer->render($diff)."</pre>";
			echo "<form action='saker.php?view=diff' method='POST'><textarea name='text1' rows='20' cols='40'>".implode($text1)."</textarea><textarea name='text2' rows='20' cols='40'>".implode($text2)."</textarea><br><input type='submit' name='submit'></form>";	
		} else {
			echo "<form action='saker.php?view=diff' method='POST'><textarea name='text1' rows='20' cols='40'></textarea><textarea name='text2' rows='20' cols='40'></textarea><br><input type='submit' name='submit'></form>";
		}
		
		*/


	return $this->_diffOut;
	
	}
	
	public function registrer_meny(MenyitemCollection &$meny){
		if ($this->_adgang > MWAUTH_NONE) { $meny->addItem(new Menyitem('Diff','&page=diff')); }
	}
	
	
}
