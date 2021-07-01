<?php

namespace igk\pdf;
use \igk\gd\Path;
use \IGKColor; 

igk_require_module("igk\gd");

class IGKPDG2GD extends IGKPDF2Visitor{
    private $gd;
    
    public function __construct($gd){
        parent::__construct();
        $this->gd = $gd;
        $this->gd->setAlphaBlending(false);
        // $this->gd->Clearf((object)["R"=>0.36, "G"=>0.25, "B"=>0.8]);
        $this->gd->setAntialias($this->states[0]->antialias);
    }
    public function VisitSetFillModeEventOdd(){
        //clip using event-odd
        $this->states[0]->fillmode = "event-odd";
        $this->states[0]->path->reverse = true;
        $this->states[0]->clipath =  $this->states[0]->path->getBound();
        $this->states[0]->path->clear();
        //intersect 
    }
    public function VisitResetFillMode(){
        $this->states[0]->fillmode = "";
        $this->states[0]->path->reverse = false;
        // $this->states[0]->clipath = null; // $this->states[0]->path->getBound(); 
        $this->states[0]->path->clear();
    }
   
    
    public function VisitendPathWidthoutFillorStroke(){
        $cpath = igk_getv($this->states[0], "path");
        if ($cpath){
            $b = $cpath->getBound(); 
            $this->states[0]->clip = $b;
            $cpath->clear();            
        }
    }
 
    public function VisitRestoreState($points){
        array_shift($this->states);
        $this->gd->resetclip();
        if ($this->states[0]->clip){
            list($x, $y, $w, $h) = $this->states[0]->clip; 
        }
    }

    public function VisitRectangle($points){
        list($x, $y, $w, $h) = $points;
        $cpath = igk_getv($this->states[0], "path");
        // $m = $cpath->getBound(); 
        $cpath->addRectangle($x, $y, $w, $h); 
       
    }
    public function VisitfillPath($points){
        $cpath = igk_getv($this->states[0], "path");
  
        if ($cpath){ 
            $pc = $this->states[0]->clipath;
            $c =  $cpath->getPoints();
            $this->gd->setLineWidth(0);
            if ($pc){
                $this->gd->clip($pc[0],
                $pc[1],
                $pc[2]+10,
                $pc[3]+10);
                $c = $pc;
                $this->gd->FillRectangle(  $this->states[0]->{"fill-color"},
                $c[0],$c[1],$c[2],$c[3]
                ,  $this->states[0]->{"fill-color"});
            }else{
                $this->gd->resetClip();
                $this->gd->FillPolygon(  
                    $c
                    ,  $this->states[0]->{"fill-color"});                     
            } 
        }
 
    }
}