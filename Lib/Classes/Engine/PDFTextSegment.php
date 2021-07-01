<?php
namespace igk\pdf\Engine;

use IGKException;

/**
 * new text box segment use store text definition.
 * @package 
 */
class PDFTextSegment{
    var $text;
    var $font;
    var $fontSize;
    var $style;
    var $css;
    var $link;
    /**
     * contruct
     * @param string $text 
     * @return void 
     */
    public function __construct($text = ""){
        $this->text  = $text;         
    }
    /**
     * measure the text
     * @param mixed $pdf 
     * @param mixed $options object 
     * @return array 
     */
    public function GetMeasurements($pdf, $options =null){
        $line_height = 8;        
        $height = 0;
        $box_width = 0;
        if (isset($options->line_height) && is_numeric($options->line_height) ) {
            $line_height =  $options->line_height;
        }
        $cb = $this->BindStyle($pdf);
        if ($cb){
            extract((array)$cb);
        }

        $widths = [];
        $strings = explode("\n", $this->text);
        foreach($strings as $l){
            $widths[] = $w = $pdf->GetStringWidth($l);
            $box_width = max($box_width, $w);
            $height += $line_height;
        }
        return compact("widths", "height", "strings", "box_width", "line_height");  
    }
    /**
     * bind style
     * @param mixed $pdf 
     * @return object|void 
     * @throws IGKException 
     */
    public function BindStyle($pdf){
        if ($this->font)
        $pdf->setFont($this->font, "");
        if ($this->fontSize)
        $pdf->setFontSize($this->fontSize);
        $g = "";
        foreach(explode("|", $this->style) as $s){
            switch($s){
                case "bold": 
                    $g .= "B";break;
                case "underline";
                    $g .= "U"; break;
                case "italic":
                    $g .=  "I";
                    break;
            }
        }
        $pdf->SetFont("", $g);  
        if ($this->css){ 
            return PDFUtil::BindStyle($pdf, $this->css);
        }
    }
}