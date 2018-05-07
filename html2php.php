<?php


class HTML2PHP
{
    protected $raw_string;
    protected $regkey = "/<[^>]*>/";
    protected $flat;
    protected $tree   = NULL;
    public    $ignore = array("doctype");
    
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
    }

    public function convert()
    {
        $struct = array();
        foreach($this->flat as $index => $element){
            $temp = array(
                "tagname"    => $this->get_pure_tag($element[0]),
                "start"      => $this->flat[$index],
                "startIndex" => $index,
                "children"   => array(),
                'end'        => NULL
            );

            $temp['endIndex'] = $this->findClosing($index);
            
            if($temp['endIndex'] > 0){
                $temp['end'] = $this->flat[ $temp['endIndex'] ];
            }

            $this->flat[$index] = $temp;
        }

        $this->list2tree();
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

    public function print_array()
    {
        echo "<pre>";
        print_r($this->tree);
        echo "</pre>";
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

    // function removeIgnores($list){
    //     $new = array();
    //     foreach($list as $index => $item){
    //         if(!isIgnore($item[0])){
    //             $new[] = $item;
    //         }
    //     }
    //     return $new;
    // }

    // function getHTMLString()
    // {
    //     return file_get_contents("testfiles/simple.html");
    // }

    // function isIgnore($tag)
    // {
    //     $ignore = array("doctype", "html", "/html", "<br");
    //     foreach($ignore as $target){
    //         if( strripos( $tag , $target) !== FALSE){
    //             return true;
    //         }
    //     }
    //     return false;
        
    // }
    
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
    
    private function isClosingTag($tag)
    {
        if(substr($tag,1,1) == "/"){
            return true;
        }else{
            return false;
        }
    }
}