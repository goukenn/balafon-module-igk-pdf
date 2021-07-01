<?php


namespace igk\pdf;
use igk\svg\IGKSvgDocument;
use \IGKListener;

class IGKPDFO{
	static $sm_errors;
	var $version;
	var $resources;
	var $pages;
	var $FontDescriptor;
	var $cmaps;
	var $page;
	
	private function __construct(){
		$this->resources = [];
		$this->pages = (object)array_fill_keys(["mediaBox"], null);
		$this->FontDescriptor = [];
		$this->cmaps = [];
		$this->page = [];
	}
	
	public static function GetErrors(){
		$t = self::$sm_errors;
		self::$sm_errors = [];
		return $t;
	}
	public static function LoadContent($content){
		$tfile=tempnam(sys_get_temp_dir(), "igk");
		file_put_contents($tfile, $content);
		$out =  self::Load($tfile);
		@unlink($tfile);
		return $out;
	}
	public static function Load($filename){
	 
		self::$sm_errors = [];
		if (!file_exists($filename)){
			self::$sm_errors[] = "file not exists";
			return null;
		}
		$hfile = fopen($filename, "r+");
		if (!$hfile)
			return null;
		
		//read pdf header
		$s = fread($hfile, 4);
		if ($s!="%PDF"){
			self::$sm_errors[] = "not a pdf file";
			return null;
		} 
		$pdfo = new IGKPDFO();
		$pdfo->version = substr(self::_read_line($hfile), 1);
		$m = self::_read_line($hfile);
		if ($m[0]=="%"){
			$m = substr($m, 1);
		}
		$obj_id = "";
		// $count = 0;
		$stream=0;
		$v = "";
		self::ReadObjectData($pdfo, $hfile, null, 0);
		// self::ReadObject($pdfo, "11 0", $hfile);
		// while(!feof($hfile) && (( $m = self::_read_line($hfile)) !==null)){
		// 	// igk_wln("m:".$m);
		// 	switch($m){
		// 		case "%%EOF": // end of pdf file					
		// 			break 2;
		// 		case "stream":
		// 				$stream=1;
		// 			break;
		// 		case "endstream":
		// 				$stream=0;
		// 				$sm = $pdfo->resources[$obj_id]->stream ;
		// 				if (strlen($sm)>0 && ($sm[0] == "x")){
		// 					$pdfo->resources[$obj_id]->stream = gzuncompress($sm);
		// 				}
		// 			break;
		// 		case "endobj":
		// 			$pdfo->resources[$obj_id]->value = $v;
		// 			self::_treat_value($pdfo, $pdfo->resources[$obj_id]);
		// 			$v="";
		// 			break;
		// 		default:
		// 			if (strrpos($m, " obj", -1)!==false){
		// 				$obj_id = substr($m, 0, -4); 
		// 				$pdfo->resources[$obj_id]= (object)[];
						
		// 			}else {
		// 				if ($stream){
		// 					if (!isset($pdfo->resources[$obj_id]->stream))
		// 						$pdfo->resources[$obj_id]->stream = $m;
		// 					else 
		// 						$pdfo->resources[$obj_id]->stream .= "\n".$m;							
		// 				}
		// 				else {
		// 					if (strlen($v)>0)
		// 						$v.="\n";							
		// 					$v .= $m;
		// 				}
		// 			}
		// 			break;
		// 	}
		// 	// if ($count>200)
		// 	// 	break;
		// 	// $count++;
		// } 
		fclose($hfile);
		return $pdfo;
	}
	private static function ReadObjectData($pdfo, $hfile, $obj_id=null, $outobj=1){
	 
		// $count = 0;
		$stream=0;
		$v = "";
		if (($obj_id!=null) && !isset($pdfo->resources[$obj_id]))
		$pdfo->resources[$obj_id] = (object)[
			"id"=>$obj_id
		];

		while(!feof($hfile) && (( $m = self::_read_line($hfile)) !==null)){
			// igk_wln("m:".$m);
			switch($m){
				case "%%EOF": // end of pdf file					
					break 2;
				case "stream":
						$stream=1;
					break;
				case "endstream":
						$stream=0;
						$sm = $pdfo->resources[$obj_id]->stream ;
					
						if (strlen($sm)>0 && ($sm[0] == "x")){
							$pdfo->resources[$obj_id]->stream = gzuncompress($sm);
						} 
					break;
				case "endobj":
					$pdfo->resources[$obj_id]->value = $v;
					self::_treat_value($pdfo, $pdfo->resources[$obj_id]);
					$v="";
					if ($outobj)
						return $pdfo->resources[$obj_id];
				//continue reading
				break;
				default:
					if (strrpos($m, " obj", -1)!==false){
						$obj_id = substr($m, 0, -4); 
						$pdfo->resources[$obj_id]= (object)[];
						
					}else {
						if ($stream){
							if (!isset($pdfo->resources[$obj_id]->stream))
								$pdfo->resources[$obj_id]->stream = $m;
							else 
								$pdfo->resources[$obj_id]->stream .= "\n".$m;							
						}
						else {
							if (strlen($v)>0)
								$v.="\n";							
							$v .= $m;
						}
					}
					break;
			}
			// if ($count>200)
			// 	break;
			// $count++;
		} 
		
	}
	private static function ReadObject ($pdfo, $objid, $hfile){
		$s = $objid." obj"; 
		\fseek($hfile, 0);
		while(!feof($hfile) && (( $m = self::_read_line($hfile)) !==null)){
			if ($s == $m){
				$obj = self::ReadObjectData($pdfo,  $hfile, $objid); 
				break;
			}
		}
	}
	private static function _read_line($hfile){
		$s = null;
		while(!feof($hfile) && (($h = fread($hfile, 1))!==false)){ 
			if ($h=="\n"){
				if ($s === null)
					$s = "";
				break;
			}
			$s.=$h;
		} 
		return $s;
	}
	private static function _treat_value($pdfo, $obj){
		$s = $obj->value;
		$pos = 0;
		$ln = strlen($s);
		$v = "";
		$def = (object)[];
		$array_level = 0;
		$obj_level = 0;
		$read = []; 
		$lpos = $pos;
		$clpos = $pos;
		for (;$pos< $ln;$pos++){
			$ch = $s[$pos]; 
			if ("\n"==$ch)
				$ch = " ";
			// if ($debug == 2){
			// 	igk_wln("line breaking .... ".$ch, $v);

			// }
			// if ($lpos != $pos){
			// 	$lpos = $pos;
			// 	$clpos = 0;
			// }else {
			// 	$clpos++;
			// 	if ($clpos>10){
			// 		igk_wln_e("poschanged: infinit detection ".$pos);
			// 	}
			// }

			if (!empty(trim($v)) && \preg_match("/[\/\<\>\[]/", $ch)){
				switch(trim($v.$ch)){
					case "<<":
					case ">>";
					break;
					default:
						$ch = " ";
						$pos--;
						$debug = 2; 
					break;
				}
			
			}
			switch($ch){   
				case "[": // start array
					$array_level++;
					$v="";
					array_unshift($read, (object)["type"=>2,"prop"=>[]]);
				continue 2;
				case "]": // end array
					if (!empty($v))
						$def->array[] = $v;
					array_shift($read);
					if (count($read)== 0){
						if (isset($def->value))
							die("value already set");
						$def->value = $def->array;
					} else{
						if(($c = count($read[0]->prop))>0){
							// $def->array[$def->array[$c]] = $def->array;
							$read[0]->prop[] = $def->array;
							// unset($def->options[$c-1]);
							//igk_wln_e($def->options[$c-1]);
						}
					}
					unset($def->array);
					//igk_wln_e("end array", $c, $read[0], $def->array);
					$array_level--;
					$v="";
					
				continue 2;
				case "(": // end array
						$tm = "";
						$pos++;
						while($pos< $ln) {
							$ch = $s[$pos];
							if ($ch==")"){
								break;
							}
							$pos++;
							$tm .= $ch;
						}
						$def->text = $tm;
					 
					 $v="";
				continue 2;
				case " ": // symbol that end data 
				// case ">": 
				// case "/":
					// igk_wln("attach=".$v." for [".$ch."]\n");
					//$append_properties
					$_on = 0;  

					if ((count($read)> 0 )  && (strlen($v)!=0)){
						if ($read[0]->type ==1){							
							if ($v=="R"){
								if (($p = count($read[0]->prop))>2){
									$prop = $read[0]->prop[$p-3];
									$value =  sprintf("r:%s %s", $read[0]->prop[$p-2], $read[0]->prop[$p-1]);
									unset($read[0]->prop[$p-1]);
								    unset($read[0]->prop[$p-2]);
									$read[0]->prop[] = $value;
									$read[0]->prop = array_values($read[0]->prop);
									$_on = 1;
									
									$prop = null;
								}
							}
							if (!$_on)
								$read[0]->prop[] = $v;		
						}else{
							if ($v=="R"){
								$_ht = & $def->array;
								if (($p = count($_ht))>2){
									// $prop = $_ht[$p-3];
									$value =  sprintf("r:%s %s", $_ht[$p-2], $_ht[$p-1]);
									unset($_ht[$p-1]);
								    unset($_ht[$p-2]);
									$_ht[] = $value;
									$def->array = array_values($_ht);
									$_on = 1; 
									$prop = null;
								}
								else if (($p = count($_ht))>1){
									
									$value =  sprintf("r:%s %s", $_ht[$p-2], $_ht[$p-1]);
									unset($_ht[$p-1]);
								    unset($_ht[$p-2]);
									$_ht[] = $value;
									$def->array = array_values($_ht);
									$_on = 1; 
									$prop = null;
								}
							}
							if (!$_on)
							 $def->array[] = $v;
						}
					} 

					// if ($ch != " "){
					// 	if (!empty($v) && $operand) 
					// 		$v = "";

					// 	if ($debug)
					// 		igk_wln("breaking :".$v." :for: ".$ch."\n", "level: ".$obj_level."\n");
					// 	break;
				 	// } 
					$v ="";
					continue 2;
				break;
			}
			
			$v.= $ch; 
	 
			switch(trim($v)){
				case "<<":
					$v = "";
					$obj_level++;
					array_unshift($read, (object)["type"=>1,"prop"=>[]]);
				break;
				case ">>": 
					$v = "";
					$obj_level--;
					// if ($debug)
					// 		igk_wln("reduce:::::::::::::::::::::::".$v." :for: ".$ch."\n", "level: ".$obj_level."\n");
					
					if ($obj_level===0){
						$def->options = & $read[0]->prop;
					}else {
						$c = $read[0]->prop;
						$read[1]->prop[] = $c;
					}
					array_shift($read);
				break;
			}
		}
		if (($array_level != 0) || 
			($obj_level != 0)){  
				igk_wln_e("Error: defnition not match [$obj_level ; $array_level] : $s "," last v : ".$v, "c:".$c, $read);
				return;
			} 
		if (!empty((array)$def)){
			$obj->def = $def;
			// ne pas imprimer les object 
			// build info media box
			if (isset($def->options)){
				$tab=  $def->options;
				
				switch($def->options[0])
				{
					case "/Type":
						switch($def->options[1]){
							case "/Pages":								 
								 $pdfo->pages->mediaBox = self::_resolv($pdfo, $tab, "/MediaBox");
								 $pdfo->pages->count = self::_resolv($pdfo, $tab, "/Count");
								break;
							case "/Page":
								 $obj = (object)[];
								 $obj->mediaBox = self::_resolv($pdfo, $tab, "/MediaBox");
								 $obj->contents = self::_resolv($pdfo, $tab, "/Contents");
								 $obj->Resources = self::_resolv($pdfo, $tab, "/Resources");						 
								 $pdfo->page[] = $obj; 
								break;
							case "/FontDescriptor":
								$obj = (object)[];

								//igk_wln_e("font ", 	$tab);	
								$obj->fontName = self::_resolv($pdfo, $tab, "/FontName");
								$obj->italicAngle = self::_resolv($pdfo, $tab, "/ItalicAngle");	
								$obj->Ascent = self::_resolv($pdfo, $tab, "/Ascent");	
								$obj->Descent = self::_resolv($pdfo, $tab, "/Descent");	
								$obj->FontFile2 = self::_resolv($pdfo, $tab, "/FontFile2");	
										 
								$pdfo->FontDescriptor[] = $obj;
							break;
						}
					break;
				}
				if (isset($obj->value) && (strpos($obj->value, "/XObject")!==false) ){
					
					$o = self::_resolv($pdfo, $tab, "/XObject");
					$pdfo->XObjects[] = $o;
				}

				//debug 
				// igk_dev_wln(__FILE__.':'.__LINE__,  $pdfo);
			}
			
		}
	}
	private static function _resolv($pdfo, $tab, $name){
 
			if (($index =array_search($name, $tab))!==false){
				$s = $tab[$index+1];
				if (is_string($s)){
					if (preg_match("/^r:/", $s)){
						$s = substr($s, 2);
						return function() use ($pdfo,$s){							
								return igk_getv($pdfo->resources, $s);
						};
					}					
				}
				return $s;
			}
			return null;
	}
	private static function _get_res($pdfo, $tab, $name){
		if (($index =array_search($name, $tab))!==false){
			$s = $tab[$index+1];
			if (is_string($s)){
				if (preg_match("/^r:/", $s)){
					$s = substr($s, 2);
			 		 return igk_getv($pdfo->resources, $s);
				 
				}					
			}
			return $s;
		}
		return null;
	}
	private static function _get_value($fc){
		if (is_callable($fc)){
			return $fc();
		}
		return $fc;
	}
	private function resolvResources($ac){

	}
	
	public function ExtractSVG($page=null){ 
		$tpage = igk_getv( $this->page, $page-1);
		if (!$tpage){
			return null;
		}
		list($x, $y, $w, $h) = $tpage->mediaBox; 
		$svg = new IGKSvgDocument(); 
		$svg["width"] = ($w*2)."pt";
		$svg["height"] = ($h*2)."pt";
		$svg["viewBox"] = "$x $y $w $h"; 

		$fc = self::_get_value($tpage->contents);
		$resolver = $this->CreateResolver($tpage); 
		if (isset($fc->stream)){
			if (strpos($fc->value ,"/FlateDecode")){
				//
				// good decoding 
				// 
				
				$converter = new IGKPDF2SVG($svg,$w, $h);
				IGKPDFUtility::DecodeStream($fc->stream, $resolver , [$converter, "Visit"]); 
			} 
		}  

		// foreach($this->page as $p){
		// 	list($x, $y, $w, $h) = $p->mediaBox;
		// 	$svg["width"] = $w."pt";
		// 	$svg["height"] = $h."pt";
		// 	$svg["viewBox"] = "$x $y $w $h"; 		 
		// 	$fc = self::_get_value($p->contents);
		// 	$listener = $this->CreateResolver($p);
			
		// 	if (isset($fc->stream)){
		// 		if (strpos($fc->value ,"/FlateDecode")){
		// 			// good decoding 
		// 			IGKPDFUtility::SVGDecodeStream($svg, $fc->stream, $w, $h, $listener );
		// 			break;
		// 		}
				
		// 	}
			
			 
		// }
		return $svg->render();
	}	 
	private function CreateResolver($tpage){
		$listener = new IGKListener(); 
		$p = $tpage;
		$listener->register("resolveResources", function($ac)use($p){
			$m = "";
			if ($mp = igk_getv($this->cmaps, $ac)){
				return $mp;
			}

			if (isset($p->Resources)){
				$m = self::_get_value( $p->Resources);// self::_resolv($this,, $ac));
				if ($m){
					$opts = self::_get_value(self::_resolv($this,  $m->def->options, "/Font"));
					$m2 = self::_get_value(self::_resolv($this, $opts->def->options, $ac));
					if ($m2){
						if (is_callable($m2)){
							igk_wln("m2 is a callable");
						}else{
						 
							$g = self::_get_res($this, $m2->def->options, "/ToUnicode");
							if (!$g){
								igk_die("not support to unicode char map: ".$ac);
							}
						
							$decode_data = [];
							$bc = "beginbfchar";
							(($c = strpos($g->stream, $bc)) === false) && die("beginbfchar not found ".$ac);
							$ec = strpos($g->stream, "endbfchar", $c);
							$txt = substr($g->stream, $c+ strlen($bc), $ec);
							$rmap = 0;
							$k = "";
							for($pos = 0 ; $pos < strlen($txt); $pos++){
								$ch = $txt[$pos];
								switch($ch){
									case "<":
										$n = substr($txt, $pos+1, $tpos=strpos($txt, ">", $pos+1) -$pos-1);
										if ($rmap==0){
											$k = $n;
											$rmap = 1;
										}else {
											$rmap = 0;
											$decode_data[$k] = $n; 
										}
										if ($tpos>$pos)
											$pos = $tpos; 
										break;
								}
							}
							// while(preg_match("/(\<(?P<n>[0-9]+)\>)\s+(\<(?P<v>[0-9]+)\>)/", $g->stream, $tab, PREG_OFFSET_CAPTURE, $c )){

							// 	$decode_data[$tab["n"][0]] = $tab["v"][0];
							// 	$c = $tab[0][1] + strlen($tab[0][0]); 
								 
							// }
							$this->cmaps[$ac] = $decode_data;
							// igk_wln_e( count($decode_data));
							return $decode_data;
						}
					}
				} 
			}
		});
		$listener->register("resolvObject", function($ac){
			
			foreach($this->XObjects as $t){
				$m = self::_get_value(self::_resolv($this, $t, $ac));
				// if ($ac=="/Fm1"){
					// igk_wln("normal : ".$m->stream);
					// // return "q Q q 0 48 m 12 48 l 12 36 l 0 36 l 0 48 l h 0 48 m W* n 0 0 48 48 re W n";
					// return "q Q q 0 48 m 12 48 l 12 36 l 0 36 l 0 48 l h 0 48 m W* n 0 0 48 48 re W n 0.6 i /Cs2 cs 0.2059415 0.2059837 0.2059389 sc -5 53 m 17 53 l 17 31 l -5 31 l h f Q";
				// }
				if ($m){
					return $m->stream;
				}
			}
			
		});
		return $listener;
	}
	public function ExtractText(){

	}
	public function ExtractImage(int $page = 1, $format="Png"){
		$tpage = igk_getv( $this->page, $page-1);
		if (!$tpage){
			return;
		}
		require_once(IGK_LIB_DIR."/igk_gd.php");
		list($x, $y, $w, $h) = $tpage->mediaBox; 
		$gd = \IGKGD::Create($w, $h);
		$gd->Clearf((object)["R"=>1.0, "G"=>1.0, "B"=>1.0]);
		$fc = self::_get_value($tpage->contents);
		$resolver = $this->CreateResolver($tpage); 
		if (isset($fc->stream)){
			if (strpos($fc->value ,"/FlateDecode")){
				//
				// good decoding 
				// 
				
				$converter = new IGKPDG2GD($gd,$w, $h);
				IGKPDFUtility::DecodeStream($fc->stream, $resolver , [$converter, "Visit"]); 
			} 
		}  

		return $gd->RenderText();
	}
}
