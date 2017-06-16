<?php

namespace DHL\Api;

trait Tool
{
    //object transformation array
    function object_to_array($obj){
        $_arr=is_object($obj) ? get_object_vars($obj) : $obj;
        $arr = null;
        foreach($_arr as $key=>$val){
            $val = (is_array($val))||is_object($val) ? $this->object_to_array($val) : $val;
            $arr[$key] = $val;
        }
        return $arr;
    }

    //array transformation object
    function array_to_object($obj){
        foreach ($obj as $key=>$item) {
            if (gettype($item)=='array' || getType($item)=='object') {
                $obj[$key] = (object)$this->array_to_object($item);
            }
        }
        return (object)$obj;
    }
}
