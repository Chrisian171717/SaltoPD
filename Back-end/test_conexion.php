<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = "localhost";
$user = "root";
$pass = "";
$db   = "saltopd";

$mysqli = new mysqli($host, $user, $pass, $db);

if ($mysqli->connect_errno) {
    die("Error conexión: " . $mysqli->connect_error);
} else {
    echo "✓ Conexión exitosa a MySQL<br>";
}

// Verificar si la base de datos existe
if ($mysqli->select_db($db)) {
    echo "✓ Base de datos '$db' existe<br>";
} else {
    die("✗ Base de datos '$db' NO existe");
}

// Verificar si la tabla existe
$result = $mysqli->query("SHOW TABLES LIKE 'denuncias'");
if ($result->num_rows > 0) {
    echo "✓ Tabla 'denuncias' existe<br>";
} else {
    die("✗ Tabla 'denuncias' NO existe");
}

// Verificar estructura de la tabla
$result = $mysqli->query("DESCRIBE denuncias");
echo "✓ Campos de la tabla:<br>";
while ($row = $result->fetch_assoc()) {
    echo "- " . $row['Field'] . " (" . $row['Type'] . ")<br>";
}

$mysqli->close();
?>