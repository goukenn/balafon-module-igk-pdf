<?php


namespace igk\pdf\Engine;

use FPDF;
use IGKHtmlItem;
use PDF;

use function igk_getv as getv;


class PDFTableNode extends PDFNodeBase
{
    protected $html_tagname = "table";

    public function __construct()
    {
        parent::__construct("pdf-table");
    }
  
    
    public function renderPdf($pdf, $options = null)
    {
        // measure table cell
        $_WIDTH = $pdf->GetPageWidth();
        $trs = [];
        $row = 0;
        $col = 0;
        $header_rows = -1;
        $cell_tags = ["th", "td"];
        $measures = []; // list of measurement
        $engine = igk_getv($options, "Engine") ?? new PDFRendererEngine($pdf, null);
        $data = (object)["cells" => [], "rows" => []];
        $measure_options = (object)["line_height" => 8];
        $header_cell = false;
        foreach ($this->getChilds() as $child) {
            $tagname = self::_GetTagName($child);
            if ($tagname == "tr") {
                $trs[] = $child;
                $col_count = 0;
                $row++;
                foreach ($child->getChilds() as $cell) {
                    $ctag = self::_GetTagName($cell);
                    if (in_array($ctag, $cell_tags)) {
                        $col_count++;
                        $hcell = ($ctag == "th");
                        if ($hcell && ($header_rows == -1)) {
                            $header_rows = count($trs) - 1;
                        }
                        $col = max($col, $col_count);
                        $text =PDFUtil::GetRenderText($cell);

                        if (!empty($text)) {
                            $segments = [];
                            $measures = [];

                            foreach ($text as $b) {
                                $s = new PDFTextSegment($b->data);
                                $styles = [];
                                if ($hcell) {
                                    $styles[] = "bold"; // set bold to  all header cell 
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
                            // var_dump($text);
                            // exit;

                            $info = (object)[
                                "segments" => $segments,
                                "node" => $cell,
                                "col" => $col_count,
                                "row" => $row - 1,
                                "col_span" => $cell->colspan ?? 1,
                                "row_span" => $cell->rowspan ?? 1,
                                "styles"=>PDFUtil::GetStyles($cell),
                                "measure" => null,
                                "Height"=>20,
                                "Width" =>50
                            ];
                            $data->cells[] = $info;
                            $data->rows[$row - 1][] = $info;
                        }
                    }
                }
            }
        }
        // $k = (object)[
        //     "width" => 150,
        //     "height"=> 100,           
        //     "border"=> "1",
        //     "align" => "C",
        //     "fill" => false,
        //     "link" => null,
        //     "line_height"=>5,
        //     "boxHeight"=>25
        // ];

 

        $row = count($trs);
        // foreach($data->ranges as $k){
        //     $pdf->MultiCell($k->width, $k->height, $k->text, $k->border, $k->align);
        // }
        $fonts = [8, 30, 8];
        $pdf->SetFillColor(0, 255, 0);
        $x = floor($pdf->GetX());
        $y = floor($pdf->GetY());

        // $pdf->setXY($x, $y);
        // $pdf->Cell(100, 10, json_encode(compact("col", "row", "x", "y", "header_rows")), 0, 1);

        $render = true;
        $r_row = 0;
        $start = 0;
        $render_header = $header_rows != -1;       
        $H = 5; // line height
        while ($render && ($r_row < $row)) {
            $row_span = 1;
            $col_span = 1;
            $pdf->SetXY($x, $y);
            if (!$start) {
                $start = true;
                if ($render_header) {
                    //render header
                    // igk_wln_e("render header");
                    $render_header = false;
                    $r = $data->rows[$header_rows];
                    // var_dump(json_encode($r));
                    $cH = PDFUtil::RenderTableCell($pdf, $r, $H);
                    $y += $cH;
                    if ($r_row == $header_rows){
                        $r_row++;                       
                        continue;
                    }
                    $pdf->SetXY($x, $y);
                }
            }
            $cH = PDFUtil::RenderTableCell($pdf, $data->rows[$r_row], $H);
            $y += $cH; 
            $r_row++;
           
            if (PDFUtil::YExceed($pdf, $y)){
                $pdf->addPage();
                $x = $x;
                $y = PDFUtil::GetTopMargin($pdf);
                $pdf->SetXY($x, $y);
                $start = false;
                $render_header = true;
            }
        }
       
        return true;
    }
}
