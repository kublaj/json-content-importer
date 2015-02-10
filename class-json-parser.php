<?php
/*
CLASS JsonContentParser
Description: Basic template engine Class: building code with JSON-data and template markups 
Version: 1.1.0
Author: Bernhard Kux
Author URI: http://www.kux.de/
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/


class JsonContentParser {

    /* shortcode-params */		
		private $jsondata = "";
    private $datastructure = "";
    private $basenode = ""; 
    private $numberofdisplayeditems = -1; # -1: show all
    private $oneOfTheseWordsMustBeIn = "";
    
    /* internal */
    private $showDebugMessages = FALSE; # set TRUE in constructor for debugging
    private $triggerUnique = NULL;
    private $subLoopParamArr = NULL;
    private $regExpPatternDetect = "([a-zA-Z0-9,;\_\-\:\,\<\>\/ ]*)";

		public function __construct($jsonData, $datastructure, $basenode, $numberofdisplayeditems, $oneOfTheseWordsMustBeIn){  
      #$this->showDebugMessages = TRUE; # sometimes helpful
      if (is_numeric($numberofdisplayeditems)) {     
        $this->numberofdisplayeditems = $numberofdisplayeditems;
      }
      $this->oneOfTheseWordsMustBeIn = $oneOfTheseWordsMustBeIn;
      $this->jsondata = $jsonData;
      $this->datastructure = $datastructure;
      ##todo $this->datastructure = preg_replace("/\n/", "", $this->datastructure); # remove linefeeds from template
      $this->basenode = $basenode;
      $this->output = ""; #v105
		}
    
    /* retrieveDataAndBuildAllHtmlItems: get json-data, build html*/
		public function retrieveDataAndBuildAllHtmlItems(){
      $jsonTree = $this->jsondata;
      $baseN = $this->basenode;
      $this->debugEcho("<hr>basenode: $baseN<br>");
      if ($baseN!="") {
        $baseNArr = explode(".", $baseN);  # path of basenode: separator is "."
        foreach($baseNArr as $key => $valin) {
          $val = $valin;
          if (is_object($jsonTree)) {
            $jsonTree = $jsonTree->$val;
          } else if (is_array($jsonTree)){
           foreach($jsonTree as $jsonTreekey => $jsonTreeval) {
              if (is_object($jsonTreeval)) {
                $test = $jsonTree[$jsonTreekey]->$val;
                if (!is_null($test)) {
                  $jsonTree1 = $jsonTree[$jsonTreekey];
                }
              } else {
                # not implemented yet: uncool, but possible - why not another array
                $this->debugEcho("<hr>double-array at root? not implemented yet<hr>", "wordpressticket");
              }
            }
          } else {
            # neither object nor array? not implemented yet: should never happen
            $this->debugEcho("<hr>neither object nor array? not implemented yet<hr>", "wordpressticket");
          }
        }
      }
      
      $this->debugEcho("basic entry with: <i>".gettype($jsonTree)."</i><br>");
      
      # $jsonTree has to be object or array
      if (!is_object($jsonTree) && !is_array($jsonTree)) {
        $this->debugEcho("<hr>unsupported JSON-structure<hr>", "wordpressticket");
        exit;
      }

      # start parsing
      $startdepth = 0;
      $resultArr = $this->checkType($jsonTree, gettype($jsonTree), $this->datastructure, "", $startdepth, "", $this->numberofdisplayeditems);
      return $resultArr[1];
		}


     private function checkType($jsonIn, $type, $template, $node2check, $depth, $keyIn, $noofDisplayedItems=-1) {
        $result = "";
        $depth++;
        $counter = 0;
        $loopcounter = 0;

        $keypass .= $keyIn.".".$node2check;
        $keypass = preg_replace("/^\./", "", $keypass);
        $keypass = preg_replace("/\.$/", "", $keypass);
        $keypass = preg_replace("/\.\./", ".", $keypass);

        $this->debugEcho( "<hr><font color=blue>ENTER function checkType // depth: <i>$depth</i> // type: <i>$type</i> // keyIn: <i>$keypass</i> // node2check: <i>$node2check</i> // noofDisplayedItems: <i>$noofDisplayedItems</i> // template: <i>".htmlentities($template)."</i>");
        $this->debugEcho("<br> // json-in: ", "showdump", $jsonIn);
        $this->debugEcho( "</font><br><font color=green>start loop</font><br>");

        foreach($jsonIn as $key => $val) {
          $loopcounter++;
          if (is_object($val)) {
            $this->debugEcho( "object found: depth: <i>$depth</i> // loop: <i>$loopcounter</i> // key:  <i>$key</i> // type: <i>$type</i> // template: <i>".htmlentities($template)."</i> // node2check: <i>$node2check</i> // ");
            $this->debugEcho(" json in loop: ", "showdump", $val);
            if (is_numeric($noofDisplayedItems) && ($noofDisplayedItems>0) && is_numeric($key)) {
              $counter++;
              if ($counter > $noofDisplayedItems) {
                continue;
              }
            }
            if ($type=="array") {
              list($returnHTMLinsideProc, $resultOfProcessedObjects, $noofItems) = $this->checkType($val, "object", $template, "", $depth, $keypass);
              $noofFoundItems++;
              $result .= $resultOfProcessedObjects;
            } else if (is_numeric($key)) {
              $this->debugEcho( "num key:  <i>$key</i> // val: <i>".gettype($val)."</i><br>");
              if (is_object($val)) {
                list($returnHTMLinsideProc, $resultOfProcessedObjects, $noofItems) = $this->checkType($val, "object", $template, "", $depth, $keypass);
                $result .= $resultOfProcessedObjects; ## concat needed for locations-json
              }
            } else {
              list($subloopNodeObj, $subLoopNumberObj, $subloopTemplate, $keypassreturn) = $this->process_subloop($template, $key, $keypass);
              if ($subloopTemplate=="") {
                # no subloop: use template
                list($returnHTMLinsideProc, $resultFromSubloopprocessing, $noofItems) = $this->checkType($val, "", $template, $subloopNodeObj, $depth, $keypass);
                $template = $resultFromSubloopprocessing;

              } else {
                if ($key==$subloopNodeObj || is_numeric($key)
                ) {
                  list($returnHTMLinsideProc, $resultFromSubloopprocessing, $noofItems) = $this->checkType($val, "", $subloopTemplate, $subloopNodeObj, $depth, $keypass);
                  $returnHTMLinsideProc = $this->replace_subloop($resultFromSubloopprocessing, $subloopNodeObj, $subLoopNumberObj, $subloopHTMLObj, $template, $keypass);
                  $template = $returnHTMLinsideProc;
                  $result = $template;
                } else {
                  $this->debugEcho( "no match<hr>");
                }
              }
            }

          } else if (is_array($val)) {
            $this->debugEcho("array found: key: <i>$key</i> // template: <i>".htmlentities($template)."</i> <br>// ");
            $this->debugEcho("jsininarray: ", "showdump", $val);
            list($subloopNode, $subLoopNumber, $subloopTemplate) = $this->process_subloop_array($template, $key, $keypass); # check on {subloop-array}
            if ($subloopTemplate=="") {
              $this->debugEcho( "no {subloop-array}: loop array one by one<br>");
              foreach($val as $keynosubloop => $valnosubloop) {
                list($returnHTMLinsideProc, $resultFromSubloopprocessing, $noofItems) = $this->checkType($valnosubloop, gettype($valnosubloop), $template, "", $depth, $keypass);
                $result = $resultFromSubloopprocessing;
              }

              ##########################
            } else if ($key==$subloopNode) {
              $this->debugEcho( "subloopNode: ".$subloopNode." // no: ".$subLoopNumber." // html: ".htmlentities($subloopTemplate)."<br>");
              list($returnHTMLinsideProc, $resultFromSubloopArray, $noofItems) = $this->checkType($val, "array", $subloopTemplate, $subloopNode, $depth, $keypass, $subLoopNumber);
              if (preg_match("/{/", $resultFromSubloopArray)) {
                $resultFromSubloopArray = preg_replace("/{(.*)}/i", "", $resultFromSubloopArray);
              }
              $template = $this->replace_subloop_array($resultFromSubloopArray, $subloopNode, $subLoopNumber, $subloopTemplate, $template, $keypass);
              $result = $template;
            }
          } else if (is_string($val) || is_numeric($val)) {
            if (
              ($type=="array") && is_numeric($key) && ($key >= $noofDisplayedItems)
              ){
              continue;
            }
            $valout = $val;
            if (!$valout || is_null($valout)) {
              $valout = "";
            }
            if (mb_check_encoding($valout, 'UTF-8')) {
              $valout = htmlentities($valout, ENT_QUOTES, "UTF-8", FALSE);
            }
            $template = $this->replacePattern($template, $key, $valout);
            $result = $template;
          }
        }
        $this->debugEcho( "<hr><font color=red>LEAVE function checkType: result :<i>".htmlentities($result)."</i><br>// noofItems: <i>$noofItems</i> // returnHTMLinsideProc: <i>$returnHTMLinsideProc</i><br></font>");
        return array ($returnHTMLinsideProc, $result, $noofItems);
   }

    private function replace_subloop_array($result, $subloopNode, $subLoopNumber, $subloopStructure, $datastructure, $keypass) {
      $this->debugEcho("<br>REPLACE replace_subloop_array BEGIN: $subloopNode , keypass: $keypass<br>");
      $sli = '/{subloop-array:'.$subloopNode.':'.$subLoopNumber.'}(.*){\/subloop-array:'.$subloopNode.'}/i';
      $ret = preg_replace($sli , $result , $datastructure);
      $sli = '/{subloop-array:'.$subloopNode.':'.$subLoopNumber.'}(.*){\/subloop-array}/i';
      $ret = preg_replace($sli , $result , $ret);
      $this->debugEcho( "<br>REPLACE replace_subloop_array END <br>");
      return $ret;
    }


    private function replace_subloop($result, $subloopNode, $subLoopNumber, $subloopStructure, $datastructure, $keypass) {
      if ($keypass=="") {
        $re = $subloopNode;
      } else {
        $re = $keypass.".".$subloopNode;
      }
      $sli = '/{subloop:'.$re.':'.$subLoopNumber.'}(.*){\/subloop:'.$re.'}/i';
      $ret = preg_replace($sli , $result , $datastructure);

      $sli = '/{subloop:'.$re.':'.$subLoopNumber.'}(.*){\/subloop}/i';
      $ret = preg_replace($sli , $result , $ret);
      return $ret;
    }


    /* replacePattern: replace markup with data and do the specials like urlencode etc.*/
    private function replacePattern($datastructure, $pattern, $value) {
      $datastructure = str_replace("{".$pattern."}" , $value , $datastructure);
      $datastructure = str_replace("{".$pattern.":urlencode}" , urlencode(html_entity_decode($value)) , $datastructure);
      if (trim($value)=="") {
        $datastructure = preg_replace("/{".$pattern.":ifNotEmptyAdd:".$this->regExpPatternDetect."}/i" , '' , $datastructure);
        $datastructure = preg_replace("/{".$pattern.":ifNotEmptyAddRight:".$this->regExpPatternDetect."}/i" , '' , $datastructure);
        $datastructure = preg_replace("/{".$pattern.":ifNotEmptyAddLeft:".$this->regExpPatternDetect."}/i" , '' , $datastructure);
      } else {
        $datastructure = preg_replace("/{".$pattern.":ifNotEmptyAdd:".$this->regExpPatternDetect."}/i" , $value.'\1' , $datastructure);
        $datastructure = preg_replace("/{".$pattern.":ifNotEmptyAddRight:".$this->regExpPatternDetect."}/i" , $value.'\1' , $datastructure);
        $datastructure = preg_replace("/{".$pattern.":ifNotEmptyAddLeft:".$this->regExpPatternDetect."}/i" , '\1'.$value , $datastructure);
      }

      # a markup can be defined as unique: display only the FIRST data, ignore all following...
      $uniqueParam = '{'.$pattern.':unique}';
      if (preg_match("/$uniqueParam/", $datastructure)) {
    	   # there is a markup defined as unique
         $datastructure = str_replace("{".$pattern.":unique}", $value, $datastructure);
         $this->triggerUnique{$value}++;
         if ($this->triggerUnique{$value}>1) {
            return "";
         }
      }
      return $datastructure; # return template filled with data
    }

      private function process_subloop_array($datastructure, $callingKey, $keypass) {
      $rege = "([a-zA-Z0-9\_\-]*)";
      if (is_string($callingKey)) {
        #echo "extr arrsubl: $callingKey<br>";
        $rege = $callingKey;
        preg_match('/{subloop-array:'.$rege.':([\-0-9]*)}/', $datastructure, $subloopNodeArr);
        $subloopNode =$callingKey; # name of subloop-datanode
        $subLoopNumber = $subloopNodeArr[1];
        #echo  "extr found: $subloopNode<br>";
      } else {
        preg_match('/{subloop-array:'.$rege.':([\-0-9]*)}/', $datastructure, $subloopNodeArr);
        $subloopNode = $subloopNodeArr[1]; # name of subloop-datanode
        $subLoopNumber = $subloopNodeArr[2];
      }
      preg_match('/{subloop-array:'.$subloopNode.':'.$subLoopNumber.'}(.*){\/subloop-array:'.$subloopNode.'}/', $datastructure, $subloopStructureArr);
      $subloopStructure = $subloopStructureArr[1];
      if ($subloopStructure=="") {
        #  subloop-array not found, e.g. in closing-tag no subloopNode?
        preg_match('/{subloop-array:'.$subloopNode.':'.$subLoopNumber.'}(.*){\/subloop-array}/', $datastructure, $subloopStructureArr);
        $subloopStructure = $subloopStructureArr[1];
      }

      $subloopHTML = $subloopStructure;
      $this->debugEcho("subloop-array: ".htmlentities($datastructure)." node:$subloopNode  number:$subLoopNumber html:$subloopHTML<br>");
      return array ($subloopNode, $subLoopNumber, $subloopHTML);
    }

     private function process_subloop($datastructure, $callingKey, $keypass) {
      $rege = "([a-zA-Z0-9\_\-]*)";
      $regereturn = "";
      $this->debugEcho("process_subloop: $callingKey || $keypass<br>");
      if (is_string($callingKey)) {
        $rege = $callingKey;
        if ($keypass!="") {
          $rege = $keypass.".".$callingKey;
        }
        preg_match('/{subloop:'.$rege.':([\-0-9]*)}/', $datastructure, $subloopNodeArr);
        $subloopNode = $callingKey; # name of subloop-datanode
        $regereturn = $rege;
        $subLoopNumber = $subloopNodeArr[1];
        $this->debugEcho( "patt: ".'/{subloop:'.$rege.':'.$subLoopNumber.'}(.*){\/subloop:'.$rege.'}/'."<br>");
        preg_match('/{subloop:'.$rege.':'.$subLoopNumber.'}(.*){\/subloop:'.$rege.'}/', $datastructure, $subloopStructureArr);
        $subloopStructure = $subloopStructureArr[1];
      } else {
        preg_match('/{subloop:'.$rege.':([\-0-9]*)}/', $datastructure, $subloopNodeArr);
        $subloopNode = $subloopNodeArr[1]; # name of subloop-datanode
        $subLoopNumber = $subloopNodeArr[2];
        preg_match('/{subloop:'.$subloopNode.':'.$subLoopNumber.'}(.*){\/subloop:'.$subloopNode.'}/', $datastructure, $subloopStructureArr);
        $subloopStructure = $subloopStructureArr[1];
      }
      if ($subloopStructure=="") {
        #  subloop not found, e.g. in closing-tag no subloopNode?
        preg_match('/{subloop:'.$subloopNode.':'.$subLoopNumber.'}(.*){\/subloop}/', $datastructure, $subloopStructureArr);
        #$subloopStructure = $subloopStructureArr[0][0];
        $subloopStructure = $subloopStructureArr[1];
      }
      $subloopHTML = $subloopStructure;
      $this->debugEcho( "subloop-array: <i>".htmlentities($datastructure)."</i> //  node: <i>$subloopNode</i> // regereturn: $regereturn // number: <i>$subLoopNumber</i> //  html: <i>".htmlentities($subloopHTML)."</i><br>");
      return array ($subloopNode, $subLoopNumber, $subloopHTML, $regereturn);
    }


    /* checkIfAddToResult: the code created by the template and the JSON-data is checked on
    - remaining markups
    - needed keywords
    - ignore flag $this->addToResult might set somewhere before to FALSE
    */
    private function checkIfAddToResult($resultCode) {
      # here we finally check the created code and remove all reminaing {subloop} or {subloop-array}-markups
      if ($this->oneOfTheseWordsMustBeIn!="") {
        $oneOfTheseWordsMustBeInArr = explode(",", trim($this->oneOfTheseWordsMustBeIn));
        $isIn = FALSE;
        foreach($oneOfTheseWordsMustBeInArr as $keyword) {
          if (trim($keyword)=="") { continue; }
          $kw = htmlentities(trim($keyword), ENT_COMPAT, 'UTF-8', FALSE);
          if (preg_match("/".$kw."/i", strip_tags($resultCode))) {
            $isIn = TRUE;
          }
        }
        if (!$isIn) {   return "";    } # none of the keywords was found: ignore this
      }
      if ($this->addToResult) {
        return $resultCode; # ok, add this code
      }
      return "";
    }

    /* debugEcho: display debugMessages or not */
    private function debugEcho($txt, $paramIn="", $object=NULL) {
      if ($paramIn=="wordpressticket") {
        echo $txt."<br>please open ticket at <a href=\"https://wordpress.org/plugins/json-content-importer/\" target=\"_blank\">wordpress.org</a><hr>";
      }
      if ($this->showDebugMessages) {
        if ($paramIn=="hhhshowdump") {
          echo "$txt<br><i>";
          print_r($object);
          echo "</i><br>";
        } else if ($paramIn=="") {
          echo $txt;
        }
      }
    }

    /* clearUnusedArrayDatafields: remove unfilled markups: we loop the JSON-data, not the markups. If there is no JSON, the markup might stay markup... */
    private function clearUnusedArrayDatafields($datastructure) {
      $regExpPatt = "([0-9]*)";
      $datastructure = preg_replace("/{".$regExpPatt."}/i", "", $datastructure);
      $datastructure = preg_replace("/{".$regExpPatt.":urlencode}/i", "", $datastructure);
      $datastructure = preg_replace("/{".$regExpPatt.":ifNotEmptyAdd:".$this->regExpPatternDetect."}/i", "", $datastructure);
      $datastructure = preg_replace("/{".$regExpPatt.":ifNotEmptyAddLeft:".$this->regExpPatternDetect."}/i", "", $datastructure);
      $datastructure = preg_replace("/{".$regExpPatt.":ifNotEmptyAddRight:".$this->regExpPatternDetect."}/i", "", $datastructure);
      return $datastructure;
    }

	}
?>