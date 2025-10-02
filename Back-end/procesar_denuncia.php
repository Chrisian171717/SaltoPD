<?php
// procesar_denuncia.php - Usando conexion.php
include("denuncias.php");

header('Content-Type: text/html; charset=UTF-8');

// Verificar conexión
if (!verificarConexionBD()) {
    echo "<p class='error'>Error de conexión a la base de datos.</p>";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'agregar') {
    
    if (!isset($_POST['nombre_civil']) || !isset($_POST['codigo_penal']) || !isset($_POST['descripcion'])) {
        echo "<p class='error'>Todos los campos son obligatorios.</p>";
        exit();
    }
    
    $nombre_civil = trim($_POST['nombre_civil']);
    $codigo_penal = trim($_POST['codigo_penal']);
    $descripcion  = trim($_POST['descripcion']);

    // Validaciones adicionales
    if (empty($nombre_civil) || empty($codigo_penal) || empty($descripcion)) {
        echo "<p class='error'>Todos los campos son obligatorios.</p>";
        exit();
    }

    if (strlen($nombre_civil) > 100) {
        echo "<p class='error'>El nombre no puede tener más de 100 caracteres.</p>";
        exit();
    }

    if (strlen($codigo_penal) > 255) {
        echo "<p class='error'>El código penal no puede tener más de 255 caracteres.</p>";
        exit();
    }

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