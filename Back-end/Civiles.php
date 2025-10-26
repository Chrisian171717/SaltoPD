<?php
// Configuración de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Permitir CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Conexión a la base de datos
$conexion = new mysqli("localhost", "root", "", "saltopd");

if ($conexion->connect_error) {
    echo json_encode([
        'success' => false,
        'message' => "Error de conexión: " . $conexion->connect_error
    ]);
    exit;
}

// Sanitizar datos
function sanitizar($dato) {
    return htmlspecialchars(trim($dato), ENT_QUOTES, 'UTF-8');
}

// Determinar acción
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// ==================== MÉTODOS POST ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Agregar civil
    if ($action === 'create') {
        $nombre = sanitizar($_POST['nombre'] ?? '');
        $dni = sanitizar($_POST['dni'] ?? '');
        
        $errores = [];
        if (strlen($nombre) < 3) $errores[] = "El nombre debe tener al menos 3 caracteres";
        if (empty($dni) || !is_numeric($dni)) $errores[] = "DNI inválido";
        
        if (!empty($errores)) {
            echo json_encode([
                'success' => false,
                'message' => 'Errores de validación',
                'errors' => $errores
            ]);
            exit;
        }
        
        $stmt = $conexion->prepare("INSERT INTO civiles (Nombre, dni) VALUES (?, ?)");
        $stmt->bind_param("ss", $nombre, $dni);
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Civil agregado correctamente',
                'data' => ['nombre' => $nombre, 'dni' => $dni]
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => "Error al insertar: " . $stmt->error
            ]);
        }
        
        $stmt->close();
        $conexion->close();
        exit;
    }
    
    // Agregar delito
    if ($action === 'add_delito') {
        $civil_id = intval($_POST['civil_id'] ?? 0);
        $tipo_delito = sanitizar($_POST['tipo_delito'] ?? '');
        $descripcion = sanitizar($_POST['descripcion'] ?? '');
        $fecha_delito = sanitizar($_POST['fecha_delito'] ?? '');
        
        if ($civil_id <= 0 || empty($tipo_delito) || empty($fecha_delito)) {
            echo json_encode([
                'success' => false,
                'message' => 'Datos incompletos'
            ]);
            exit;
        }
        
        $stmt = $conexion->prepare("INSERT INTO delitos (civil_id, tipo_delito, descripcion, fecha_delito) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $civil_id, $tipo_delito, $descripcion, $fecha_delito);
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Delito agregado correctamente'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => "Error al insertar delito: " . $stmt->error
            ]);
        }
        
        $stmt->close();
        $conexion->close();
        exit;
    }
    
    // Editar delito
    if ($action === 'edit_delito') {
        $delito_id = intval($_POST['delito_id'] ?? 0);
        $tipo_delito = sanitizar($_POST['tipo_delito'] ?? '');
        $descripcion = sanitizar($_POST['descripcion'] ?? '');
        $fecha_delito = sanitizar($_POST['fecha_delito'] ?? '');
        
        if ($delito_id <= 0 || empty($tipo_delito) || empty($fecha_delito)) {
            echo json_encode([
                'success' => false,
                'message' => 'Datos incompletos'
            ]);
            exit;
        }
        
        $stmt = $conexion->prepare("UPDATE delitos SET tipo_delito = ?, descripcion = ?, fecha_delito = ? WHERE id = ?");
        $stmt->bind_param("sssi", $tipo_delito, $descripcion, $fecha_delito, $delito_id);
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Delito actualizado correctamente'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => "Error al actualizar delito: " . $stmt->error
            ]);
        }
        
        $stmt->close();
        $conexion->close();
        exit;
    }
    
    // Eliminar delito
    if ($action === 'delete_delito') {
        $delito_id = intval($_POST['delito_id'] ?? 0);
        
        if ($delito_id <= 0) {
            echo json_encode([
                'success' => false,
                'message' => 'ID de delito inválido'
            ]);
            exit;
        }
        
        $stmt = $conexion->prepare("DELETE FROM delitos WHERE id = ?");
        $stmt->bind_param("i", $delito_id);
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Delito eliminado correctamente'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => "Error al eliminar delito: " . $stmt->error
            ]);
        }
        
        $stmt->close();
        $conexion->close();
        exit;
    }
    
    // Acción POST no reconocida
    echo json_encode([
        'success' => false,
        'message' => 'Acción POST no válida'
    ]);
    $conexion->close();
    exit;
}

// ==================== MÉTODOS GET ====================
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    
    // Leer todos los civiles
    if ($action === 'read') {
        $result = $conexion->query("SELECT * FROM civiles ORDER BY Nombre");
        $civiles = [];
        while ($row = $result->fetch_assoc()) {
            $civiles[] = [
                'id' => $row['id'],
                'nombre' => $row['Nombre'],
                'dni' => $row['dni']
            ];
        }
        echo json_encode(['success' => true, 'data' => $civiles]);
        $result->free();
        $conexion->close();
        exit;
    }
    
    // Buscar civiles
    if ($action === 'search') {
        $q = sanitizar($_GET['q'] ?? '');
        $stmt = $conexion->prepare("SELECT * FROM civiles WHERE Nombre LIKE ? OR dni LIKE ? ORDER BY Nombre");
        $like = "%$q%";
        $stmt->bind_param("ss", $like, $like);
        $stmt->execute();
        $result = $stmt->get_result();
        $civiles = [];
        while ($row = $result->fetch_assoc()) {
            $civiles[] = [
                'id' => $row['id'],
                'nombre' => $row['Nombre'],
                'dni' => $row['dni']
            ];
        }
        echo json_encode(['success' => true, 'data' => $civiles]);
        $stmt->close();
        $conexion->close();
        exit;
    }
    
    // Leer delitos de un civil
    if ($action === 'read_delitos') {
        $civil_id = intval($_GET['civil_id'] ?? 0);
        
        if ($civil_id <= 0) {
            echo json_encode([
                'success' => false,
                'message' => 'ID de civil inválido'
            ]);
            exit;
        }
        
        $stmt = $conexion->prepare("SELECT * FROM delitos WHERE civil_id = ? ORDER BY fecha_delito DESC");
        $stmt->bind_param("i", $civil_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $delitos = [];
        while ($row = $result->fetch_assoc()) {
            $delitos[] = [
                'id' => $row['id'],
                'civil_id' => $row['civil_id'],
                'tipo_delito' => $row['tipo_delito'],
                'descripcion' => $row['descripcion'],
                'fecha_delito' => $row['fecha_delito']
            ];
        }
        echo json_encode(['success' => true, 'data' => $delitos]);
        $stmt->close();
        $conexion->close();
        exit;
    }
    
    // Acción GET no reconocida
    echo json_encode([
        'success' => false,
        'message' => 'Acción no válida. Usa action=read, action=search o action=read_delitos'
    ]);
    $conexion->close();
    exit;
}

// Método no permitido
echo json_encode([
    'success' => false,
    'message' => 'Método no permitido'
]);
$conexion->close();
?>