<?php
// funciones_denuncias.php
include("conexion.php");

// Habilitar errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

function sanitizarDato($dato) {
    return htmlspecialchars(trim($dato));
}

function agregarDenuncia($nombre_civil, $codigo_penal, $descripcion) {
    $result = conectarDB();
    if (!$result['success']) {
        return $result['error'];
    }
    $mysqli = $result['connection'];
    
    $nombre_civil = $mysqli->real_escape_string(sanitizarDato($nombre_civil));
    $codigo_penal = $mysqli->real_escape_string(sanitizarDato($codigo_penal));
    $descripcion = $mysqli->real_escape_string(sanitizarDato($descripcion));
    
    if (empty($nombre_civil) || empty($codigo_penal) || empty($descripcion)) {
        $mysqli->close();
        return "Todos los campos son obligatorios";
    }
    
    try {
        $stmt = $mysqli->prepare("INSERT INTO denuncias (nombre_civil, CodigoPenal, descripcion, Fecha) VALUES (?, ?, ?, NOW())");
        
        if (!$stmt) {
            throw new Exception("Error al preparar consulta: " . $mysqli->error);
        }
        
        $stmt->bind_param("sss", $nombre_civil, $codigo_penal, $descripcion);
        
        if ($stmt->execute()) {
            $resultado = "success";
        } else {
            throw new Exception("Error al ejecutar: " . $stmt->error);
        }
        
        $stmt->close();
        
    } catch (Exception $e) {
        $resultado = $e->getMessage();
    }
    
    $mysqli->close();
    return $resultado;
}

function buscarDenuncias($busqueda = '', $codigo_busqueda = '') {
    $result = conectarDB();
    if (!$result['success']) {
        error_log($result['error']);
        return false;
    }
    $mysqli = $result['connection'];
    
    $where = "";
    $params = [];
    $types = "";

    if (!empty($busqueda)) {
        $where .= "nombre_civil LIKE CONCAT('%', ?, '%')";
        $params[] = $busqueda;
        $types .= "s";
    }
    
    if (!empty($codigo_busqueda)) {
        if (!empty($where)) $where .= " AND ";
        $where .= "CodigoPenal LIKE CONCAT('%', ?, '%')";
        $params[] = $codigo_busqueda;
        $types .= "s";
    }

    $query = "SELECT id, nombre_civil, CodigoPenal, descripcion, Fecha FROM denuncias";
    if (!empty($where)) $query .= " WHERE " . $where;
    $query .= " ORDER BY Fecha DESC LIMIT 100";

    try {
        $stmt = $mysqli->prepare($query);
        
        if (!$stmt) {
            throw new Exception("Error prepare: " . $mysqli->error);
        }

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        if (!$stmt->execute()) {
            throw new Exception("Error execute: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        $denuncias = [];
        
        while ($row = $result->fetch_assoc()) {
            $denuncias[] = $row;
        }
        
        $stmt->close();
        $mysqli->close();
        
        return $denuncias;
        
    } catch (Exception $e) {
        error_log($e->getMessage());
        $mysqli->close();
        return false;
    }
}

function generarTablaDenuncias($denuncias) {
    if ($denuncias === false) {
        return "<p class='error'>Error al conectar con la base de datos. Verifica la conexión.</p>";
    }
    
    if (empty($denuncias)) {
        return "<p class='no-data'>No se encontraron denuncias.</p>";
    }
    
    $html = "<table class='denuncias-table'>";
    $html .= "<thead><tr><th>ID</th><th>Nombre Civil</th><th>Código Penal</th><th>Descripción</th><th>Fecha</th></tr></thead>";
    $html .= "<tbody>";
    
    foreach ($denuncias as $row) {
        $html .= "<tr>";
        $html .= "<td>" . htmlspecialchars($row['id']) . "</td>";
        $html .= "<td>" . htmlspecialchars($row['nombre_civil']) . "</td>";
        $html .= "<td>" . htmlspecialchars($row['CodigoPenal']) . "</td>";
        $html .= "<td>" . htmlspecialchars($row['descripcion']) . "</td>";
        $html .= "<td>" . htmlspecialchars($row['Fecha']) . "</td>";
        $html .= "</tr>";
    }
    
    $html .= "</tbody>";
    $html .= "</table>";
    $html .= "<p style='text-align: center; color: #666; margin-top: 15px;'>Mostrando " . count($denuncias) . " denuncia(s)</p>";
    
    return $html;
}
?>