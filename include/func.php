<?php

function redirect($url){
    header('Location: '.$url);
    exit();
}

function addNumber($num1 , $num2){

    return $num1 + $num2;

}

?>