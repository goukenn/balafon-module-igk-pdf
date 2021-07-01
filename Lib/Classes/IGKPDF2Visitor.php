<?php
namespace igk\pdf;
use \igk\gd\Path;
use \IGKColor;
igk_require_module("igk/gd");

abstract class IGKPDF2Visitor{ 
    protected $states;
    protected function __construct(){
        $this->states = [];
        $this->cpoint = [0,0];
        array_unshift($this->states,  $this->initState()); 
        igk_set_env(IGK_LF_KEY, "\n");
    } 
    protected function initState(){
        return (object)[
            "fillmode"=>"",
            "stroke-width"=>1.0,
            "stroke-color"=>"#00000",
            "fill-color"=>"#000000",
            "tolerance"=>1.0,
            "start"=>1,
            "clip"=>null,
            "antialias"=>true,
            "path"=>$this->initPath()
        ];
    }
    protected function initPath(){
        $c = new Path();
        return $c;
    }

    public function VisitendPathWidthoutFillorStroke(){
        $cpath = igk_getv($this->states[0], "path");
        if ($cpath){
            $b = $cpath->getBound(); 
            $this->states[0]->clip = $b; 
        }
    }
    public function VisitEmbedLast($points){ 
    }
    public function VisitRectangle($points){

    }
    public function Visit($action, $points){
        static $actionName;
        if ($actionName===null){
            $actionName = [
                "i"=>"SetTolerance",
                "m"=>"MoveTo",
                "q"=>"SaveState",
                "Q"=>"RestoreState",
                "l"=>"Line",
                "h"=>"ClosePath",
                "n"=>"endPathWidthoutFillorStroke",
                "w"=>"SetStrokeWith",
                "W*"=>"SetFillModeEventOdd",
                "W"=>"ResetFillMode",
                "Do"=>"EmbedLast",
                "re"=>"Rectangle",
                "cs"=>"SetNonStrokingColor",
                "sc"=>"SetStrokingColor",
                "f"=>"fillPath"
            ];
        }
        if (isset($actionName[$action]))
            $action = $actionName[$action];
        if (!empty($action)){
            $fc = "Visit".$action;
            if (method_exists($this, $fc)){
                //\igk_dev_wln("invoke ".$fc."<br />");
                call_user_func_array([$this, $fc], [$points]);
                return 1;
            }
        }
        return 0;
    } 
    public function VisitMoveTo($points){
        $cpath = igk_getv($this->states[0], "path");
        if ($cpath){
            $cpath->MoveTo($points[0], $points[1]);
            $this->cpoint = $points;
        }
    }
    public function VisitLine($points){
        $cpath = igk_getv($this->states[0], "path");
        if ($cpath){
            $cpath->LineTo($points[0] , $points[1]);
        }
    }
    public function VisitSaveState($points){
        \array_unshift($this->states, $this->states[0]);
        $this->states[0]->path = $this->initPath();
    }
    public function VisitRestoreState($points){
        array_shift($this->states);        
    }
   
    public function VisitClosePath($points){
        $cpath = $this->states[0]->path;
        $cpath->ClosePath(); 
          
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
        //$this->states[0]->clipath = null;// $this->states[0]->path->getBound();
        $this->states[0]->path->clear();
    }
    public function VisitSetStrokeWith($points){
        $this->states[0]->{"stroke-width"} = $points[0];
    }
    
    public function VisitSetNonStrokingColor($points){  
        $cl = "";
        if (count($points)==3){
            list($r,$g,$b) = $points;
            $cl = (IGKColor::FromFloat($r,$g,$b,1))->toWebColor();
        }else {
            // igk_wln("non border space", $points);
            $cl="#000000";
        } 
        // $cl="#00FF00";
        $this->states[0]->{"fill-color"} = $cl; 
    }
    public function VisitSetStrokingColor($points){
        list($r,$g,$b) = $points;
        $this->states[0]->{"stroke-color"} = (IGKColor::FromFloat($r,$g,$b,1))->toWebColor();
    }
    public function VisitSetTolerance($points){
        $this->states[0]->tolerance = $points[0];
    } 
}