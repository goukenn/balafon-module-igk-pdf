<?php
namespace igk\pdf\Engine;

use FPDF;
use IGKHtmlItem;


abstract class PDFNodeBase extends IGKHtmlItem{
    /**
     * tag name to display in html renderging context
     * @var mixed
     */
    protected $html_tagname;

    protected static function _GetTagName($node)
    {
        if ($node instanceof PDFNodeBase) {
            if (!empty($c = $node->html_tagname)) {
                return $c;
            }
        }
        return $node->getTagName();
    }

    protected function __construct($tag=null){
        if ($tag==null){
            $tag = "pdf-".str_replace("\\", "-", strtolower(static::class));
        }
        parent::__construct($tag);
        $this->setTempFlag("RootNS",[PDFDocument::class, "CreateWebNode"]);
    }
    public function getTagName()
    {
        if (!empty($c = $this->html_tagname)) {
            return $c;
        }      
        return parent::getTagName();
    }
}