<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = "localhost";
$user = "root";
$pass = "";
$db   = "saltopd";

$mysqli = new mysqli($host, $user, $pass, $db);
if ($mysqli->connect_errno) die("Error conexión");

// Contar denuncias
$result = $mysqli->query("SELECT COUNT(*) as total FROM denuncias");
$row = $result->fetch_assoc();
echo "Total denuncias: " . $row['total'] . "<br>";

// Mostrar primeras 5 denuncias
$result = $mysqli->query("SELECT * FROM denuncias LIMIT 5");
echo "Primeras 5 denuncias:<br>";
while ($row = $result->fetch_assoc()) {
    echo "ID: " . $row['id'] . ", Nombre: " . $row['nombre_civil'] . "<br>";
}

$mysqli->close();
?>