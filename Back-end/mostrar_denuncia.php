<?php
// mostrar_denuncias.php - Usando conexion.php

// HABILITAR ERRORES - MUY IMPORTANTE
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

echo "<!-- Iniciando mostrar_denuncias.php -->";

include("denuncias.php");

header('Content-Type: text/html; charset=UTF-8');

// Verificar conexión
if (!verificarConexionBD()) {
    echo "<p class='error'>Error al conectar con la base de datos.</p>";
    echo "<!-- Error de conexión a la BD -->";
    exit();
}

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

// Función para generar la tabla
function generarTablaDenuncias($denuncias) {
    if (empty($denuncias)) {
        return "<p class='info'>No se encontraron denuncias.</p>";
    }
    
    $html = "<table border='1' cellpadding='8' style='width:100%; border-collapse: collapse;'>
        <thead>
            <tr style='background-color: #f2f2f2;'>
                <th>ID</th>
                <th>Nombre Civil</th>
                <th>Código Penal</th>
                <th>Descripción</th>
                <th>Fecha</th>
            </tr>
        </thead>
        <tbody>";
    
    foreach ($denuncias as $d) {
        $html .= "<tr>
            <td>{$d['id']}</td>
            <td>{$d['nombre_civil']}</td>
            <td>{$d['CodigoPenal']}</td>
            <td>{$d['descripcion']}</td>
            <td>{$d['Fecha']}</td>
        </tr>";
    }
    
    $html .= "</tbody></table>";
    return $html;
}

echo generarTablaDenuncias($denuncias);

if (empty($denuncias) && (!empty($busqueda) || !empty($codigo_busqueda))) {
    echo "<p class='info'>No se encontraron resultados. Intente con otros términos de búsqueda.</p>";
}

echo "<!-- Finalizando mostrar_denuncias.php -->";
?>