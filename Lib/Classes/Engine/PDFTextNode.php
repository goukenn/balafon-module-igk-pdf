<?php

namespace igk\pdf\Engine;


class PDFTextNode extends PDFNodeBase{
    /**
     * tag to render in web context
     * @var string
     */
    protected $html_tagname ="span";

    public function getCanAddChild()
    {
        return false;
    }

    public function __construct($content=null)
    {
        parent::__construct();
        $this->Content = $content; 
    }

    public function renderPdf($pdf, $options = null)
    {
        $s = $this->Content;
        if (empty($s) || !($segment = $pdf->GetSegments($this))){
            return false;
        }
        $g = PDFUtil::GetStyles($this, null);
         
        $s = $segment["segments"];
        $m = $segment["measures"];
      
        $y = isset($g->top) ? PDFUtil::ConvertToCurrentUnit($pdf, $g->top) : $pdf->GetY();
        $x = isset($g->left) ? PDFUtil::ConvertToCurrentUnit($pdf, $g->left) : $pdf->GetX();
      
        $lines  = [];
        $lwidth = 0;
        $lheight = 0;
        $T = $pdf->GetPageWidth();
        $pdf->SetXY($x, $y);
        foreach($s as $t){ 
            $m = $t->GetMeasurements($pdf, $options);
            $w = $m["widths"][0]; 
            if (($x+ $w) > $T){
                PDFUtil::RenderSegmentLines($pdf, $lines, $lheight);        
                $lines = [];
                $lheight = 0; 
            }
            $lwidth += $w;
            $lheight = max($lheight, $m["line_height"]);
            $lines[] = [$t, $w, $lheight];
        }   
        if (count($lines)>0){
            PDFUtil::RenderSegmentLines($pdf, $lines, $lheight);        
            $lines = []; 
        }
        // igk_wln_e("lines ", $lines);       
        //exit;
        // $s =   "Lorem ipsum dolor sit amet, consectetur adipisicing elit. Magni ut libero ex explicabo nemo totam id alias laudantium eum beatae eveniet est placeat, officia fugit minus, blanditiis, omnis fugiat itaque!";
        // //$g = $pdf->GetStringWidth($s);

        // $pdf->Write(10,  $s);
        return true;
    }
}