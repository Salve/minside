<?php
if(!defined('MS_INC')) die();

class BBOppdrag extends ServiceOppdrag {
    
    public function __construct(ElementCollection $col) {
        $this->elements = $col;
    }
    
    public function genXhtml() {
        $output .= '
            <form action="'.MS_SERVICE_LINK.'&act=subbb" method="POST">
                
            
        
            ';
        foreach($this->elements as $objElement) {
            $output .= $objElement->genInput();
        }
        return $output;
    }
}