<?php
// Configuración de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Permitir CORS (opcional si solo usás localhost)
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
$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Agregar civil
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

// GET: leer o buscar civiles
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($action === 'read') {
        $result = $conexion->query("SELECT * FROM civiles ORDER BY Nombre");
        $civiles = [];
        while ($row = $result->fetch_assoc()) {
            $civiles[] = ['id' => $row['id'], 'nombre' => $row['Nombre'], 'dni' => $row['dni']];
        }
        echo json_encode(['success' => true, 'data' => $civiles]);
        $result->free();
        $conexion->close();
        exit;
    }

    if ($action === 'search') {
        $q = sanitizar($_GET['q'] ?? '');
        $stmt = $conexion->prepare("SELECT * FROM civiles WHERE Nombre LIKE ? OR dni LIKE ? ORDER BY Nombre");
        $like = "%$q%";
        $stmt->bind_param("ss", $like, $like);
        $stmt->execute();
        $result = $stmt->get_result();
        $civiles = [];
        while ($row = $result->fetch_assoc()) {
            $civiles[] = ['id' => $row['id'], 'nombre' => $row['Nombre'], 'dni' => $row['dni']];
        }
        echo json_encode(['success' => true, 'data' => $civiles]);
        $stmt->close();
        $conexion->close();
        exit;
    }

    // Acción inválida
    echo json_encode([
        'success' => false,
        'message' => 'Acción no válida. Usa action=read o action=search'
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

