<?php
if(!defined('MS_INC')) die();

class BBOppdrag extends ServiceOppdrag {
    
    public function __construct(ElementCollection $col) {
        $this->elements = $col;
    }
    
    public function genXhtml() {
        foreach($this->elements as $objElement) {
            $output .= $objElement->genInput();
        }
        return $output;
    }
}