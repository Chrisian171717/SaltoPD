<?php
// Back-end/debug.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include("conexion.php");

// Verificar conexión
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
} else {
    echo "✅ Conexión a la base de datos exitosa<br>";
}

// Probar consulta de unidades
$result = $conn->query("SELECT * FROM unidades LIMIT 1");
if ($result) {
    echo "✅ Consulta de unidades funciona<br>";
} else {
    echo "❌ Error en consulta de unidades: " . $conn->error . "<br>";
}

// Probar consulta de comunicaciones
$result = $conn->query("SELECT * FROM comunicaciones LIMIT 1");
if ($result) {
    echo "✅ Consulta de comunicaciones funciona<br>";
} else {
    echo "❌ Error en consulta de comunicaciones: " . $conn->error . "<br>";
}

// Probar consulta de emergencias
$result = $conn->query("SELECT * FROM emergencias LIMIT 1");
if ($result) {
    echo "✅ Consulta de emergencias funciona<br>";
} else {
    echo "❌ Error en consulta de emergencias: " . $conn->error . "<br>";
}
?>