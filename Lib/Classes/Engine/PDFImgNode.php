<?php
namespace igk\pdf\Engine;

use FPDF;
use IGKHtmlItem;

class PDFImgNode extends PDFBoxNode{
    protected $uri;
    protected $type;
    protected $link;
    public function __construct($uri, $x=null, $y=null, $w=0, $h=0, $type=null, $link=null)
    {
        parent::__construct($x, $y, $w, $h); 
        $this->uri = $uri;
        $this->type = $type;
        $this->link = $link;
    }
    public function renderPdf($pdf){
        $pdf->Image($this->uri, $this->x, $this->y, $this->w, $this->h, $this->type, $this->link);
        return true;
    }
}