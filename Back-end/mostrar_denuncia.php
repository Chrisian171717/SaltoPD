<?php
// mostrar_denuncias.php

// HABILITAR ERRORES - MUY IMPORTANTE
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

echo "<!-- Iniciando mostrar_denuncias.php -->";

include("funciones_denuncias.php");

header('Content-Type: text/html; charset=UTF-8');

$busqueda = isset($_GET['busqueda']) ? $_GET['busqueda'] : '';
$codigo_busqueda = isset($_GET['codigo_busqueda']) ? $_GET['codigo_busqueda'] : '';

echo "<!-- Búsqueda: '$busqueda', Código: '$codigo_busqueda' -->";

$denuncias = buscarDenuncias($busqueda, $codigo_busqueda);

echo "<!-- Resultado de buscarDenuncias: " . gettype($denuncias) . " -->";

if ($denuncias === false) {
    echo "<p class='error'>Error al conectar con la base de datos.</p>";
    echo "<!-- Error details could not be retrieved -->";
    exit();
}

echo generarTablaDenuncias($denuncias);

if (empty($denuncias) && (!empty($busqueda) || !empty($codigo_busqueda))) {
    echo "<p class='info'>Intente con otros términos de búsqueda.</p>";
}

echo "<!-- Finalizando mostrar_denuncias.php -->";
?>