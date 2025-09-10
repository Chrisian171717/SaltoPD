<?php 

$host="localhost";
$user= "root";
$pass= "";
$db= "saltodp";

$conn = mysqli_connect($host,$user,$pass, $db);


if ($conn) {
    die("error en la conexion: " .mysqli_connect_error());
}

?>