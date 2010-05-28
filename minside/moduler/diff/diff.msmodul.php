<?php
if(!defined('MS_INC')) die();
include_once "Text/Diff.php";
include_once "Text/Diff/Renderer.php";
include_once "Text/Diff/Renderer/inline.php";
if (defined('DOKU_INC')) require_once(DOKU_INC.'inc/DifferenceEngine.php');

class msmodul_diff implements msmodul{

	private $_msmodulAct;
	private $_msmodulVars;
	private $_diffOut;
	private $_userID;
	private $_adgang;
	
	public function __construct($UserID, $adgang) {
	
		$this->_userID = $UserID;
		$this->_adgang = $adgang;
	}
	
	public function gen_msmodul($act, $vars){
		$this->_msmodulAct = $act;
		$this->_msmodulVars = $vars;

		$this->_diffOut = 'Dette er output fra Diff! UserId er: '. $this->_userID . ' act er: ' . $this->_msmodulAct . '<br /><br />';
		
		if ($this->_msmodulAct == 'dispdiff') {
			$text1 = explode ("\n", $_POST['difftext1']);
			$text2 = explode ("\n", $_POST['difftext2']);
			
			if (defined('DOKU_INC') && ($_POST['diffver'] == 'dokuwiki')) {
				$df = new Diff($text1,$text2);
				$tdf = new TableDiffFormatter();
			
				$this->_diffOut .= '<table class="diff">';
				$this->_diffOut .= $tdf->format($df);
				$this->_diffOut .= '</table>';
			} else {
				$diff = &new Text_Diff($text1, $text2);
				$renderer = &new Text_Diff_Renderer_inline();
				$this->_diffOut .= '<pre>' . $renderer->render($diff) . '</pre>';
			}
			
			$this->_diffOut .= '<form action="' . MS_LINK . '&page=diff" method="POST">';
			$this->_diffOut .= '<textarea name="difftext1" rows="20" cols="44">';
			$this->_diffOut .= implode($text1);
			$this->_diffOut .= '</textarea>';
			$this->_diffOut .= '<textarea name="difftext2" rows="20" cols="44">';
			$this->_diffOut .= implode($text2);
			$this->_diffOut .= '</textarea>';
		} else {
			$this->_diffOut .= '<form action="' . MS_LINK . '&page=diff" method="POST">';
			$this->_diffOut .= '<textarea name="difftext1" rows="20" cols="40"></textarea>';
			$this->_diffOut .= '<textarea name="difftext2" rows="20" cols="40"></textarea>';
		}
		
		$this->_diffOut .= '<input type="hidden" name="act" value="dispdiff" />';
		$this->_diffOut .= '<br><input type="submit" value="Sjekk diff" class="button" />';
		if (defined('DOKU_INC')) {
			$this->_diffOut .= 'Visningsmodus: <input type="radio" ' . (($_POST['diffver'] == 'pear') ? '' : 'checked="checked"') . ' name="diffver" value="dokuwiki" />DokuWiki';
			$this->_diffOut .= '<input type="radio" ' . (($_POST['diffver'] == 'pear') ? 'checked="checked"' : '')  . ' name="diffver" value="pear" />PEAR';
		}
		$this->_diffOut .= '</form>';
		return $this->_diffOut;
	
	}
	
	public function registrer_meny(MenyitemCollection &$meny){
		if ($this->_adgang > MSAUTH_NONE) { $meny->addItem(new Menyitem('Diff','&page=diff')); }
	}
	
	
}
