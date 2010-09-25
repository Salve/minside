<?php
if(!defined('MS_INC')) die();

class ServiceFactory {
        public static function getNewBBOppdrag() {
            $col = OppdragElementFactory::getNewBBCollection();
            $objOppdrag = new BBOppdrag($col);
            return $objOppdrag;
        }
}
