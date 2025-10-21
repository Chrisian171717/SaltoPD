<?php
// get_image.php - Servir imágenes desde tu base de datos saltopd
header('Content-Type: application/json; charset=utf-8');

// Incluir tu conexión existente
require_once 'conexion.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        throw new Exception('Método no permitido');
    }
    
    $type = $_GET['type'] ?? '';
    $id = $_GET['id'] ?? 0;
    
    if (empty($type) || empty($id)) {
        throw new Exception('Parámetros type e id requeridos');
    }
    
    if (!verificarConexion()) {
        throw new Exception('No hay conexión a la base de datos');
    }
    
    global $conn;
    
    if ($type === 'document') {
        $sql = "SELECT imagen_data, imagen_tipo FROM documentos_escaneo WHERE id = ?";
    } elseif ($type === 'face') {
        $sql = "SELECT imagen_data, imagen_tipo FROM rostros_escaneo WHERE id = ?";
    } else {
        throw new Exception('Tipo no válido. Use "document" o "face"');
    }
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Error preparando consulta');
    }
    
    $stmt->bind_param("i", $id);
    if (!$stmt->execute()) {
        throw new Exception('Error ejecutando consulta');
    }
    
    $result = $stmt->get_result();
    $imageData = $result->fetch_assoc();
    $stmt->close();
    
    if (!$imageData || empty($imageData['imagen_data'])) {
        throw new Exception('Imagen no encontrada');
    }
    
    // Servir la imagen
    header('Content-Type: ' . $imageData['imagen_tipo']);
    header('Content-Length: ' . strlen($imageData['imagen_data']));
    header('Cache-Control: max-age=3600, public');
    header('Pragma: cache');
    
    echo $imageData['imagen_data'];
    exit;
    
} catch (Exception $e) {
    error_log("❌ Error sirviendo imagen: " . $e->getMessage());
    
    // Servir imagen de error por defecto
    header('Content-Type: image/svg+xml');
    echo '<svg width="400" height="300" xmlns="http://www.w3.org/2000/svg">
        <rect width="400" height="300" fill="#f3f4f6"/>
        <text x="200" y="150" text-anchor="middle" font-family="Arial" font-size="16" fill="#6b7280">
            Imagen no disponible
        </text>
    </svg>';
    exit;
}
?>