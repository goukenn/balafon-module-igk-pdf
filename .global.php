<?php

// global module

use igk\pdf\Engine\PDFDocument;
igk_sys_js_ignore(dirname(__FILE__)."/Scripts");

if (!class_exists("FPDF", false)){
    die("FPDF Library required. please goto <a href=\"http://www.fpdf.org\">http://www.fpdf.org</a>");
}

/**
 * create pdf document
 * @return PDFDocument 
 */
function igk_html_node_pdf_document()
{
    $pdf = new PDFDocument();
    return $pdf;
}
