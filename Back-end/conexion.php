<?php
// conexion.php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "saltopd";

// Verificar si la función ya existe antes de declararla
if (!function_exists('conectarDB')) {
    function conectarDB() {
        global $host, $user, $pass, $db;
        
        $mysqli = new mysqli($host, $user, $pass, $db);
        
        if ($mysqli->connect_errno) {
            error_log("Error de conexión MySQL: " . $mysqli->connect_error);
            return false;
        }
        
        $mysqli->set_charset("utf8mb4");
        return $mysqli;
    }
}

// Verificar si la conexión global ya existe
if (!isset($conn)) {
    $conn = conectarDB();
}

// Verificar si la función de verificación ya existe
if (!function_exists('verificarConexion')) {
    function verificarConexion() {
        global $conn;
        if (!$conn || $conn->connect_error) {
            return false;
        }
        return true;
    }
}

// Función adicional para manejar errores de consulta
if (!function_exists('ejecutarConsulta')) {
    function ejecutarConsulta($sql) {
        global $conn;
        if (!verificarConexion()) {
            error_log("No hay conexión a la base de datos");
            return false;
        }
        
        $resultado = $conn->query($sql);
        if (!$resultado) {
            error_log("Error en consulta SQL: " . $conn->error);
            return false;
        }
        
        return $resultado;
    }
}

// Función para obtener el último ID insertado
if (!function_exists('obtenerUltimoId')) {
    function obtenerUltimoId() {
        global $conn;
        if (verificarConexion()) {
            return $conn->insert_id;
        }
        return false;
    }
}

// Función para escapar strings y prevenir SQL injection
if (!function_exists('escaparString')) {
    function escaparString($string) {
        global $conn;
        if (verificarConexion()) {
            return $conn->real_escape_string($string);
        }
        return $string;
    }
}

// Función para cerrar la conexión manualmente - CORREGIDA
if (!function_exists('cerrarConexion')) {
    function cerrarConexion() {
        global $conn;
        if (isset($conn) && $conn instanceof mysqli && !$conn->connect_error) {
            @$conn->close();
        }
    }
}

// Función para crear las tablas del sistema de escaneo si no existen - CORREGIDA
if (!function_exists('crearTablasEscaneo')) {
    function crearTablasEscaneo() {
        global $conn; // Añadido global $conn
        
        if (!verificarConexion()) {
            return false;
        }
        
        $tablas = [
            "documentos_escaneo" => "
                CREATE TABLE IF NOT EXISTS documentos_escaneo (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    session_id VARCHAR(100) NOT NULL,
                    tipo_documento ENUM('id', 'passport', 'driver', 'other') NOT NULL,
                    nombre_documento VARCHAR(255) NOT NULL,
                    datos_ocr TEXT,
                    confianza DECIMAL(5,2),
                    calidad VARCHAR(50),
                    imagen_data LONGBLOB,
                    imagen_tipo VARCHAR(50),
                    imagen_tamanio INT,
                    ruta_imagen VARCHAR(500),
                    campos_detectados TEXT,
                    es_valido BOOLEAN DEFAULT FALSE,
                    tasa_completitud DECIMAL(5,2),
                    timestamp_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    timestamp_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_session_id (session_id),
                    INDEX idx_tipo_documento (tipo_documento),
                    INDEX idx_timestamp (timestamp_creacion)
                )
            ",
            "rostros_escaneo" => "
                CREATE TABLE IF NOT EXISTS rostros_escaneo (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    session_id VARCHAR(100) NOT NULL,
                    face_id VARCHAR(100) NOT NULL,
                    landmarks INT,
                    confianza DECIMAL(5,2),
                    tiempo_escaneo DECIMAL(5,2),
                    edad_estimada INT,
                    genero ENUM('male', 'female'),
                    expresion VARCHAR(50),
                    tiene_lentes BOOLEAN,
                    imagen_data LONGBLOB,
                    imagen_tipo VARCHAR(50),
                    imagen_tamanio INT,
                    ruta_imagen VARCHAR(500),
                    datos_biometricos TEXT,
                    calidad_analisis TEXT,
                    timestamp_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_session_id (session_id),
                    INDEX idx_face_id (face_id),
                    INDEX idx_timestamp (timestamp_creacion)
                )
            ",
            "verificaciones_escaneo" => "
                CREATE TABLE IF NOT EXISTS verificaciones_escaneo (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    verification_id VARCHAR(100) UNIQUE NOT NULL,
                    session_id VARCHAR(100) NOT NULL,
                    documento_id INT,
                    rostro_id INT,
                    datos_faciales TEXT,
                    datos_documentales TEXT,
                    info_sistema TEXT,
                    resultado_verificacion TEXT,
                    puntaje_confianza DECIMAL(5,2),
                    evaluacion_riesgo ENUM('muy_bajo', 'bajo', 'medio', 'alto'),
                    estado ENUM('pendiente', 'procesando', 'completado', 'error') DEFAULT 'pendiente',
                    timestamp_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    timestamp_procesado TIMESTAMP NULL,
                    INDEX idx_verification_id (verification_id),
                    INDEX idx_session_id (session_id),
                    INDEX idx_estado (estado)
                )
            "
        ];
        
        foreach ($tablas as $nombre => $sql) {
            $resultado = ejecutarConsulta($sql);
            if (!$resultado) {
                // CORREGIDO: Usar $conn->error directamente
                error_log("Error creando tabla $nombre: " . (isset($conn->error) ? $conn->error : 'Error desconocido'));
                return false;
            }
        }
        
        return true;
    }
}

// ============================================================
// NUEVAS FUNCIONES PARA REGISTRO DE ACTIVIDADES
// ============================================================

// Función para registrar actividades del sistema
if (!function_exists('registrarActividad')) {
    function registrarActividad($conn, $usuario, $accion, $modulo, $registro_id = null) {
        if (!verificarConexion()) {
            error_log("No se pudo registrar actividad: sin conexión a BD");
            return false;
        }
        
        $usuario = $conn->real_escape_string($usuario);
        $accion = $conn->real_escape_string($accion);
        $modulo = $conn->real_escape_string($modulo);
        $registro_id_sql = $registro_id !== null ? intval($registro_id) : 'NULL';
        
        $sql = "INSERT INTO registro_actividades (usuario, accion, modulo, registro_id, fecha) 
                VALUES ('$usuario', '$accion', '$modulo', $registro_id_sql, NOW())";
        
        $resultado = $conn->query($sql);
        
        if (!$resultado) {
            error_log("Error registrando actividad: " . $conn->error);
            return false;
        }
        
        return true;
    }
}

// Función para crear la tabla de registro de actividades
if (!function_exists('crearTablaRegistroActividades')) {
    function crearTablaRegistroActividades() {
        global $conn;
        
        if (!verificarConexion()) {
            return false;
        }
        
        $sql = "CREATE TABLE IF NOT EXISTS registro_actividades (
            id INT AUTO_INCREMENT PRIMARY KEY,
            usuario VARCHAR(100) NOT NULL,
            accion TEXT NOT NULL,
            modulo VARCHAR(50) NOT NULL,
            registro_id INT NULL,
            fecha DATETIME NOT NULL,
            INDEX idx_fecha (fecha),
            INDEX idx_modulo (modulo),
            INDEX idx_usuario (usuario)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $resultado = ejecutarConsulta($sql);
        
        if (!$resultado) {
            error_log("Error creando tabla registro_actividades: " . $conn->error);
            return false;
        }
        
        return true;
    }
}

// Crear las tablas automáticamente al incluir este archivo
crearTablasEscaneo();
crearTablaRegistroActividades(); // <- NUEVA LÍNEA
?>