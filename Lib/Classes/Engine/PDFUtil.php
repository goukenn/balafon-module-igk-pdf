<?php

namespace igk\pdf\Engine;

use IGKException;
use Exception;
use FPDF;
use IGKHtmlItemBase;
use IGKHtmlStyleValueAttribute;
use IGKHtmlUtils;
use stdClass;

use function igk_getv as getv;
use function igk_json_parse as json_parse;

class PDFUtil extends \FPDF
{
    /**
     * style definition
     * @var string[]
     */
    static $StyleDefs = ["b" => "bold", "i" => "italic", "u" => "uderline"];

    public static function GetFontSize($pdf){
        return $pdf->FontSize;
    }
    public static function WriteCell(\FPDF $fpdf, $width, $h, $txt = "", $link = null, $border = 0, $alignment = "")
    {

        // Output text in flowing mode
        if (!isset($fpdf->CurrentFont))
            $fpdf->Error('No font has been set');
        $cw = &$fpdf->CurrentFont['cw'];
        $w = $width; // ? $width :  $fpdf->w - $fpdf->rMargin - $fpdf->x;


        $wmax = ($w - (2 * $fpdf->cMargin) - ($fpdf->x - $fpdf->lMargin)) * 1000 / $fpdf->FontSize;
        $WMAX =  ($w - 2 * $fpdf->cMargin) * 1000 / $fpdf->FontSize;
        $s = str_replace("\r", '', $txt);
        $nb = strlen($s);
        $sep = -1;
        $i = 0;
        $j = 0;
        $l = 0;
        $nl = 1;

        while ($i < $nb) {
            // Get next character
            $c = $s[$i];
            if ($c == "\n") {
                // Explicit line break

                $fpdf->Cell((($l + 1000) / 1000) * $fpdf->FontSize, $h, substr($s, $j, $i - $j), $border, 2, $alignment, false, $link);
                $i++;
                $sep = -1;
                $j = $i;
                $l = 0;
                if ($nl == 1) {
                    $fpdf->x = $fpdf->lMargin;
                    //$w = $fpdf->w - $fpdf->rMargin - $fpdf->x;
                    $wmax =  $WMAX;
                }
                $nl++;
                continue;
            }
            if ($c == ' ')
                $sep = $i;
            $l += $cw[$c];

            //igk_wln_e($l, $wmax);
            if ($l > $wmax) {
                // Automatic line break
                if ($sep == -1) {
                    if ($fpdf->x > $fpdf->lMargin) {
                        // Move to next line
                        $fpdf->x = $fpdf->lMargin;
                        $fpdf->y += $h;
                        //$w = $fpdf->w - $fpdf->rMargin - $fpdf->x;
                        $wmax =  $WMAX;
                        $i++;
                        $nl++;
                        continue;
                    }
                    if ($i == $j)
                        $i++;
                    $fpdf->Cell($w, $h, substr($s, $j, $i - $j), $border, 2, $alignment, false, $link);
                } else {
                    $fpdf->Cell($wmax / 1000 * $fpdf->FontSize, $h, substr($s, $j, $sep - $j), $border, 2, $alignment, false, $link);
                    $i = $sep + 1;
                }
                $sep = -1;
                $j = $i;
                $l = 0;
                if ($nl == 1) {
                    $fpdf->x = $fpdf->lMargin;
                    //$w = $fpdf->w - $fpdf->rMargin - $fpdf->x;
                    $wmax =  $WMAX;
                }
                $nl++;
            } else
                $i++;
        }
        // Last chunk
        if ($i != $j) {
            $fpdf->Cell($l / 1000 * $fpdf->FontSize, $h, substr($s, $j), $border, 0, $alignment, false, $link);
            // $fpdf->Cell($w, $h, substr($s, $j), $border, 0, $alignment , false, $link);
        }
    }
    public static function CleanText($text){
        $text = str_replace("&nbsp;", "", $text);

        return $text;
    }
    /**
     * measuure text with current selected font
     * @param FPDF $fpdf 
     * @param mixed $width 
     * @param mixed $h 
     * @param string $txt 
     * @return array 
     * @throws Exception 
     */
    public static function WriteGetMeasure(\FPDF $fpdf, $width, $h, $txt = "")
    {
        $lines = [];
        // Output text in flowing mode
        if (!isset($fpdf->CurrentFont))
            $fpdf->Error('No font has been set');
        $cw = &$fpdf->CurrentFont['cw'];
        $w = $width;
        $bck = $fpdf->cMargin;
        $fpdf->cMargin = 0;

        $wmax = ($w - (2 * $fpdf->cMargin) - ($fpdf->x - $fpdf->lMargin)) * 1000 / $fpdf->FontSize;
        $WMAX =  ($w - 2 * $fpdf->cMargin) * 1000 / $fpdf->FontSize;

        if ($wmax<0){
           
        }
        $s = str_replace("\r", '', $txt);
        $nb = strlen($s);
        $sep = -1;
        $i = 0;
        $j = 0;
        $l = 0;
        $nl = 1;
        $offpdf = clone ($fpdf);

        // igk_ilog("wmax: ".$s . " : ".$wmax);

        while ($i < $nb) {
            // Get next character
            $c = $s[$i];
            if ($c == "\n") {
                // Explicit line break

                $offpdf->Cell((($l + 1000) / 1000) * $fpdf->FontSize, $h, substr($s, $j, $i - $j), 0, 2, "");
                $fpdf->x = $offpdf->x;
                array_push($lines, [
                    (($l + 1000) / 1000) * $fpdf->FontSize, $h,
                    substr($s, $j, $i - $j), 0, 2, "", false,  "fontSize" => $fpdf->FontSize, "fontStyle" => $fpdf->FontStyle,
                    "section" => 1
                ]);

                $i++;
                $sep = -1;
                $j = $i;
                $l = 0;
                if ($nl == 1) {
                    $fpdf->x = $fpdf->lMargin;
                    //$w = $fpdf->w - $fpdf->rMargin - $fpdf->x;
                    $wmax =  $WMAX;
                }
                $nl++;
                continue;
            }
            if ($c == ' ')
                $sep = $i;
            $l += $cw[$c];

            if ($l > $wmax) {
                //   igk_ilog("auto break !!!! ".$sep. " :: ".  $fpdf->x ." > ".$fpdf->lMargin);
                // Automatic line break
                if ($sep == -1) {
                    if ($fpdf->x > $fpdf->lMargin) {
                        igk_ilog( " s : ".$s . " data ".$fpdf->x ." > ".$fpdf->lMargin . " ".$l . " wmax : ".$wmax);
             
                        // Move to next line
                        $fpdf->x = $fpdf->lMargin;
                        $fpdf->y += $h;
                        //$w = $fpdf->w - $fpdf->rMargin - $fpdf->x;
                        $wmax =  $WMAX;
                        $i++;
                        $nl++;
                        // igk_wln_e("move to next line" . $s);
                        array_push($lines, [$w, $h, null, 0, 2, "", false, "fontSize" => $fpdf->FontSize, "fontStyle" => $fpdf->FontStyle,  "section" => "newline"]);
                        continue;
                    }
                    if ($i == $j)
                        $i++;
                    $offpdf->Cell($w, $h, substr($s, $j, $i - $j), 0, 2, "");
                    $fpdf->x = $offpdf->x;
                    array_push($lines, [$w, $h, "2" . substr($s, $j, $i - $j), 0, 2, "", false, "fontSize" => $fpdf->FontSize, "fontStyle" => $fpdf->FontStyle,  "section" => 2]);
                } else {
                    $offpdf->Cell($wmax / 1000 * $fpdf->FontSize, $h, $ms = substr($s, $j, $sep - $j), 0, 2, "");
                    $fpdf->x = $offpdf->x;
                    array_push(
                        $lines,
                        [!empty(trim($ms)) ? $wmax / 1000 * $fpdf->FontSize : 0, $h, substr($s, $j, $sep - $j), 0, 2, "", false,  "fontSize" => $fpdf->FontSize, "fontStyle" => $fpdf->FontStyle, "section" => 3]
                    );

                    $i = $sep + 1;
                }
                $sep = -1;
                $j = $i;
                $l = 0;
                if ($nl == 1) {
                    $fpdf->x = $fpdf->lMargin;
                    //$w = $fpdf->w - $fpdf->rMargin - $fpdf->x;
                    $wmax =  $WMAX;
                }
                $nl++;
            } else
                $i++;
        }
        // Last chunk
        if ($i != $j) {
            $offpdf->Cell($l / 1000 * $fpdf->FontSize, $h,  substr($s, $j), 0, 0, "");
            $fpdf->x = $offpdf->x;
            array_push($lines, [
                $l / 1000 * $fpdf->FontSize, $h,  substr($s, $j), 0, 0, "", false,
                "fontSize" => $fpdf->FontSize, "fontStyle" => $fpdf->FontStyle, "section" => 4, "i" => $i, "j" => $j, "l" => $l, "wmax" => $wmax
            ]);

            // $fpdf->Cell($l / 1000 * $fpdf->FontSize, $h, substr($s, $j), $border, 0, $alignment, false, $link);
            // $fpdf->Cell($w, $h, substr($s, $j), $border, 0, $alignment , false, $link);
        }
        unset($offpdf);
        $fpdf->cMargin = $bck;
        return $lines;
    }

    public static function GetAvailableWidth($fpdf)
    {
        return $fpdf->GetPageWidth()  - $fpdf->rMargin - $fpdf->lMargin;
    }
    public static function YExceed($pdf, $y)
    {
        //$Y = -$y + $pdf->GetPageHeight() - $pdf->tMargin -$pdf->bMargin;
        $rp = $pdf->tMargin + $pdf->bMargin + 2;
        //  igk_ilog(" ".($y ). "  > ".($pdf->GetPageHeight() - $rp));
        return ($y) > ($pdf->GetPageHeight() - $rp);
        // igk_wln_e($y, $Y);

    }
    public static function GetTopMargin($pdf)
    {
        return $pdf->tMargin;
    }
    /**
     * 
     * @param mixed $pdf pdf instance
     * @param mixed $lines array of line segment
     * @param mixed $x position x
     * @param mixed $y position y
     * @param mixed $w max width
     * @param string $alignment C|R alignment
     * @param int $border allows segment border
     * @return void 
     */
    public static function RenderLines($pdf, $lines, $x, $y, $w, $alignment = "R", $border = 0, $fill = false)
    {
        $line_width = 0;
        $line_height = 0;
        $pdf->SetX($x);
        $pdf->SetY($y);
        $toRender = [];
        $bck = $pdf->cMargin;
        $pdf->cMargin = 0;
        //$pdf->Rect($x , $y, $w, 10, '');
        $alignment = strtoupper($alignment);


        foreach ($lines as $ln) {

            $line_width += $ln[0];
            $line_height = max($line_height, $ln[1]);
            $toRender[] = $ln;
            if ($ln[4] == 2) {
                self::_renderLine($pdf, $toRender, $w, $x, $y, $line_width, $line_height, $fill, $alignment, $border);

                $y += $line_height;
                $line_width = 0;
                $line_height = 0;
                $toRender = [];
                continue;
            }
        }
        if (count($toRender) > 0) {
            self::_renderLine($pdf, $toRender, $w, $x, $y, $line_width, $line_height, $fill, $alignment, $border);
        }
        $pdf->cMargin = $bck;
    }
    private static function _renderLine($pdf, $toRender, $w, $x, $y, $line_width, $line_height, $fill, $alignment, $border)
    {
        // move to next line
        $pdf->SetX($x); // + $line_width / 2);
        $pdf->SetY($y);
        //$pdf->Rect(x + $w - ($line_width/2), $y, $line_width, $line_height, 'F');
        if ($fill) {
            if ($alignment == "R") {
                $pdf->Rect($x + $w - $line_width, $y, $line_width, $line_height, 'F');
            } else {
                $pdf->Rect($x + (($w - $line_width) / 2), $y, $line_width, $line_height, 'F');
            }
        }
        $offset = 0;
        foreach ($toRender as $m) {
            if ($m[0] == 0) {
                continue;
            }
            $pdf->SetFont("", $m["fontStyle"]);

            if ($alignment == "R") {
                // $pdf->Rect(-$x + $w - $line_width, $y, $line_width, $line_height, 'F');
                $pdf->setXY($offset + $x + $w - $line_width, $y);
            } else {
                $pdf->setXY($offset + $x + ($w - $line_width) / 2, $y);
            }
            $pdf->Cell($m[0], $line_height, $m[2], $border, 0);
            $offset += $m[0]; //  floor($pdf->GetX() - $x); 
        }
    }

    public static function FillRenderLine($pdf, $lines, $x, $y, $w, $fill = true, $LH = 0)
    {
        $line_height = $LH;
        $H = 0;
        $c = 0;
        foreach ($lines as $ln) {

            $line_height = max($line_height, $ln[1]);
            if ($ln[4] == 2) {

                $H += $line_height;
                $line_height = 0;
                $c = 0;
                continue;
            }
            $c = 1;
        }
        if ($c) {
            $H += $line_height;
        }
        if ($fill) {
            $pdf->rect($x, $y, $w, $H, "F");
        }
        return $H;
    }
    private static function pushState()
    {
        PDFStatesEngine::getInstance()->pushState();
    }
    private static function popState($pdf, $theme = null)
    {
        $defStyle = PDFStatesEngine::getInstance();

        $defStyle->popState();

        $pdf->SetTextColor(...PDFRendererEngine::GetColor($defStyle->color, $theme));
        $pdf->SetFillColor(...PDFRendererEngine::GetColor($defStyle->backgroundColor, $theme));
        $pdf->SetDrawColor(...PDFRendererEngine::GetColor($defStyle->drawColor, $theme));
    }
    public static function BindStyle($pdf, $style, $theme=null)
    {
        $defStyle = PDFStatesEngine::getInstance();

        $alignment = getv(["center" => "C", "left" => "", "right" => "R"], getv($style, "text-align", "left"), "");
        $color = igk_getv($style, "color", $defStyle->color);
        $backgroundColor = igk_getv($style, "background-color", $defStyle->backgroundColor);
        $drawColor = igk_getv($style, "draw-color", $defStyle->drawColor);
        $line_height = igk_getv($style, "line-height", $defStyle->lineHeight);
        $padding = null;
        $margin = $top = $left = null ;
        foreach ($style as $k => $v) {
            switch ($k) {
                case "top":
                    $top = $v;
                    break;
                case "left":
                    $left = $v;
                    break;
                case "padding":
                    switch (count($tv = explode(" ", $v))) {
                        case 1:
                            $padding = [$v, $v, $v, $v];
                            break;
                        case 2:
                            $padding = [$tv[0], $tv[1], $tv[0], $tv[1]];
                            break;
                        case 4:
                            $padding = $tv;
                            break;
                    }
                    self::_toPdfUnit($padding, $k);
                    break;
                case "margin":
                    switch (count($tv = explode(" ", $v))) {
                        case 1:
                            $margin = [$v, $v, $v, $v];
                            break;
                        case 2:
                            $margin = [$tv[0], $tv[1], $tv[0], $tv[1]];
                            break;
                        case 4:
                            $margin = $tv;
                            break;
                    }
                    self::_toPdfUnit($margin, $k);
            }
        }


        $pdf->SetTextColor(...PDFRendererEngine::GetColor($color, $theme));
        $pdf->SetFillColor(...PDFRendererEngine::GetColor($backgroundColor, $theme));
        $pdf->SetDrawColor(...PDFRendererEngine::GetColor($drawColor, $theme));
        if ($fs = igk_getv($style, "font-size", $defStyle->fontSize)) {
            if (is_numeric($fs)){
                $fs .= "pt";
            }
            if (preg_match("/[\.0-9](pt)$/", $fs)) {
                $fs = substr($fs, 0, -2);
            }else{
                if (preg_match("/[\.0-9](mm|cm|px)$/", $fs)){
                    $u = substr($fs,strlen($fs) -2);
                    $fs = substr($fs, 0, -2);
                    $fs = self::ConvertToPoint($fs, $u);
                }else{
                    die("data not allowed: ".$fs);
                }
            } 
            $pdf->SetFontSize($fs);
        }
        $fontStyle = "";
        if (igk_getv($fontStyle, "font-style", "") == "italic") {
            $fontStyle .= "I";
        }
        if (igk_getv($style, "font-weight", "") == "bold") {
            $fontStyle .= "B";
        }
        $tab = get_defined_vars();
        unset($tab["defStyle"]);
        unset($tab["pdf"]);
        unset($tab["style"]);
        unset($tab["theme"]);
        unset($tab["k"]);
        unset($tab["v"]);
        return (object)$tab; 
    }
    private static function _toPdfUnit(&$units, $property = "")
    {
        if (!$units)
            return;
        if (is_array($units)) {
            foreach ($units as $k => $u) {
                $units[$k] = self::_GetUnit($u, $property);
            }
        } else
            $units = self::_GetUnit($units, $property);
    }
    private static function _GetUnit($v, $property = "")
    {
        if (preg_match("/(?P<value>.+)(?P<type>(px|%))$/i", $v, $tab)) {
            switch ($tab["type"]) {
                case "px":
                    break;
                case "%":
                    break;
            }
            return $tab["value"];
        }
        if (is_numeric($v)) {
            return $v;
        }
        return 0;
    }
    
    public static function ConvertToPoint($value, $unit){
        switch($unit){
            case "cm":
                $value = ($value * 2.835) / 10.0;
                break;
            case "mm":
                $value = ($value * 2.835);
                break;
            case "px":
                $value = ($value * 0.75);
                break;
        }
        return $value;
    }
    public static function ConvertToCurrentUnit($pdf, $value){
        $u = "";
        $u = substr($value, strlen($value) -2);
        $value = substr($value, 0, -2);
        return $value;
    }

    public static function RenderSegmentLines($pdf, $lines, $lineheight=8){
        $y = $pdf->GetY();
        $x = $pdf->GetX();
        $gf = $pdf->getFontSize();          
        foreach($lines as $l){
           $pdf->BindTextStyle($l[0]);                  
           $pdf->SetXY($x,$y - ((-$gf + $pdf->getFontSize()) / (1+2.835) ) );                 
           $pdf->Write($lineheight, $l[0]->text, $l[0]->link);
           $x += $l[1]; 
        } 
    }
    /**
     * 
     * @param mixed $pdf 
     * @param mixed $r 
     * @param mixed $H 
     * @param int $rowHeight 
     * @return mixed 
     * @throws IGKException 
     * @throws Exception 
     */
    public static function RenderTableCell($pdf, $r, $H, &$rowHeight = 0)
    {
        $r_col = 0;
        $col_span = 1;
        $line_height  = $rowHeight;
        $box_height = $rowHeight;
        $x = $pdf->GetX();
        $y = $pdf->GetY();
        $mright = $pdf->rMargin;
        $lright = $pdf->lMargin;
        $available = PDFUtil::GetAvailableWidth($pdf);
        $X = $x;
        $Y = $y;
        $cells = [];
        $paddLeft =
            $paddTop =
            $paddRight = $paddBottom = 5;
        foreach ($r as $th) {
            // $_inf = $cols_measures[$r_col];
            self::pushState();
            $style = self::_bindStyle($pdf, $th->styles);
            $H = $style->line_height ?? $H;

            if ($style->padding) {
                list($paddTop, $paddRight, $paddBottom, $paddLeft) = $style->padding;
            } else {
                list($paddTop, $paddRight, $paddBottom, $paddLeft) = PDFStatesEngine::getInstance()->padding;
            }

            // $th->Width += 10;

            // igk_wln($th->Width, $mright );
            // lmargin + space + rmargin
            // PDFUtil::GetAvailableWidth($pdf) : W = PageWidth() -lmargin -rmargin
            // ==> -$rmargin = W + lmargin - PageWidth
            // ==> $rmargin = -W -lmargin + PageW 
            $pdf->setRightMargin($available + $pdf->lMargin - $th->Width + $paddRight + $paddLeft);
     
            $c = 0; 
            $w = PDFUtil::GetAvailableWidth($pdf);
            $lines = [];
            $alignment = $style->alignment;
            foreach ($th->segments as $s) { 
                $pdf->SetXY($x, $y);
                $s->BindStyle($pdf);
                $s->measure_lines =  PDFUtil::WriteGetMeasure($pdf, $w, $H,  utf8_decode($s->text));
                $lines = array_merge(
                    $lines,
                    $s->measure_lines
                );
            }

           
            // igk_wln("line : ".$line_height);
            $line_height = max($line_height,  PDFUtil::FillRenderLine($pdf, $lines, $x, $y, $w, false, $H));
            $box_height = max($box_height, $line_height +  $paddTop + $paddBottom);
            $cells[] = [$th, $lines, $style];
                         
            $r_col += $col_span;
            $x += $w + $paddRight + $paddLeft;           
            self::popState($pdf, null);
        }
        $x = $X;
        $r_col = 0;
        // measures
        foreach ($cells as $t) {
            $y = $Y;
            $lines = $t[1];
            $th = $t[0];
            $style = $t[2];
            self::pushState();
            $style = self::_bindStyle($pdf, $th->styles);
            $H = $style->line_height ?? $H;
            $alignment = $style->alignment;
            if ($style->padding) {
                list($paddTop, $paddRight, $paddBottom, $paddLeft) = $style->padding;
            } else {
                list($paddTop, $paddRight, $paddBottom, $paddLeft) = PDFStatesEngine::getInstance()->padding;
            }
            $pdf->Rect(
                $x,
                $y,
                $w  + $paddRight + $paddLeft,
                $line_height + $paddTop + $paddBottom,
                "F"
            );
            // inline data cell
            $pdf->Rect(
                $x + $paddRight,
                $y + $paddTop,
                $w,
                $line_height,
                "D"
            );

            // $pdf->lMargin = $x + $paddLeft;
            $pdf->SetXY($x + $paddLeft, $y + $paddTop);
            //$alignment = "R";
            $lmargin = $pdf->lMargin;
            $tmargin = $pdf->tMargin;
            $pdf->lMargin = $x + $paddLeft;
            $pdf->tMargin = $y + $paddTop;
            if (empty($alignment) || (strtolower($alignment == "l"))) {
              
                $ct = 0;
                $pdf->cMargin = 0;
                foreach ($th->segments as $s) {
                    $s->BindStyle($pdf);
                    if ($style->padding) {
                        list($paddTop, $paddRight, $paddBottom, $paddLeft) = $style->padding;
                    } else {
                        list($paddTop, $paddRight, $paddBottom, $paddLeft) = PDFStatesEngine::getInstance()->padding;
                    }
                    $ts = "";
                    foreach ($s->measure_lines as $lines) {
                        $ts .= $lines[2];
                        if ($lines[4] == 2) {
                            $pdf->Cell(
                                0,
                                $H,
                                $ts,
                                0,
                                2
                            );
                            $ts = "";
                            $ct = 0;
                            $pdf->x = $x + $paddRight;
                        }

                        $ct  = 1;
                    }
                   
                    if ($ct) {
                        //  extends margin to write text                          
                        $lmargin = $pdf->lMargin;
                        $rmargin = $pdf->rMargin;
                        $pdf->lMargin = 10;
                        $pdf->rMargin = 10;
                        $pdf->Write($H, $ts, null);
                        $pdf->lMargin = $lmargin;
                        $pdf->rMargin = $rmargin;
                    }
                }
            } else {
                PDFUtil::RenderLines($pdf, $lines, $x, $y, $w, $alignment, 0, true);
            }
            $pdf->lMargin = $lmargin;
            $pdf->tMargin = $tmargin;
            $r_col += $col_span;
            $x += $w + $paddLeft + $paddRight;
            self::popState($pdf, null);
        }
        //restore right margin
        $pdf->rMargin = $mright;
        $pdf->lMargin = $lright;
        return $rowHeight = $box_height;
    }

    public static function GetStyles($node, $oldstyle=null)
    {
        if (method_exists($node, "getStyle")) {
            if (($style = $node->getStyle())  && ($style instanceof IGKHtmlStyleValueAttribute)) {

                $styleprop = json_parse("{" . str_replace(";", ", ", $style->getValue()) . "}", false);
                if ($styleprop) {
                    if ($oldstyle){
                        foreach($oldstyle as $k=>$v){
                            if (!property_exists($styleprop, $k)){
                                $styleprop->$k = $v;
                            }
                        }
                    }
                    return $styleprop;
                }
            }
        }
        return $oldstyle;
    }

     /**
     * Get text to render
     * @return string|array multi definition style array
     */
    public static function GetRenderText(IGKHtmlItemBase $node, $options = null){
        $tab = array(["n"=>$node, "p"=>null]);
        $output = [];
        $ctype = "r";
        $style_match = "/\<(b|i|u|a)\s*/i";
        $s = "";  
        $cstyle = null;
        $ostyle = [];
        // $debug = false;
        while($q = array_shift($tab)){
            $n = $q["n"];
            $p = $q["p"];
            $href = getv($q, "href", null);
            $ostyle = array_filter(str_split(getv($q, "style", "")));
            

            if ($n->getFlag(IGK_NODETYPE_FLAG) == "c") {
                $tagname = $n->tagName;
            } else {
                $tagname = "igk:" . $n->tagName;
            }
            switch($tagname){
                case "i":
                case "b":
                case "u":
                   
                    $ctype = $tagname;
                    if (!in_array($ctype, $ostyle)){
                        array_push($ostyle, $ctype);                        
                    }
                    $ctype = implode("", $ostyle);
                    $ostyle = [];
                    break;
                case "a":
                    // $ctype = $tagname;
                    $href = $n->Attributes["href"];
                    if (isset($q["style"])){
                        $ctype = $q["style"];
                    } 
                    break;
                default:
                    if (isset($q["style"])){
                        $ctype = $q["style"];
                    }                   
                    break;
            }
            if (isset($q["css"])){
                $cstyle = $q["css"];
            }
            $cstyle= PDFUtil::GetStyles($n, $cstyle);
            $inner = IGKHtmlUtils::GetContentValue($n, $options);

          
            if (trim($inner) != "") {
                if (preg_match($style_match, $inner)){
                    $d = igk_createnode("dummy");
                    $d->load($inner);
                    if (!empty($v = $d->getContent())) {
                        $s .= $v;
                    }
                    if ($d->getChildCount() > 0) {
                        $bb = array_reverse($d->getChilds()->to_array());
                        foreach($bb as $dp){                            
                            array_unshift($tab, ["n"=>$dp, "p"=>$n, "style"=>$ctype, 
                            "css"=>$cstyle, "href"=>$href]);
                        }         
                    } 
                    $inner = "";
                } else {
                    $s .= $inner;
                }
            }
            if ($n->getChildCount() > 0) {
                $bb = array_reverse($n->getChilds()->to_array());
                foreach( $bb as $dp){                            
                    array_unshift($tab, ["n"=>$dp, "p"=>$n, "style"=>$ctype,  "css"=>$cstyle, "href"=>$href]);
                }         
            } 
            if (!empty($inner)){                
                // igk_wln("inner : ". $ctype. ":  = ".$inner, $cstyle);
                $output[] = (object) [
                    "data"=>$inner, 
                    "type"=>$ctype,
                    "style"=>$cstyle,
                    "href"=>$href
                ];
            }            
        }
    //  igk_wln_e($output, $s);
        return $output;

    }
    public static function GetRenderText2(IGKHtmlItemBase $node, $options = null)
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
        $state = 0;
        $debug = 0;
        $ctype = "r"; // regular text
        $s_output = null;
        $style_states = [];
        $cstyle = null;
        $oldstyle = null;
        $styles_t = [];



        if ($options === null) {
            $options = (object)[
                "Indent" => false
            ];
        }
        if (!isset($options->templateData)) {
            $options->templateData = igk_createobj();
        }
        $indent = $options->Indent;
        $depth = 0;
        $styleprop = null;
        $p = null;
        $start = 0;
        $style_match = "/\<(b|i|u|a)\s*/i";

        $ctype = "r";

        while ($node  = array_pop($tab)) {
            if ($p === $node) {
                igk_die("infinie loop detected");
            }
            $p = $node;
            if ($node->getFlag("NO_TEMPLATE")) {
                // decreate item counter and continue
                $rdinfo->count--;
                continue;
            }

            if (method_exists($node, "getPdfText")) {
                $c = $node->getPdfText();
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

            switch ($itype = strtolower($tagname)) {
                case "b":
                case "i":
                case "u":
                    if ($itype != $ctype) {
                        if ($s_output === null) {
                            $s_output = [(object)[
                                "data" => $s, // copy object data
                                "type" => $ctype,
                                "style"=> $cstyle
                            ]];
                        }
                        array_push($style_states, $ctype);
                        if (empty($s_output[count($s_output) - 1]->data)) {
                            //ajust addid segment to filter 
                            $nt = $s_output[count($s_output) - 1];
                            if (strpos($nt->type, $itype) === false) {
                                $nt->type .= $itype;
                            }
                        } else {
                            $nt = (object)[
                                "data" => "",
                                "type" => $itype,
                                "style"=> $cstyle
                            ];
                            $s_output[] = $nt;
                        }
                        $ctype = $itype;
                        $s = &$nt->data;
                    }
                    break;
            }
            
            if (!$start) {
                $start = true;
            }
            $inner = IGK_STR_EMPTY;
            $c_childs = [];
            if (!$node->getFlag("NO_CONTENT"))
                $inner .= IGKHtmlUtils::GetContentValue($node, $options);
            if ($m_cstyle= PDFUtil::GetStyles($node)){
                 array_push($styles_t, $m_cstyle);
                 $cstyle = $m_cstyle;
            }
          

            if (trim($inner) != "") {
                if (preg_match($style_match, $inner)){
                    $d = igk_createnode("dummy");
                    $d->load($inner);
                    if (!empty($v = $d->getContent())) {
                        $s .= $v;
                    }
                    if ($d->getChildCount() > 0) {
                        $c_childs = array_merge($c_childs, $d->getChilds()->to_array());                  
                    }
                } else {
                    $s .= $inner;
                }
            }
            if (!$node->getFlag("NO_CHILD")) {
                $c_childs = array_merge($c_childs, $node->GetRenderingChildren($options) ?? []);
            }
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
                continue;
            }
            $rdinfo->count--;
            if ($rdinfo->count <= 0) {
                $gg = 0;
                do {
                    if ($indent) {
                        // $lf = "\n";
                        $depth--;
                        // $indent_str = str_repeat("\t", $depth);
                    }
                    //  igk_ilog("pop state ");
                    if ($state || (isset($rdinfo->state) && $rdinfo->state)) {
                         $state = 0;
                    }
                    if ($rdinfo->rdinfo !== null) {
                        $rdinfo = $rdinfo->rdinfo;
                        $rdinfo->count--;
                        if (!isset($rdinfo->count)) {
                            die("count not found");
                        }
                    } else {
                        $state = 0;
                    }
                    $gg++;
                    if ($gg > 20) {
                        var_dump($rdinfo);
                        die("bad thing");
                    }
                } while ($rdinfo && isset($rdinfo->count) && ($rdinfo->count >= 0));
            } else if ($state) {
                $state = 0;
            }
            $old = array_pop($style_states);
           
            
            if ($old && ($old != $ctype)) {
                if ($s_output === null) {
                    $s_output = [(object)[
                        "data" => $s, // copy object data
                        "type" => $ctype,
                        "style" => $cstyle,
                    ]];
                }else {

                    $ctype = $old;
                    $cstyle = array_pop($styles_t);
                    $outdata = "";
                    $nt = (object)[
                        "data" => $outdata,
                        "type" => $ctype,
                        "style"=>$cstyle,
                    ];

                    
                    $s_output[] = $nt;
                }
                $s = &$nt->data;
            }
        }
        //clear 
        while ($rdinfo && $rdinfo->tagname) {         
            $rdinfo = $rdinfo->rdinfo;
        }

        if (($s_output === null) && !empty($s)) {
            $s_output = [(object)[
                "data" => $s,
                "type" => $ctype,
                "style"=>$cstyle
            ]];
        }
        return $s_output;
    }
}
