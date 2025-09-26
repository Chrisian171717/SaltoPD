<?php
// conexion.php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "saltopd";

function conectarDB() {
    global $host, $user, $pass, $db;
    
    $mysqli = new mysqli($host, $user, $pass, $db);
    
    if ($mysqli->connect_errno) {
        // Error más detallado
        error_log("Error de conexión MySQL: " . $mysqli->connect_error);
        return array('success' => false, 'error' => "Error de conexión: " . $mysqli->connect_error);
    }
    
    $mysqli->set_charset("utf8mb4");
    return array('success' => true, 'connection' => $mysqli);
}
?>