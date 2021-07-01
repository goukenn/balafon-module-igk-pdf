<?php
namespace igk\pdf\Engine;

use FPDF;
use IGKHtmlItem;


class PDFPageNode extends PDFNodeBase{
    public function __construct()
    {
        parent::__construct();  
    }
    public function renderPdf($pdf, $options=null){ 
        $pdf->AddPage();
        $options->x = 10;
        $options->y = 0; 
        return false;
    } 
}