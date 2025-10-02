<?php
// denuncias.php - Funciones de base de datos usando conexion.php
include("conexion.php");

class DenunciasDB {
    private $conn;
    
    public function __construct() {
        global $conn;
        $this->conn = $conn;
        
        if (!$this->conn || $this->conn->connect_error) {
            throw new Exception("Error de conexión a la base de datos");
        }
    }
    
    public function agregarDenuncia($nombre_civil, $codigo_penal, $descripcion) {
        try {
            // Verificar conexión
            if (!$this->conn || $this->conn->connect_error) {
                return "Error: No hay conexión a la base de datos";
            }
            
            $stmt = $this->conn->prepare("INSERT INTO denuncias (nombre_civil, CodigoPenal, descripcion) VALUES (?, ?, ?)");
            if (!$stmt) {
                return "Error en la preparación: " . $this->conn->error;
            }
            
            $stmt->bind_param("sss", $nombre_civil, $codigo_penal, $descripcion);
            
            if ($stmt->execute()) {
                return "success";
            } else {
                return "Error: " . $stmt->error;
            }
        } catch (Exception $e) {
            return "Error: " . $e->getMessage();
        }
    }
    
    public function editarDenuncia($id, $nombre_civil, $codigo_penal, $descripcion) {
        try {
            // Verificar conexión
            if (!$this->conn || $this->conn->connect_error) {
                return "Error: No hay conexión a la base de datos";
            }
            
            // Verificar que la denuncia existe
            $check_stmt = $this->conn->prepare("SELECT id FROM denuncias WHERE id = ?");
            if (!$check_stmt) {
                return "Error en la preparación: " . $this->conn->error;
            }
            
            $check_stmt->bind_param("i", $id);
            $check_stmt->execute();
            $check_stmt->store_result();
            
            if ($check_stmt->num_rows === 0) {
                $check_stmt->close();
                return "Error: La denuncia no existe";
            }
            $check_stmt->close();
            
            // Actualizar la denuncia
            $stmt = $this->conn->prepare("UPDATE denuncias SET nombre_civil = ?, CodigoPenal = ?, descripcion = ? WHERE id = ?");
            if (!$stmt) {
                return "Error en la preparación: " . $this->conn->error;
            }
            
            $stmt->bind_param("sssi", $nombre_civil, $codigo_penal, $descripcion, $id);
            
            if ($stmt->execute()) {
                return "success";
            } else {
                return "Error: " . $stmt->error;
            }
        } catch (Exception $e) {
            return "Error: " . $e->getMessage();
        }
    }
    
    public function eliminarDenuncia($id) {
        try {
            // Verificar conexión
            if (!$this->conn || $this->conn->connect_error) {
                return "Error: No hay conexión a la base de datos";
            }
            
            // Verificar que la denuncia existe
            $check_stmt = $this->conn->prepare("SELECT id FROM denuncias WHERE id = ?");
            if (!$check_stmt) {
                return "Error en la preparación: " . $this->conn->error;
            }
            
            $check_stmt->bind_param("i", $id);
            $check_stmt->execute();
            $check_stmt->store_result();
            
            if ($check_stmt->num_rows === 0) {
                $check_stmt->close();
                return "Error: La denuncia no existe";
            }
            $check_stmt->close();
            
            // Eliminar la denuncia
            $stmt = $this->conn->prepare("DELETE FROM denuncias WHERE id = ?");
            if (!$stmt) {
                return "Error en la preparación: " . $this->conn->error;
            }
            
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                return "success";
            } else {
                return "Error: " . $stmt->error;
            }
        } catch (Exception $e) {
            return "Error: " . $e->getMessage();
        }
    }
    
    public function listarDenuncias() {
        try {
            // Verificar conexión
            if (!$this->conn || $this->conn->connect_error) {
                error_log("Error: No hay conexión a la base de datos");
                return [];
            }
            
            $result = $this->conn->query("SELECT id, nombre_civil, CodigoPenal, descripcion, Fecha FROM denuncias ORDER BY Fecha DESC");
            if (!$result) {
                error_log("Error en consulta: " . $this->conn->error);
                return [];
            }
            
            $denuncias = [];
            while ($row = $result->fetch_assoc()) {
                $denuncias[] = $row;
            }
            
            return $denuncias;
        } catch (Exception $e) {
            error_log("Error en listarDenuncias: " . $e->getMessage());
            return [];
        }
    }
    
    public function buscarDenuncias($busqueda, $codigo) {
        try {
            // Verificar conexión
            if (!$this->conn || $this->conn->connect_error) {
                error_log("Error: No hay conexión a la base de datos");
                return [];
            }
            
            $sql = "SELECT id, nombre_civil, CodigoPenal, descripcion, Fecha FROM denuncias WHERE 1=1";
            $params = [];
            $types = "";
            
            if (!empty($busqueda)) {
                $sql .= " AND (nombre_civil LIKE ? OR descripcion LIKE ?)";
                $searchTerm = "%$busqueda%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $types .= "ss";
            }
            
            if (!empty($codigo)) {
                $sql .= " AND CodigoPenal LIKE ?";
                $params[] = "%$codigo%";
                $types .= "s";
            }
            
            $sql .= " ORDER BY Fecha DESC";
            
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                error_log("Error en preparación de búsqueda: " . $this->conn->error);
                return [];
            }
            
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            $denuncias = [];
            
            while ($row = $result->fetch_assoc()) {
                $denuncias[] = $row;
            }
            
            return $denuncias;
        } catch (Exception $e) {
            error_log("Error en buscarDenuncias: " . $e->getMessage());
            return [];
        }
    }
    
    // Función para obtener estadísticas
    public function obtenerEstadisticas() {
        try {
            // Verificar conexión
            if (!$this->conn || $this->conn->connect_error) {
                return [
                    'total' => 0,
                    'hoy' => 0,
                    'mes' => 0,
                    'resueltas' => 0
                ];
            }
            
            // Total de denuncias
            $result = $this->conn->query("SELECT COUNT(*) as total FROM denuncias");
            $total = $result ? $result->fetch_assoc()['total'] : 0;
            
            // Denuncias de hoy
            $result = $this->conn->query("SELECT COUNT(*) as hoy FROM denuncias WHERE DATE(Fecha) = CURDATE()");
            $hoy = $result ? $result->fetch_assoc()['hoy'] : 0;
            
            // Denuncias del mes
            $result = $this->conn->query("SELECT COUNT(*) as mes FROM denuncias WHERE MONTH(Fecha) = MONTH(CURDATE()) AND YEAR(Fecha) = YEAR(CURDATE())");
            $mes = $result ? $result->fetch_assoc()['mes'] : 0;
            
            // Denuncias resueltas (asumiendo que hay un campo 'estado')
            $result = $this->conn->query("SELECT COUNT(*) as resueltas FROM denuncias");
            $resueltas = $result ? $result->fetch_assoc()['resueltas'] : 0;
            
            return [
                'total' => (int)$total,
                'hoy' => (int)$hoy,
                'mes' => (int)$mes,
                'resueltas' => (int)$resueltas
            ];
        } catch (Exception $e) {
            error_log("Error en obtenerEstadisticas: " . $e->getMessage());
            return [
                'total' => 0,
                'hoy' => 0,
                'mes' => 0,
                'resueltas' => 0
            ];
        }
    }
}

// Funciones globales
function agregarDenuncia($nombre, $codigo, $descripcion) {
    try {
        $db = new DenunciasDB();
        return $db->agregarDenuncia($nombre, $codigo, $descripcion);
    } catch (Exception $e) {
        return "Error: " . $e->getMessage();
    }
}

function editarDenuncia($id, $nombre, $codigo, $descripcion) {
    try {
        $db = new DenunciasDB();
        return $db->editarDenuncia($id, $nombre, $codigo, $descripcion);
    } catch (Exception $e) {
        return "Error: " . $e->getMessage();
    }
}

function eliminarDenuncia($id) {
    try {
        $db = new DenunciasDB();
        return $db->eliminarDenuncia($id);
    } catch (Exception $e) {
        return "Error: " . $e->getMessage();
    }
}

function listarDenuncias() {
    try {
        $db = new DenunciasDB();
        return $db->listarDenuncias();
    } catch (Exception $e) {
        error_log("Error en listarDenuncias global: " . $e->getMessage());
        return [];
    }
}

function buscarDenuncias($busqueda, $codigo) {
    try {
        $db = new DenunciasDB();
        return $db->buscarDenuncias($busqueda, $codigo);
    } catch (Exception $e) {
        error_log("Error en buscarDenuncias global: " . $e->getMessage());
        return [];
    }
}

function obtenerEstadisticas() {
    try {
        $db = new DenunciasDB();
        return $db->obtenerEstadisticas();
    } catch (Exception $e) {
        error_log("Error en obtenerEstadisticas global: " . $e->getMessage());
        return [
            'total' => 0,
            'hoy' => 0,
            'mes' => 0,
            'resueltas' => 0
        ];
    }
}

// Función para verificar la conexión
function verificarConexionBD() {
    try {
        $db = new DenunciasDB();
        return true;
    } catch (Exception $e) {
        return false;
    }
}
?>