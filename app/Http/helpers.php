<?php


if(! function_exists('resultFunction')){
    function resultFunction($message, $status = false, $data = null){
        return [
            'status' => $status,
            'message' => $message,
            'data' => $data
        ];
    }
}