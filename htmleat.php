<?php


class HTMLeat
{
    protected $raw_string;
    protected $regkey = "/<[^>]*>/";
    public $ignore = array("doctype");
    
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
        $this->read();
    }

    public function read()
    {
        // print $this->getHTMLString();
        $out = array();
        preg_match_all($this->regkey, $this->getHTMLString(), $out, PREG_OFFSET_CAPTURE);
        $matches = $this->removeIgnores($out[0]);
        
        $struct = array();

        
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
}