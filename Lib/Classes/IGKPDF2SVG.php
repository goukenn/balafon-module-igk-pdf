<?php
namespace igk\pdf;

class IGKPDF2SVG extends IGKPDF2Visitor{
    private $svg;
    private $height;
    public function __construct($svg, $w, $h){
        parent::__construct();
        $this->svg = $svg;
        $this->height = $h;
    }

    
    public function VisitFillPath($points){
        $cpath = igk_getv($this->states[0], "path");  
        if ($cpath){
            $pc = $this->states[0]->clipath;
            if ($pc){
                // $this->gd->clip($pc[0],
                // $pc[1],
                // $pc[2],
                // $pc[3]);
            }else{
            
            }
            $c =  $cpath->getPoints();
            $c = $pc;
            $this->svg->add("g")->add("rect")->setAttributes([
                "x"=>$c[0],
                "y"=>$c[1],
                "width"=>$c[2],
                "height"=>$c[3]
            ]
            )->setAttribute("transform", "matrix(1,0,0,-1,0,{$this->height})")->setAttribute("fill", "#000000");
        }
    }
}