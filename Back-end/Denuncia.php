<?php
// Configuración DB
include("conexion.php");

// Denuncia.php
header('Content-Type: text/html; charset=UTF-8');

// --- CONFIGURACIÓN ---
$host = "localhost";
$user = "root";
$pass = "";
$db   = "saltopd";

// --- CONEXIÓN ---
$mysqli = new mysqli($host, $user, $pass, $db);
if ($mysqli->connect_errno) {
    die("Error de conexión: " . $mysqli->connect_error);
}

// --- FUNCIONES ---
function sanitize($mysqli, $data) {
    return htmlspecialchars($mysqli->real_escape_string(trim($data)));
}

// --- ACCIONES ---
$accion = $_POST['accion'] ?? '';
$busqueda = $_POST['busqueda'] ?? '';
$codigo_busqueda = $_POST['codigo_busqueda'] ?? '';

// --- AGREGAR DENUNCIA ---
if ($accion === 'agregar') {
    $nombre_civil = sanitize($mysqli, $_POST['nombre_civil'] ?? '');
    $codigo_penal = sanitize($mysqli, $_POST['codigo_penal'] ?? '');
    $descripcion  = sanitize($mysqli, $_POST['descripcion'] ?? '');

    if ($nombre_civil && $codigo_penal && $descripcion) {
        $stmt = $mysqli->prepare("INSERT INTO denuncias (nombre_civil, codigo_penal, descripcion, fecha) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("sss", $nombre_civil, $codigo_penal, $descripcion);
        $stmt->execute();
        $stmt->close();
        echo "<p class='success'>Denuncia agregada correctamente.</p>";
    } else {
        echo "<p class='error'>Todos los campos son obligatorios.</p>";
    }
}

// --- BUSCAR DENUNCIA ---
$where = "";
$params = [];
$types = "";

if ($busqueda) {
    $where .= "nombre_civil LIKE CONCAT('%', ?, '%')";
    $params[] = $busqueda;
    $types .= "s";
}
if ($codigo_busqueda) {
    if ($where) $where .= " AND ";
    $where .= "codigo_penal LIKE CONCAT('%', ?, '%')";
    $params[] = $codigo_busqueda;
    $types .= "s";
}

$query = "SELECT * FROM denuncias";
if ($where) $query .= " WHERE $where";
$query .= " ORDER BY fecha DESC";

// Preparar y ejecutar
$stmt = $mysqli->prepare($query);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// --- GENERAR TABLA DE DENUNCIAS ---
echo "<table border='1' class='denuncias-table'>";
echo "<thead><tr><th>ID</th><th>Nombre Civil</th><th>Código Penal</th><th>Descripción</th><th>Fecha</th></tr></thead>";
echo "<tbody>";
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>".htmlspecialchars($row['id'])."</td>";
    echo "<td>".htmlspecialchars($row['nombre_civil'])."</td>";
    echo "<td>".htmlspecialchars($row['codigo_penal'])."</td>";
    echo "<td>".htmlspecialchars($row['descripcion'])."</td>";
    echo "<td>".htmlspecialchars($row['fecha'])."</td>";
    echo "</tr>";
}
echo "</tbody>";
echo "</table>";

$stmt->close();
$mysqli->close();
?>
