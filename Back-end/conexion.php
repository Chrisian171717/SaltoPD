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
        error_log("Error de conexión MySQL: " . $mysqli->connect_error);
        return false;
    }
    
    $mysqli->set_charset("utf8mb4");
    return $mysqli;
}

// Crear la conexión global
$conn = conectarDB();

// Función para verificar la conexión
function verificarConexion() {
    global $conn;
    if (!$conn || $conn->connect_error) {
        return false;
    }
    return true;
}
?>