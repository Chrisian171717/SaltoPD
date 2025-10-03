<?php
include("conexion.php");

// Verificar conexión
if (!$conn || $conn->connect_error) {
    die("Error de conexión a la base de datos.");
}

// Inicializar variable de éxito
$success = false;
$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo_guardar = $_POST['tipo_guardar'];
    
    switch($tipo_guardar) {
        case 'unidad':
            $id = !empty($_POST['id_unidad']) ? intval($_POST['id_unidad']) : null;
            $codigo = trim($_POST['codigo']);
            $tipo = trim($_POST['tipo']);
            $estado = trim($_POST['estado']);
            $oficial_nombre = trim($_POST['oficial_nombre']);
            $oficial_rango = trim($_POST['oficial_rango']);
            $sector = trim($_POST['sector']);
            
            // Validar campos requeridos
            if (empty($codigo) || empty($tipo) || empty($estado) || empty($oficial_nombre) || empty($oficial_rango) || empty($sector)) {
                $message = "Todos los campos son requeridos";
                break;
            }
            
            if ($id) {
                // Actualizar unidad existente
                $stmt = $conn->prepare("UPDATE unidades SET codigo=?, tipo=?, estado=?, oficial_nombre=?, oficial_rango=?, sector=? WHERE id=?");
                $stmt->bind_param("ssssssi", $codigo, $tipo, $estado, $oficial_nombre, $oficial_rango, $sector, $id);
            } else {
                // Insertar nueva unidad
                $stmt = $conn->prepare("INSERT INTO unidades (codigo, tipo, estado, oficial_nombre, oficial_rango, sector) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssss", $codigo, $tipo, $estado, $oficial_nombre, $oficial_rango, $sector);
            }
            
            if ($stmt->execute()) {
                $success = true;
                $message = $id ? "Unidad actualizada correctamente" : "Unidad creada correctamente";
            } else {
                $message = "Error al guardar la unidad: " . $stmt->error;
            }
            $stmt->close();
            break;
            
        case 'emergencia':
            $id = !empty($_POST['id_emergencia']) ? intval($_POST['id_emergencia']) : null;
            $codigo = trim($_POST['codigo']);
            $descripcion = trim($_POST['descripcion']);
            $ubicacion = trim($_POST['ubicacion']);
            $unidades_asignadas = trim($_POST['unidades_asignadas']);
            $activa = intval($_POST['activa']);
            
            // Validar campos requeridos
            if (empty($codigo) || empty($descripcion) || empty($ubicacion)) {
                $message = "Código, descripción y ubicación son requeridos";
                break;
            }
            
            if ($id) {
                // Actualizar emergencia existente
                $stmt = $conn->prepare("UPDATE emergencias SET codigo=?, descripcion=?, ubicacion=?, unidades_asignadas=?, activa=? WHERE id=?");
                $stmt->bind_param("ssssii", $codigo, $descripcion, $ubicacion, $unidades_asignadas, $activa, $id);
            } else {
                // Insertar nueva emergencia
                $stmt = $conn->prepare("INSERT INTO emergencias (codigo, descripcion, ubicacion, unidades_asignadas, activa) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssi", $codigo, $descripcion, $ubicacion, $unidades_asignadas, $activa);
            }
            
            if ($stmt->execute()) {
                $success = true;
                $message = $id ? "Emergencia actualizada correctamente" : "Emergencia creada correctamente";
            } else {
                $message = "Error al guardar la emergencia: " . $stmt->error;
            }
            $stmt->close();
            break;
            
        case 'ubicacion':
            $id = !empty($_POST['id_ubicacion']) ? intval($_POST['id_ubicacion']) : null;
            $nombre = trim($_POST['nombre']);
            $descripcion = trim($_POST['descripcion']);
            $lat = trim($_POST['lat']);
            $lng = trim($_POST['lng']);
            $tipo = trim($_POST['tipo']);
            
            // Validar campos requeridos
            if (empty($nombre) || empty($lat) || empty($lng) || empty($tipo)) {
                $message = "Nombre, coordenadas y tipo son requeridos";
                break;
            }
            
            // Validar coordenadas
            if (!is_numeric($lat) || !is_numeric($lng)) {
                $message = "Las coordenadas deben ser valores numéricos";
                break;
            }
            
            $lat = floatval($lat);
            $lng = floatval($lng);
            
            if ($id) {
                // Actualizar ubicación existente
                $stmt = $conn->prepare("UPDATE ubicaciones SET nombre=?, descripcion=?, lat=?, lng=?, tipo=? WHERE id=?");
                $stmt->bind_param("ssddsi", $nombre, $descripcion, $lat, $lng, $tipo, $id);
            } else {
                // Insertar nueva ubicación
                $stmt = $conn->prepare("INSERT INTO ubicaciones (nombre, descripcion, lat, lng, tipo) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("ssdds", $nombre, $descripcion, $lat, $lng, $tipo);
            }
            
            if ($stmt->execute()) {
                $success = true;
                $message = $id ? "Ubicación actualizada correctamente" : "Ubicación creada correctamente";
            } else {
                $message = "Error al guardar la ubicación: " . $stmt->error;
            }
            $stmt->close();
            break;
            
        default:
            $message = "Tipo de operación no válido";
            break;
    }
} else {
    $message = "Método no permitido";
}

// Redirigir de vuelta a la página principal con mensaje
$redirect_url = "Radio.php";
if ($message) {
    $redirect_url .= "?message=" . urlencode($message) . "&success=" . ($success ? "1" : "0");
}

header("Location: $redirect_url");
exit;
?>