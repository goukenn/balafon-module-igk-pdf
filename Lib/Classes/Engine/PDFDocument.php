<?php

namespace igk\pdf\Engine;

use FPDF;
use IGKHtmlItem;
use ReflectionClass;

class PDFDocument extends PDFNodeBase
{
    const NS = "//schema.igkdev.com/pdfdocument/1.0";

    protected $html_tagname = "div";

    public function __construct()
    {
        parent::__construct("igk-pdf-document");
        $this->setAttribute("xmlns", self::NS);
    }
    public function render_ouput()
    {
        $f =  new FPDF("P", "mm", "A4");
        self::Builder($this, $f);
        $f->output();
    }
    public static function Builder($node, $fpdf)
    {
        $theme = $node->Theme;
        $options =  igk_createobj([
            "Engine" => new PDFRendererEngine($fpdf, $theme)
        ]);
        igk_html_render_node($node, $options);
    }

    public static function CreateElement($name, $param = null)
    {
        igk_wln_e("create element to implement");
    }
    public static function CreateWebNode($name, $attributes = null, $indexOrArgs = null)
    {
        if (class_exists($cl = __NAMESPACE__."\\PDF".$name."Node") && !(new ReflectionClass($cl))->isAbstract()){
            if ($indexOrArgs==null){
                $indexOrArgs = [];
            }
            $o = new $cl(...$indexOrArgs);
            if ($attributes)
            $o->setAttribute($attributes); 
            return $o;
        }         
        $o = IGKHtmlItem::CreateWebNode($name, $attributes, $indexOrArgs);
        $o->setTempFlag("RootNS", [self::class, __FUNCTION__]);
        return $o;
    }
}



