<?php
require 'lib/nusoap.php';
function getUserByName($name) {
    $con = mysqli_connect ("localhost", "username", "password", "albsale-vlora");
    if (mysqli_connect_errno()) {
        echo "Failed to connect to MySQL: ".mysqli_connect_error();
        die();
    }
    
    $result = mysqli_query($con, "Select * from user where Name like '%$name%");
    $response = "";
    
    if (mysqli_num_rows($result)>0) {
        while($row = mysqli_fetch_array($result)) {
            $response[] = $row;
        }
        
        return json_encode($response);
    }
    else{
        return "no data";
    }
}

function getArticleByID($name) {
    $con = mysqli_connect ("localhost", "ilrexho", "nA)Aj9NQWix0[diC", "albsale-vlora");
    if (mysqli_connect_errno()) {
        echo "Failed to connect to MySQL: ".mysqli_connect_error();
        die();
    }
    
    $result = mysqli_query($con, "Select * from user where Name like '%$name%");
    $response = "";
    
    if (mysqli_num_rows($result)>0) {
        while($row = mysqli_fetch_array($result)) {
            $response[] = $row;
        }
        
        return json_encode($response);
    }
    else{
        return "no data";
    }
}

$server = new nusoap_server();  // creation of an instance for soap server
$server->configureWSDL("Soap PHP Albsale-Vlora","urn:salt");

$server->register(
        "getUserByName", // name of function or service
        array("name"=>"xsd:string"), // input ..
        array("return"=>"xsd:string")); // output

$server->service(file_get_contents("php://input"));

/* 
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/EmptyPHP.php to edit this template
 */


