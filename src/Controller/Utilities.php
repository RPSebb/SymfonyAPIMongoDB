<?php
namespace App\Controller;

function beautifyError(string $errors) : array {
    $listErrors = preg_split("/[.,:]+/", $errors);
    $beautyErrors = [];
    $length = count($listErrors);
    for($i = 1; $i < $length; $i += 3)
    {
        $name = $listErrors[$i];
        if(!isset($beautyErrors[$name])){
            $beautyErrors[$name] = '';
        }else {
            $beautyErrors[$name] .= ' ';
        }
        $beautyErrors[$name] .= preg_replace('/^\s+/', '', $listErrors[$i + 1]) . '.';
    }
    
    return $beautyErrors;
}