<?php
if(!defined('MS_INC')) die();

class Bruker {
    
    protected $_id;
    protected $_navn;
    protected $_fullnavn;
    protected $_epost;
    protected $_groups = array();
    protected $_createtime;
    protected $_isactive;
    
    public function __construct($id, $navn, $fullnavn, $groups, $epost, $isactive, $createtime) {
        $this->_id = $id;
        $this->_navn = $navn;
        $this->_fullnavn = $fullnavn;
        $this->_epost = $epost;
        $this->_isactive = (bool) $isactive;
        $this->_createtime = strtotime($createtime);
        
        if(is_array($groups)) {
            $this->_groups = $groups;
        } else {
            $this->_groups = explode(',', $groups);
        }
        
    }

    public function getId() {
        return $this->_id;
    }
    public function getNavn() {
        return $this->_navn;
    }
    
    public function getFullNavn() {
        return $this->_fullnavn;
    }
    
    public function getEpost() {
        return $this->_epost;
    }
    
    public function getGroups() {
        return $this->_groups;
    }
    
    public function getCreatetime() {
        return $this->_createtime;
    }
    
    public function isActive() {
        return $this->_isactive;
    }
    
    public static function compare_alpha_navn(Bruker $a, Bruker $b) {
        $navnA = strtoupper($a->getFullNavn());
        $navnB = strtoupper($b->getFullNavn());
        if($navnA == $navnB) return 0;
        return ($navnA > $navnB) ? +1 : -1;
    }
    
    public static function getAlleBrukere() {
        $arBrukere = MinSide::getUsers();
        $colBrukere = new BrukerCollection();
        
        foreach($arBrukere as $bruker) {
            $objBruker = new Bruker(
                $bruker['id'], 
                $bruker['wikiname'], 
                $bruker['wikifullname'], 
                $bruker['wikigroups'], 
                $bruker['wikiepost'], 
                $bruker['isactive'], 
                $bruker['createtime']
            );
            $colBrukere->addItem($objBruker, $objBruker->getId());
        }
        
        return $colBrukere;
    }
    
    public function getMailLink() {
		$format = '<a title="%2$s" class="mail JSnocheck" href="mailto:%2$s">%1$s</a>';
		return sprintf($format, $this->getFullNavn(), $this->getEpost());
	}
}
