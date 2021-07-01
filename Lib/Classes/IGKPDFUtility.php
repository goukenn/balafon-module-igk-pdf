<?php

namespace igk\pdf;

use \IGKColor;

class IGKPDFUtility{
	private static function GetId(& $counter, $n){
		if (!isset($counter[$n])){
			$counter[$n] = 0;
		}
		$counter[$n] =  $counter[$n]+1;
		return $n."-".$counter[$n];
		
	}
	public static function SVGDecodeStream($svg, $data,  $w, $h,$resolver, $action_listener=null){
		$defs= $svg->add("defs");
		$defs->setCallback("getIsVisible", function()use($defs){
			return $defs->HasChilds;
		});

		
 
	}
	public  static function DecodeStream($data,$resolver, $action_listener=null){
		$state = 0;
		$points = [];
		$cpoint = (object)["x"=>0, "y"=>0];	// current point
		$spoint = (object)["x"=>0, "y"=>0]; // start point  
		
		$fillmode = "event-odd";
		$fill = "#000000"; // fill color
		$stroke = "none";
		$tolerance = 1.0;
		$pathdef = "";
		$data = str_replace("\n"," ",$data);
		$tbase = [["pos"=>0, "data"=>$data]];
		
		 $clipid = "";
		 $id_counter = [];
		 $clips = [];
		 $clip_id = ""; 
		 $text_move_pos = [0, 0 ];
		 $text_font_size = 12;
		 $debug = 0;
		$loop = 0;
		$max = 10;
		$cmap = [];
		$wline = 0;
		while($rdata = array_pop($tbase)){ 
			$t =  $rdata["data"];
			$pos = $rdata["pos"];	
			$ln = \strlen($t);
			$s = "";
		 
			$wline++;
			// igk_wln("data : ".$t."<br />");
				for($i= $pos; $i < $ln; $i++){ 
					$ch = $t[$i];  
					if ($i!=$loop){
						$loop = $i;
						$max = 10;
					}else {
						$max--;
						if ($max<=0){
							igk_wln_e("infinite loop detected ".$i);
						}
					}
					switch($ch){
						case "<":
						case ">":
						case "[":
						case "]":
						case "(":
						case ")": 				
						case " ":
							$s = trim($s);
							$p_added = 0;
							if (strlen($s)>0){
								if (is_numeric($s)){ 
									$points[] = $s;
									$p_added = 1;
								}else{					 
									if ($s[0] == "/"){
										$points[] = $s; 
										$p_added = 1;
									}  
								}
								
								
								if ($p_added){
									$s = "";
									continue 2;
								} else {
									if ($ch == "["){
										// igk_wln("read array:".$s, $points);
										// $debug = 1;
										$i--;
									}
									if ($ch == "<"){
										// igk_wln("read array symbol : ".$s, $points);
										$i--;
									} 
									if ($ch == "("){
										// igk_wln("read array symbol : ".$s, $points);
										$i--;
									} 
								} 
							} else { 
								// if ($debug){
								// 	igk_wln("debug: ", $points , $ch,
								// 	\igk_str_read_brank($t, $i, "]", "[" ),
								// 	$t[$i]
								// 	);
								// }
								if ($ch == "<"){
									$m_data = \igk_str_read_brank($t, $i, ">", "<" ); 
									$points[] = $m_data;
									$s = "";
									$i--;
									continue 2;
								}
								if ($ch == "["){
									$m_data = \igk_str_read_brank($t, $i, "]", "[" ); 
									$points[] = $m_data;
									$i--;
									$s = "";
									continue 2;
								}
								if ($ch == "("){
									$m_data = \igk_str_read_brank($t, $i, ")", "(" ); 
									$points[] = $m_data;
									$i--;
									$s = "";
									continue 2;
								}
							}
						break;
		
						default:
							$s.=$ch;
							continue 2;
						break;
					}
					if (empty(trim($s)))
						continue;
		
					$action = 1;
					// igk_wln("action:::".$s);
					if ($action_listener){ 
						if (!$action_listener($s, $points)){
							die(__FILE__.':'.__LINE__. ": action not handled [".$s."]".strlen($s));
						}
					}
					switch($s){
						case "rg": 
							list($r,$g,$b) = $points;
							$fill = (new IGKColor($r,$g,$b, 255))->toWebColor();
							$points = [];
						break;
						case "RG": 
							list($r,$g,$b) = $points;
							$stroke = (new IGKColor($r,$g,$b, 255))->toWebColor();
							$points = [];
							break;
						case "BT": //BEGIN TEXT
							if (igk_getv($rdata, "mode") == "BT"){
								die("embed begin text not allowed"); 
							}  
							$rdata["mode"] = "BT"; 
							break;
						case "ET": // END TEXT
							if (igk_getv($rdata, "mode") != "BT"){
								die("end text found not in BT mode"); 
							}  
							$rdata["mode"] = "";
							break;
						case "Td": // move text position
							$text_move_pos = $points;
							$points = []; 
							break;
						case "Tf":// set font size
							$fdesc = "";					
							$terror = 0;
							if (count($points) >= 1){
								if ((count($points)>1) && ($points[0][0]=="/")) {
									$fdesc = $points[0];
								}else{ 
									$terror = 1;
								}
							}
							if ($terror){
								die("font size not valid");
							}
							if ($fdesc){
								$cmap = $resolver->resolveResources($fdesc);
							}
							$text_font_size = $points[count($points) - 1];
							$points = [];
							break;
						case "Tj":// show text
							$s = $points[0];
							if ($s[0] == "<"){ // char code resolution
								$s = self::DecodeString(substr($s, 1, -1), $cmap); // array of tchr 						 
							}else if ($s[0]=="("){
								$s = substr($s, 1, -1); // direct string
							} 
							// igk_wln("Tj: show text:".$s);
						break;
						case "TJ": // show array by positionning
							$s = trim($points[0]);
							if ($s[0] == "["){ // char code resolution
								$s = substr($s, 1, -1); // array of tchr 
								$st = "";
								$s = self::ReadTJ($s, $cmap,$st);
							}
							// igk_wln("show text array:", $s);
							$points = [];
						break;
						case "q":
							$clip_id = null;							 
							$state++;					
							array_unshift($clips , (object)["items"=>[], "def"=>"", "node"=>null, "id"=>$clip_id]);
							break;
						case "Q":
							$objs = array_shift($clips);							 
							$state--;
							$pathdef = "";				 
							break;
						case "h":
						 
							$pathdef .= "Z ";
							 
							break;
						case "l":
							$cpoint->x = $points[0];
							$cpoint->y = $points[1]; 
							$pathdef .= "L ".$cpoint->x ." ".$cpoint->y ." ";
							// igk_wln_e("move l to ", $points, $pathdef, $cpoint);
							$points = [];
							break;
						case "m":
						
							// igk_wln_e("data: start new ".$pathdef);
							$cpoint->x = $points[0];
							$cpoint->y = $points[1]; 
							$spoint->x = $points[0];
							$spoint->y = $points[1]; 
							
							// igk_wln("start : ", $cpoint);
							$pathdef .= "M ".$cpoint->x. " ".$cpoint->y." ";
							$points = [];
							break;
						case "W*":
							$clip_fillmode = "event-odd";
							break;
						case "W":
							$clip_fillmode = "";
							break;
						case "w":
							if (count($points)!=1){
								igk_wln_e("not a points", $points);
							}
							$line_stroke_width = $points[0]; 
							$points = [];
							break;
						case "i": 
							$tolerance = $points[0];
							$points = [];
							break;
						case "f":
							$fillmode = ""; 
							$points = [];
							break;
						case "f*":
							$fillmode = "event-odd";
							break;
						case "n":
							// ("end path without filling or stoking: n", $points);
							if (!empty(trim($pathdef))){
								if (count($clips)>0){
									$clips[0]->def .= $pathdef;
								}else {
									igk_wln_e("not in a clips");
								}
							// // $clip = $o->add("clipPath");
								// // $clip_id = self::GetId($id_counter,  "clip");
							// // array_push($clips, $clip_id);
							
							// $clip["id"] = $clip_id;
							// $clip->add("path")->setAttribute("d", trim($pathdef))
								// ->setAttribute("fill-rule", $fillmode)
								// ->setAttribute("transform", "matrix(1,0,0,-1,0,$w)");			
							}
							// $o = $o->add("g")->setAttribute("clip-path", "url(#{$clip_id})");
							$points = [];
							$pathdef = "";
							break;
						case "cs":					
							if (count($points)==3){
								list($r,$g,$b) = $points;
								$stroke = (IGKColor::FromFloat($r,$g,$b,1))->toWebColor();
							}else {
								// igk_wln("non border space", $points);
							}
							$points = [];
							break;
						case "sc":
							// igk_wln("set color: ", $points);
							if (count($points)==3){
								list($r,$g,$b) = $points; 
								$fill = (IGKColor::FromFloat($r,$g,$b, 1))->toWebColor();
							}
							$points = [];
							break;
						case "re": //rectangle
							list($x, $y, $width, $height) = $points;
							// $d = $o;
							// if ($clip_id){
								// $d = $d->add("g")->setAttribute("clip-path", "url(#".$clip_id.")");
							// }
							// $d->add("rect")->setAttributes(compact("x","y","width", "height"))
							// ->setAttribute("fill", $fill)
							// ->setAttribute("stroke", $stroke)
							// ->setAttribute("transform", "matrix(1,0,0,-1,0,$w)");
							// $pathdef.="M $x $y L ".($x +$w) ." ".$y." L ".($x +$w) . " ".($y +$h)." Z";
							$clips[0]->items[] = ["t"=>"rect",  "properties"=>array_merge([], compact("x","y","width", "height"))];
							$points = [];					
							break;
						// case "/Cs2":
							// igk_wln_e("c2 handle , ", $points);
							// break;
						case "Do":
							//
							$ack = implode(" ", $points);
							$points = [];
							$m = $resolver->resolvObject($ack);
							array_push($tbase, ["pos"=>$i+1, "data"=>$rdata["data"]]);
							array_push($tbase, ["pos"=>0, "data"=>str_replace("\n", " ", $m)]); 
							break 2;
						case "c":
							igk_wln("curve to", $points );
							$points = [];	
							break;
						case "cm":
							list($ma, $mb, $mc, $md, $me, $mf) = $points;
							// igk_dev_wln("concat matrix", $points );
							$points = [];	
							break;	
						case "S":
							// igk_dev_wln("stroke path", $points);
							$points = [];					
							break;	
						case "Tm":
							igk_wln("set text matrix");
							$points = [];
						break;
						case "j":
							igk_wln("set text line join");
							$points = [];
						break;	
						case "J":
							igk_wln("set text line cap");
							$points = [];
						break;			 			 
						default:
							$action = 0; 
						break;
					}
		
					if (!$action){
						igk_wln(__FILE__.':'.__LINE__, "action not handled : ", $s);
					}else{
						$s=""; 
					}
					 
				}
		
				}
	}
	public static function DecodeString($s, $cmap){
		$tt = "";
		$ttx = "";
		foreach(str_split($s, 2) as $tch){
			if ($cmap){
				$tt .= unicodeString('\u'.$cmap[$tch].'');
			}else {
				$tt .= chr(hexdec("0x".$tch)); 
			}
		} 
		return $tt;
	}
	public  static function ReadTJ($txt, $cmap=null, & $string=null){
		$data = [];
		$string ="";
		$rmap = 0;
		$k = "";
		for($pos = 0 ; $pos < strlen($txt); $pos++){
			$ch = $txt[$pos];
			switch($ch){
				case "<":
					if ($rmap==1){
						$data[] = $k;
						$k = "";

					}
					$tpos=strpos($txt, ">", $pos);

					$n = self::DecodeString(substr($txt, $pos+1, $tpos - $pos-1), $cmap);
					$data[] = $n;
					$string .=$n;
					if ($rmap==0){ 
						$rmap = 1;
					} 
					if ($tpos>$pos)
						$pos = $tpos;
					else{
						die("tpos die: ".$tpos);
					} 
					break;
				default:
					$k.=$ch;
					break;
			}
		
		}
		if (strlen($k)>0){
			$data[] = $k;
		} 
		return $data;
	}
}


function unicodeString($str, $encoding=null) {
    if (is_null($encoding)) $encoding = ini_get('mbstring.internal_encoding');
    return preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/u', function($match)use($encoding){
		return mb_convert_encoding(pack("H*", $match[1]), $encoding, "UTF-16BE");
	}, $str);
}