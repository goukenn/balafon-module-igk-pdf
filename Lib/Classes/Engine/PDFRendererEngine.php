<?php

namespace igk\pdf\Engine;

use FPDF;
use IGKColor;
use IGKHtmlItem;
use IGKHtmlUtils;
use stdClass;
use function igk_getv as getv;

class PDFRendererEngine
{
    private $m_fpdf;
    private $m_options;
    private $m_theme;
    private $start;
    private $m_states;
 
    public function push_state($state)
    {
        if ($this->m_states === null) {
            $this->m_states = [];
        } 
        array_push($this->m_states, $state);
    }
    public function pop_state()
    {

        if ($d = array_pop($this->m_states)) {
            $d = ($c = count($this->m_states)) > 0 ?
                $this->m_states[$c - 1] : null;
            $this->bind_style($d); 
        }
    }
    public static function GetColor($s, $theme): array
    {
        $r = $g = $b = 0;
        $tcl = $theme ? $theme->cl : [];
        if (igk_css_is_webknowncolor($s, $tcl)) {
            $s = igk_css_get_color_value($s, $tcl);
        }

        $cl = IGKColor::FromString($s);
        $r = $cl->getR();
        $g = $cl->getG();
        $b = $cl->getB();
        return [$r, $g, $b];
    }
    public function bind_style($prop)
    {
        $this->m_options->lineHeight = igk_getv($prop, "line-height", $this->m_options->lineHeight);
        $this->m_fpdf->SetTextColor(...self::GetColor(igk_getv($prop, "color", $this->m_options->color),  $this->m_theme));
        $this->m_fpdf->SetFillColor(...self::GetColor(igk_getv($prop, "background-color", $this->m_options->fillColor), $this->m_theme));
        $this->m_fpdf->SetDrawColor(...self::GetColor(igk_getv($prop, "draw-color", $this->m_options->drawColor), $this->m_theme));
        if ($fs = igk_getv($prop, "font-size", $this->m_options->fontSize)) {
            if (preg_match("/[\.0-9]pt$/", $fs)) {
                $fs = substr($fs, -2);
            }
            $this->m_fpdf->SetFontSize($fs);
        }
        $fstyle = "";
        if (igk_getv($prop, "font-style", "") == "italic") {
            $fstyle .= "I";
        }
        if (igk_getv($prop, "font-weight", "") == "bold") {
            $fstyle .= "B";
        }
        $this->m_fpdf->SetFont('', $fstyle);
    }
    public function __construct($fpdf, $theme = null)
    {
        $this->m_fpdf = $fpdf;
        $this->m_theme = $theme;
        $this->m_options = new stdClass();
        $this->m_options->x = 10;
        $this->m_options->y = 10;
        $this->m_options->fillColor = "#000";
        $this->m_options->color = "#000";
        $this->m_options->drawColor = "#000";
        $this->m_options->fontSize = "12";
        $this->m_options->lineHeight = 12;
        $this->m_states = [];
        $this->start = false;
    }
    public function __get($name){
        igk_wln("name : ", $name);
        igk_trace();
        igk_exit();
    }
    public function GetFontSize(){
        return PDFUtil::GetFontSize($this->m_fpdf);
    }
    public function Render($node, $options)
    {
        $tab = array($node);
        $rdinfo = (object)[
            "parent" => null,
            "rdinfo" => null,
            "tagname" => null,
            "state" => null,
            "count" => 0
        ];
        $p = null;
        $s = "";
        $_x = &$this->m_options->x;
        $_y = &$this->m_options->y;
        $line_height = &$this->m_options->lineHeight;
        $this->m_fpdf->setFont("Helvetica", "");
        $this->m_fpdf->setFontSize($this->m_options->fontSize);
        $this->m_fpdf->addPage();

        if ($options === null) {
            $options = igk_createobj();
        }
        if (!isset($options->templateData)) {
            $options->templateData = igk_createobj();
        }
        $indent = $options->Indent;
        $depth = 0;
        $styleprop = null;
        $p = null;

        while ($node  = array_pop($tab)) {
            if ($p === $node) {
                igk_wln_e("infinie loop detected");
            }
            $p = $node;
            if ($node->getFlag("NO_TEMPLATE")) {
                // decreate item counter and continue
                $rdinfo->count--;
                continue;
            }
            $state = 0;
            if ($styleprop = PDFUtil::GetStyles($node)) {
                $this->push_state($styleprop);
                $this->bind_style($styleprop);
                $state = 1;
            }


            if (method_exists($node, "renderPdf")) {
                $c = $node->renderPdf($this, $this->m_options);
                if ($state) { 
                    $this->pop_state();
                    $state = 0;
                }
                if ($c) {
                    $rdinfo->count--;
                    continue;
                }
            }

            if ($node->getFlag(IGK_NODETYPE_FLAG) == "c") {
                $tagname = $node->tagName;
            } else {
                $tagname = "igk:" . $node->tagName;
            }
         
            if (!$this->start) {
                $this->start = true;
            }
            $inner = IGK_STR_EMPTY;
            if (!$node->getFlag("NO_CONTENT"))
                $inner .= IGKHtmlUtils::GetContentValue($node, $options);
            if (trim($inner) != "") {

                $this->m_fpdf->SetXY($_x, $_y);
                

                $this->m_fpdf->Write($line_height, PDFUtil::CleanText($inner));

                //$this->m_fpdf->Text($_x, $_y, $inner);

            }
            if (!$node->getFlag("NO_CHILD")) {
                $c_childs = $node->GetRenderingChildren($options);
                $c_tchild = igk_count($c_childs);

                if ($c_tchild > 0) {

                    // $s .= ">";
                    $rdinfo = (object) [
                        "parent" => $node,
                        "rdinfo" => $rdinfo,
                        //"content" => $inner,
                        "tagname" => $tagname,
                        "count" => $c_tchild,
                        "state" => $state
                    ];
                    $p = $node;
                    $tab = array_merge($tab, array_reverse($c_childs));
                    if ($indent) {
                        $lf = "\n";
                        $depth++;
                        $indent_str = str_repeat("\t", $depth);
                    }
                    $_y += $line_height;
                    continue;
                }
            }


            $rdinfo->count--;
            if ($rdinfo->count <= 0) {
                $gg = 0;
                do {
                    if ($indent) {
                        $lf = "\n";
                        $depth--;
                        $indent_str = str_repeat("\t", $depth);
                    }
                    if ($state || (isset($rdinfo->state) && $rdinfo->state)) {
                          $this->pop_state();
                        $state = 0;
                    }
                    if ($rdinfo->rdinfo !== null) {
                        $ginfo = $rdinfo;
                        $rdinfo = $rdinfo->rdinfo;
                        $rdinfo->count--;
                        if (!isset($rdinfo->count)) {
                            var_dump($rdinfo);
                            var_dump($ginfo);
                            die("count not found");
                        }
                    } else {
                        $this->pop_state();
                        $state = 0;
                    }
                    $gg++;
                    if ($gg > 20) {
                        var_dump($rdinfo);
                        die("bad thing");
                    }
                } while ($rdinfo && isset($rdinfo->count) && ($rdinfo->count >= 0));
            } else if ($state) {
                igk_ilog("pop_::::closing restore");
                $this->pop_state();
                $state = 0;
            }
            $_y += $line_height;
        }
        while ($rdinfo && $rdinfo->tagname) {
            if ($indent) {
                //$lf = "\n";
                $depth--;
                //$indent_str = str_repeat("\t", $depth);
            }
            $rdinfo = $rdinfo->rdinfo;
        }
        $this->m_states = [];
        $this->start = false;

        return $s;
    }
   

    public function GetSegments($txt, $hcell=0 , & $measure_options = null, callable $callback=null){
        $v_dinf = PDFUtil::GetRenderText($txt);
        $pdf = $this->m_fpdf;
        if (!empty($v_dinf)) {
            $segments = [];
            $measures = [];

            foreach ($v_dinf as $b) {
                $s = new PDFTextSegment($b->data);
                $styles = [];
                if ($hcell) {
                    $styles[] = "bold"; // set bold to  all header cell 
                }
                if ($b->href){
                    $s->link = $b->href;
                  //   igk_wln_e("link : ".    $s->link );
                } 
        
                for ($i = 0; $i < strlen($b->type); $i++) {
                  
                    $m = getv(PDFUtil::$StyleDefs,  $b->type[$i], "regular");
                    if (!in_array($m,  $styles)) {
                        $styles[] = $m;
                    }
                } 
                $s->style = implode("|", $styles); 
                $s->css = $b->style;
                $segments[] = $s;
                $measures[] = $s->GetMeasurements($pdf, $measure_options);                                
            } 
            // igk_wln_e($segments);
            if ($callback){
                $callback($txt, $segments, $measures);
            }
            return compact("segments", "measures");
        }            
    }

    public function BindTextStyle(PDFTextSegment $segment){
        $segment->BindStyle($this->m_fpdf);
    }

  
    public function __call($name, $args){
        if (method_exists($this->m_fpdf , $name)){
            return call_user_func_array([$this->m_fpdf, $name, ],  $args);
        }
    }
}
