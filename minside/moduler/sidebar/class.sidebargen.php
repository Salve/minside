<?php
if(!defined('MS_INC')) die();

class SidebarGen {
	public static function GenSidebar(MenyitemCollection $objSidebar, $adgang) {
        
        if ($objSidebar->length() === 0) {
            return self::GenEmptySidebar($adgang);
        }
        
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
		$output .= '<form action="' . MS_LINK . '&page=sidebar&act=InsOrMov" method="POST">';
		$output .= '<div class="sidebaradmin_left" style="float: left;">';
		
		if ($objSidebar->length() === 0) {
			$output .= '<div class="mswarningbar">Ingenting her!</div>';
		}
		
		$output .= '<div class="left_sidebar">' .
				   '<table class="sidebaradm">';
		
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
		$output .= '<div class="label">Tekst: </div><input type="text" name="addtekst" class="edit"><br />';
		$output .= '<div class="label">URL: </div><input type="text" name="addhref" class="edit"><br />';
		$output .= '<div class="label">ACL: </div><input type="text" name="addacl" class="edit"><br />';
		$output .= '<input type="submit" name="addaction" value="Lag overskrift" class="msbutton"><br />';
		$output .= '<input type="submit" name="addaction" value="Lag vanlig lenke" class="msbutton"><br />';
		$output .= '<br /><p><strong>Legg til spesialblokker</strong></p>';
		$output .= '<input type="submit" name="addaction" value="Spacer" class="msbutton"><br />';
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
		$acl = $objMenyitem->getAcl();
		
		if (empty($acl)) { 
			$acl = 'Ingen ACL satt, alle kan se denne.';
		} else {
			$acl = 'Blokk kun synlig for brukere med lesetilgang til: ' . $acl;
		}
		
		if (!empty($href)) {
			$menyitem = "<span title=\"$acl\"><a href=\"$href\" class=\"menu_item\">$tekst</a></span>";
		} else {
			$menyitem = $tekst;
		}
		
		$opt['move'] = '<input type="image" src="'.MS_IMG_PATH.'insert.gif" width=16 height=16 alt="flytt" ' .
					'title="Flytt blokk til valgt posisjon" name="movblokkid" value="' . $objMenyitem->getId() . '">';
		$opt['trash'] = '<a href="'. MS_LINK.'&page=sidebar&act=rem&blokkid='. $objMenyitem->getId() .
					'"><img src="'.MS_IMG_PATH.'trash.png" width=16 height=16 alt="slett" title="Slett blokk"></a>';
		
		switch ($objMenyitem->getType()) {
			case Menyitem::TYPE_HEADER:
				$blokk = '<td class="menu_title" align="left">' . $menyitem . '</td>';
				break;
			case Menyitem::TYPE_NORMAL:
				$blokk = "<td> $menyitem </td>";
				break;
			case Menyitem::TYPE_SPACER:
				$blokk = "<td><span title=\"$acl\">SPACER</span></td>";
				break;
			case Menyitem::TYPE_MSTOC:
				$blokk = '<td class="menu_title" align="left">MinSide meny her</td>';
				break;
			default:
				throw new Exception('Ukjent menyitem-type: ' . $objMenyitem->getType());
		}
		
		$output = '<tr>';
		$output .= '<td><input type="radio" name="targetblokkid" value="' . 
			$objMenyitem->getId() . '" /></td>';
		$output .= '<td>' . implode($opt) . '</td>';
		$output .= $blokk;
		$output .= '</tr>';
		
		return $output;		
	}
    
	protected static function GenEmptySidebar($adgang) {
        $colDefaultSidebar = new MenyitemCollection();
        
        $objInfoItem = new Menyitem(
			'Ingen meny satt opp!',
			'doku.php',
			'',
			Menyitem::TYPE_NORMAL
		);
        
        $colDefaultSidebar->additem($objInfoItem);
        
        if ($adgang == MSAUTH_ADMIN){
            $objSpacer = new Menyitem('Spacer', '', '', Menyitem::TYPE_SPACER);
            $objAdmin = new Menyitem(
                'Administrer sidebar',
                'doku.php?do=minside&page=sidebar&act=admin',
                '',
                Menyitem::TYPE_NORMAL
            );
            $colDefaultSidebar->additem($objSpacer);
            $colDefaultSidebar->additem($objAdmin);
        }
        
        return self::GenSidebar($colDefaultSidebar, $adgang);
    }
	
}
