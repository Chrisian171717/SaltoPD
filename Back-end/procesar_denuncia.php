<?php
include("funciones_denuncias.php");

header('Content-Type: text/html; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'agregar') {
    
    if (!isset($_POST['nombre_civil']) || !isset($_POST['codigo_penal']) || !isset($_POST['descripcion'])) {
        echo "<p class='error'>Todos los campos son obligatorios.</p>";
        exit();
    }
    
    $nombre_civil = $_POST['nombre_civil'];
    $codigo_penal = $_POST['codigo_penal'];
    $descripcion  = $_POST['descripcion'];

    $resultado = agregarDenuncia($nombre_civil, $codigo_penal, $descripcion);
    
    if ($resultado === "success") {
        echo "<p class='success'>✓ Denuncia agregada correctamente.</p>";
    } else {
        echo "<p class='error'>✗ " . htmlspecialchars($resultado) . "</p>";
    }
    
} else {
    echo "<p class='error'>Método no permitido.</p>";
}
?>