<?php
// Configuración de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Permitir CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
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
$action = $_GET['action'] ?? '';

// ==================== SOLO MÉTODOS GET (LECTURA) ====================
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

// Método no permitido (bloqueamos POST, PUT, DELETE, etc.)
echo json_encode([
    'success' => false,
    'message' => 'Método no permitido. Este endpoint es solo de lectura.'
]);
$conexion->close();
?>