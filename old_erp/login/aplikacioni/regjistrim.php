<?php
 
// sigurohet lidhja me databazen
include_once '../konfigurimi/databaza.php';
 
// krijimi i instances se objektit perdorues
include_once '../objekte/perdoruesit.php';
 
$database = new Database();
$db = $database->getConnection();

//Krijojme objekt te ri te klases User 
$user = new User($db);
 
// vendosen vlerat e cilesive te perdoruesit
$user->username = $_POST['username'];
$user->password = base64_encode($_POST['password']);
$user->created = date('Y-m-d H:i:s');
 
// krijohet perdoruesi
if($user->signup()){
    $user_arr=array(
        "status" => true,
        "message" => "Die Registrierung war erfolgreich!",
        "id" => $user->id,
        "username" => $user->username
    );
}
else{
    $user_arr=array(
        "status" => false,
        "message" => "Dieser Benutzer existiert!"
    );
}
print_r(json_encode($user_arr));
?>