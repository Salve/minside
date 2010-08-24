<?php
if(!defined('MS_INC')) die();

class SidebarGen {
	public static function GenSidebar(MenyitemCollection $objSidebar) {
		$output .= '<div class="left_sidebar">' .
				   '<TABLE cellspacing=4>';
		
		foreach ($objSidebar as $objMenyitem) {
			if ($objMenyitem->checkAcl() === true) {
				$output .= self::GenMenyitem($objMenyitem);
			}
		}
		
		$output .= '</TABLE>' .
				   '</div>  <!-- end left_sidebar-->';
		
		return $output;
	
	}
	
	
	private static function GenMenyitem(Menyitem $objMenyitem) {
		
		$href = $objMenyitem->getHref();
		$tekst = $objMenyitem->getTekst();
		
		if (!empty($href)) {
			$menyitem = "<a href=\"$href\" class=\"menu_item\">$tekst</a>";
		} else {
			$menyitem = $tekst;
		}
		
		switch ($objMenyitem->getType()) {
			case Menyitem::TYPE_HEADER:
				$output = '<tr><td class="menu_title" align="left">' .
						  $menyitem .
						  '</td></tr>' . "\n";
				break;
			case Menyitem::TYPE_NORMAL:
				$output = '<tr><td>' .
						  $menyitem .
						  '</td></tr>';
				break;
			case Menyitem::TYPE_SPACER:
				$output = "<tr><td>&nbsp;</td></tr>\n";
				break;
			case Menyitem::TYPE_MSTOC:
				$objMinSide = MinSide::getInstance();
				return '<tr><td>' . $objMinSide->getMeny() . '</td></tr>';
				break;
			default:
				throw new Exception('Ukjent menyitem-type: ' . $objMenyitem->getType());
		}
		
		return $output;		
	}
	
	public static function GenAdmin(MenyitemCollection $objSidebar) {
		
		$output .= '<div class="sidebaradmin">';
		$output .= '<form action="' . MS_LINK . '&page=sidebar&act=add" method="POST">';
		$output .= '<div class="sidebaradmin_left" style="float: left;">';
		
		$output .= '<div class="left_sidebar">' .
				   '<table cellspacing=4>';
		
		foreach ($objSidebar as $objMenyitem) {
			if ($objMenyitem->checkAcl() === true) {
				$output .= self::GenMenyitemAdmin($objMenyitem);
			}
		}
		
		$output .= '</table>' .
				   '</div>  <!-- end left_sidebar-->';
		$output .= '</div> <!-- end sidebaradmin_left -->';
		
		$output .= '<div class="sidebaradmin_right" style="float: right;">';
		$output .= '<p><strong>Legg til menyelementer</strong></p>';
		$output .= '<input type="submit" name="addaction" value="Overskrift" class="msbutton"><br />';
		$output .= '<input type="submit" name="addaction" value="Vanlig lenke" class="msbutton"><br />';
		$output .= '<input type="submit" name="addaction" value="Spacer" class="msbutton"><br />';
		$output .= '<p><strong>Legg til spesialblokker</strong></p>';
		$output .= '<input type="submit" name="addaction" value="MinSide meny" class="msbutton"><br />';
		$output .= '</div> <!-- end sidebaradmin_right -->';
		$output .= '<div class="msclearer"></div>';
		$output .= '</form>';
		$output .= '</div> <!-- end sidebaradmin -->';
		
		return $output;
	}
	
	private static function GenMenyitemAdmin(Menyitem $objMenyitem) {
		
		$href = $objMenyitem->getHref();
		$tekst = $objMenyitem->getTekst();
		
		if (!empty($href)) {
			$menyitem = "<a href=\"$href\" class=\"menu_item\">$tekst</a>";
		} else {
			$menyitem = $tekst;
		}
		
		switch ($objMenyitem->getType()) {
			case Menyitem::TYPE_HEADER:
				$output = '<tr><td class="menu_title" align="left">' .
						  $menyitem .
						  '</td></tr>' . "\n";
				break;
			case Menyitem::TYPE_NORMAL:
				$output = '<tr><td>' .
						  $menyitem .
						  '</td></tr>';
				break;
			case Menyitem::TYPE_SPACER:
				$output = "<tr><td>SPACER</td></tr>\n";
				break;
			case Menyitem::TYPE_MSTOC:
				return '<tr><td class="menu_title" align="left">' . 
					   'MinSide meny her' . 
					   '</td></tr>';
				break;
			default:
				throw new Exception('Ukjent menyitem-type: ' . $objMenyitem->getType());
		}
		
		return $output;		
	}
	
	
}
