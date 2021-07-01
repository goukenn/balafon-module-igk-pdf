<?php
namespace igk\pdf\Engine;

use FPDF;
use IGKHtmlItem;

abstract class PDFBoxNode extends PDFNodeBase{
    protected $x, $y,$w, $h;
    public function __construct($x, $y, $w, $h)
    {
        parent::__construct();
        $this->x = $x;
        $this->y = $y;
        $this->w = $w;
        $this->h = $h;
    }
}
