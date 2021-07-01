<?php

namespace igk\pdf\Engine;

use FPDF;
use IGKHtmlItem;


class PDFRectNode extends PDFBoxNode{
    protected $x, $y,$w, $h;
    protected $style = "D";

    public function __construct($x, $y, $w, $h, $style="D")
    {
        parent::__construct($x, $y, $w, $h);
        $this->style = $style;
    }
    public function renderPdf($pdf){
        $pdf->Rect($this->x, $this->y, $this->w, $this->h, $this->style);
    }
}

