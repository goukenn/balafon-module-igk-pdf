<?php

namespace igk\pdf\Engine;

/**
 * pdf rendering statate
 * @package igk\pdf\Engine
 */
class PDFStatesEngine{
    static $sm_instance;

    var $fontSize = 12;
    var $backgroundColor= "#FFF";
    var $color = "#000";
    var $drawColor = "#000";
    var $lineHeight = 8;
    var $padding = [2,2,2,2];
    var $margin = [0,0,0,0];

    private $_states = [];
    private function __construct(){
    }
    /**
     * return the by state instance
     * @return PDFStatesEngine 
     */
    public static function getInstance(): PDFStatesEngine{
        if (self::$sm_instance === null)
            self::$sm_instance = new static;
        return self::$sm_instance;
    }

    public function pushState(){
        array_push($this->_states, json_encode($this));
    }
    public function popState(){
        if ($r = array_pop($this->_states)){
            foreach(json_decode($r) as $k=>$v){
                $this->{$k} = $v;
            }
        }
    }
}