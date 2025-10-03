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

// Función para cerrar la conexión manualmente
if (!function_exists('cerrarConexion')) {
    function cerrarConexion() {
        global $conn;
        if (isset($conn) && $conn instanceof mysqli) {
            // No usar ping() ya que puede causar problemas
            @$conn->close();
        }
    }
}

// Eliminar el cierre automático ya que PHP lo maneja automáticamente
// Las conexiones se cierran automáticamente al final del script
?>