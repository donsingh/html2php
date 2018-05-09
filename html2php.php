<?php


class HTML2PHP
{
    protected $raw_string;
    protected $regkey = "/<[^>]*>/";
    protected $flat;
    protected $tree    = NULL;
    protected $matches = array();
    public    $ignore  = array("doctype");
    
    public function __construct( $file = null) {
        if($file == NULL){
            throw new Exception("No file passed");
        }

        if(!file_exists($file)){
            throw new Exception("File {$file} not found");
        }

        $this->consume($file);
    }

    public function consume($file)
    {
        $this->raw_string = file_get_contents($file);
        $temp = array();
        preg_match_all($this->regkey, $this->getHTMLString(), $temp, PREG_OFFSET_CAPTURE);
        $this->flat = $temp[0];
        $this->convert();
    }

    public function convert()
    {
        if(count($this->flat) < 6){
            throw new Exception("Failed to parse file!");
        }
        
        $struct = array();
        foreach($this->flat as $index => $element){
            $temp = array(
                "tagname"    => $this->get_pure_tag($element[0]),
                "dirtytag"   => $element[0],
                "start"      => $this->flat[$index],
                "startIndex" => $index,
                "children"   => array(),
                'end'        => NULL
            );

            $temp['endIndex'] = $this->findClosing($index);
            if($temp['endIndex'] > 0){
                $temp['end'] = $this->flat[ $temp['endIndex'] ];

                $readStart = $temp["start"][1] + strlen($temp['dirtytag']);
                $readEnd = $temp['end'][1] - $readStart;
                $temp['text'] = substr($this->raw_string, $readStart, $readEnd);
            }

            $temp['attributes'] = $this->getAttributes($element[0]);
            

            $this->flat[$index] = $temp;
        }

        // $remove = array("<html", "<body>");
        // foreach($this->flat as $index => $element){
        //     if(in_array($element['tagname'], $remove)){
        //         $this->flat = $element['children'];
        //     }
        // }

        $this->list2tree();
    }

    private function getAttributes($elem)
    {
        $attr        = array();
        $word        = "";
        $current     = "";
        $classifying = false;
        
        for($i=0; $i < strlen($elem); $i++){
            $char = $elem[$i];
            
            if($char == "="){
                $current = $word;
                $classifying = true;
                $word="";
            }
            
            $word .= $char;

            if($char == " " && $classifying == false){
                $word = "";
            }
            
            if($classifying == true && strlen($word) > 2 && $char == $word[1]){
                $temp = trim($word);
                $temp = str_replace("'", "", $temp);
                $temp = str_replace('"', "", $temp);
                $temp = str_replace("=", "", $temp);
                $temp = explode(" ", $temp);

                $attr[$current] = $temp;
                $word = "";
                $classifying = false;
            }
        }

        return $attr;
    }

    public function list2tree($start = NULL, $end = NULL)
    {
        if($this->tree == NULL){
            $this->tree = $this->flat[0];
            $this->tree["children"] = $this->list2tree($this->tree["startIndex"], $this->tree["endIndex"]);
        }else{
            $children = array();
            for($i = $start + 1; $i < $end; $i++){
                $current = $this->flat[$i];
                
                if( !$this->isClosingTag($current["tagname"]) && !isset($this->flat[$i]["used"])){
                    
                    // if element has child
                    if($current["startIndex"] + 1 != $current["endIndex"]){
                        $current["children"] = $this->list2tree($current["startIndex"], $current["endIndex"]);
                        
                    }
                    $this->flat[$i]["used"] = true;
                    $children[] = $current;
                }
            }
            return $children;
        }
        
    }

    public function find($selector = NULL)
    {
        if($selector == NULL || $selector == ""){
            throw new Exception("Missing parameter for find()");
        }

        $this->matches = array();

        $type = substr($selector, 0, 1);
        
        //TODO:: FIX FOR elem > elem.class + elem#id
        
        if($type == "#"){
            //search by id
        }else if($type == "."){
            //search by class
            $this->traverse($this->tree["children"], "class");
        }else{
            $this->traverse($this->tree["children"], $selector);
        }
        
        return $this->matches;
    }

    private function traverse($list, $search, $type = "tagname")
    {
        foreach($list as $props){

            $temp = NULL;

            if(is_array($props['children']) && count( $props['children']) > 0){
                $this->traverse($props['children'], $search, $type);
            }
            
            if($type == "tagname"){
                if($props["tagname"] == "<{$search}>"){
                    $temp = $props;
                }
            }else if($type == "class"){
                echo "hahaha";
                $search = substr($search, 1);
                if(isset($props['attributes']) && isset($props['attributes']['class']) && count($props['attributes']['class']) > 0){
                    foreach($props['attributes']['class'] as $className){
                        // echo $className."\n";
                        if(strcasecmp($className,$search)){
                            $temp = $props;
                        }
                    }
                }
            }

            if($temp !== NULL){
                unset($temp['children']);
                $this->matches[] = $temp;
            }
            
        }
    }

    public function getArray()
    {
        // echo "<pre>";
        // print_r($this->tree);
        // echo "</pre>";
        return $this->tree;
    }

    private function get_pure_tag($dirty)
    {
        $temp = explode(" ", $dirty);
        if(count($temp) == 1){
            return $dirty;
        }else{
            return $temp[0].">";
        }
    }

    public function removeIgnores($list)
    {
        //remove matches within the ignore list
        foreach($list as $index => $match){
            foreach($this->ignore as $target){
                if( strripos( $match[0] , $target) !== FALSE){
                    unset( $list[$index] );
                }
            }
        }
        
        return $list;
    }

    public function getHTMLString()
    {
        return $this->raw_string;
    }
    
    private function findClosing($pos)
    {
        $matches = $this->flat;
        $tag     = $this->get_pure_tag($matches[$pos][0]);
        $eClosing = "</".substr($tag,1);
        $nested = 0;
    
        for($j = $pos + 1; $j < count($matches); $j++){
            $thisTag = $this->get_pure_tag($matches[$j][0]);
            if($this->isClosingTag($thisTag) && $thisTag == $eClosing){
                if($nested == 0){
                    return $j;
                }else{
                    $nested--;
                }
            }
        }
        return -1;
    }
    
    public function isClosingTag($tag)
    {
        if(substr($tag,1,1) == "/"){
            return true;
        }else{
            return false;
        }
    }
}