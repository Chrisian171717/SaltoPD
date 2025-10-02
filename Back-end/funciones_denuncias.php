<?php
// Headers para CORS y JSON - DEBEN SER LAS PRIMERAS LÍNEAS
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Configuración de la base de datos
$host = 'localhost';
$user = 'root';
$password = '';
$dbname = 'plataforma_denuncias';

// Respuesta por defecto
$response = ['status' => 'error', 'mensaje' => 'Acción no válida'];

try {
    // Conexión a MySQL
    $conn = new mysqli($host, $user, $password);
    
    if ($conn->connect_error) {
        throw new Exception('Error de conexión MySQL: ' . $conn->connect_error);
    }
    
    // Crear base de datos si no existe
    if (!$conn->query("CREATE DATABASE IF NOT EXISTS $dbname")) {
        throw new Exception('Error creando base de datos: ' . $conn->error);
    }
    
    // Seleccionar base de datos
    $conn->select_db($dbname);
    
    // Crear tabla si no existe
    $sql = "CREATE TABLE IF NOT EXISTS denuncias (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre_civil VARCHAR(100) NOT NULL,
        CodigoPenal VARCHAR(255) NOT NULL,
        descripcion TEXT NOT NULL,
        Fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        Tipo VARCHAR(50) DEFAULT 'Denuncia',
        Tipo_Informe VARCHAR(255) DEFAULT 'General',
        Informe_Denuncia VARCHAR(100),
        Num_Placa INT,
        Cedula_C INT
    )";
    
    if (!$conn->query($sql)) {
        throw new Exception('Error creando tabla: ' . $conn->error);
    }
    
    // Obtener acción
    $accion = isset($_POST['accion']) ? $_POST['accion'] : (isset($_GET['accion']) ? $_GET['accion'] : '');
    
    switch($accion) {
        case 'listar':
            $result = $conn->query("SELECT * FROM denuncias ORDER BY Fecha DESC");
            if (!$result) {
                throw new Exception('Error en consulta: ' . $conn->error);
            }
            
            $denuncias = [];
            while($row = $result->fetch_assoc()) {
                $denuncias[] = $row;
            }
            $response = ['status' => 'ok', 'data' => $denuncias];
            break;
            
        case 'agregar':
            $nombre = isset($_POST['nombre_civil']) ? $conn->real_escape_string(trim($_POST['nombre_civil'])) : '';
            $codigo = isset($_POST['codigo_penal']) ? $conn->real_escape_string(trim($_POST['codigo_penal'])) : '';
            $descripcion = isset($_POST['descripcion']) ? $conn->real_escape_string(trim($_POST['descripcion'])) : '';
            
            if(empty($nombre) || empty($codigo) || empty($descripcion)) {
                throw new Exception('Todos los campos son obligatorios');
            }
            
            $sql = "INSERT INTO denuncias (nombre_civil, CodigoPenal, descripcion) VALUES ('$nombre', '$codigo', '$descripcion')";
            
            if($conn->query($sql)) {
                $response = ['status' => 'ok', 'mensaje' => 'Denuncia agregada correctamente'];
            } else {
                throw new Exception('Error al agregar: ' . $conn->error);
            }
            break;
            
        case 'editar':
            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            $nombre = isset($_POST['nombre_civil']) ? $conn->real_escape_string(trim($_POST['nombre_civil'])) : '';
            $codigo = isset($_POST['codigo_penal']) ? $conn->real_escape_string(trim($_POST['codigo_penal'])) : '';
            $descripcion = isset($_POST['descripcion']) ? $conn->real_escape_string(trim($_POST['descripcion'])) : '';
            
            if($id <= 0) throw new Exception('ID inválido');
            if(empty($nombre) || empty($codigo) || empty($descripcion)) {
                throw new Exception('Todos los campos son obligatorios');
            }
            
            $sql = "UPDATE denuncias SET nombre_civil = '$nombre', CodigoPenal = '$codigo', descripcion = '$descripcion' WHERE id = $id";
            
            if($conn->query($sql)) {
                $response = ['status' => 'ok', 'mensaje' => 'Denuncia actualizada correctamente'];
            } else {
                throw new Exception('Error al editar: ' . $conn->error);
            }
            break;
            
        case 'eliminar':
            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            if($id <= 0) throw new Exception('ID inválido');
            
            if($conn->query("DELETE FROM denuncias WHERE id = $id")) {
                $response = ['status' => 'ok', 'mensaje' => 'Denuncia eliminada correctamente'];
            } else {
                throw new Exception('Error al eliminar: ' . $conn->error);
            }
            break;
            
        case 'buscar':
            $busqueda = isset($_POST['busqueda']) ? $conn->real_escape_string(trim($_POST['busqueda'])) : '';
            $codigo_busqueda = isset($_POST['codigo_busqueda']) ? $conn->real_escape_string(trim($_POST['codigo_busqueda'])) : '';
            
            $sql = "SELECT * FROM denuncias WHERE 1=1";
            if(!empty($busqueda)) {
                $sql .= " AND (nombre_civil LIKE '%$busqueda%' OR descripcion LIKE '%$busqueda%')";
            }
            if(!empty($codigo_busqueda)) {
                $sql .= " AND CodigoPenal LIKE '%$codigo_busqueda%'";
            }
            $sql .= " ORDER BY Fecha DESC";
            
            $result = $conn->query($sql);
            if (!$result) {
                throw new Exception('Error en búsqueda: ' . $conn->error);
            }
            
            $denuncias = [];
            while($row = $result->fetch_assoc()) {
                $denuncias[] = $row;
            }
            $response = ['status' => 'ok', 'data' => $denuncias];
            break;
            
        case 'estadisticas':
            $total_result = $conn->query("SELECT COUNT(*) as total FROM denuncias");
            $hoy_result = $conn->query("SELECT COUNT(*) as hoy FROM denuncias WHERE DATE(Fecha) = CURDATE()");
            $mes_result = $conn->query("SELECT COUNT(*) as mes FROM denuncias WHERE MONTH(Fecha) = MONTH(CURDATE()) AND YEAR(Fecha) = YEAR(CURDATE())");
            
            if (!$total_result || !$hoy_result || !$mes_result) {
                throw new Exception('Error obteniendo estadísticas');
            }
            
            $total = $total_result->fetch_assoc()['total'];
            $hoy = $hoy_result->fetch_assoc()['hoy'];
            $mes = $mes_result->fetch_assoc()['mes'];
            
            $response = [
                'status' => 'ok', 
                'data' => [
                    'total' => $total,
                    'hoy' => $hoy,
                    'mes' => $mes,
                    'resueltas' => 0
                ]
            ];
            break;
            
        default:
            $response = ['status' => 'error', 'mensaje' => 'Acción no reconocida'];
            break;
    }
    
    $conn->close();
    
} catch (Exception $e) {
    $response = ['status' => 'error', 'mensaje' => $e->getMessage()];
}

// Limpiar buffer de salida y enviar solo JSON
if (ob_get_length()) {
    ob_clean();
}

// Enviar respuesta JSON - ESTA DEBE SER LA ÚLTIMA LÍNEA
echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit();
?>