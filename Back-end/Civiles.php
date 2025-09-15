<?php
header('Content-Type: application/json');

include("conexion.php");

// --- CONFIGURACIÓN ---
$host = "localhost";
$user = "root";
$pass = "";
$db   = "saltopd";

// --- CONEXIÓN ---
$mysqli = new mysqli($host, $user, $pass, $db);
if ($mysqli->connect_errno) {
    echo json_encode(["success" => false, "message" => "Error de conexión: " . $mysqli->connect_error]);
    exit();
}

// --- CLASE CIVIL ---
class Civil {
    private $conn;

    public function __construct($mysqli) {
        $this->conn = $mysqli;
    }

    private function sanitize($data) {
        return htmlspecialchars($this->conn->real_escape_string(trim($data)));
    }

    // LISTAR TODOS
    public function list() {
        $result = $this->conn->query("SELECT * FROM civiles ORDER BY id DESC");
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    // BUSCAR CIVIL
    public function search($term) {
        $term = $this->sanitize($term);
        $stmt = $this->conn->prepare(
            "SELECT * FROM civiles WHERE nombre LIKE CONCAT('%', ?, '%') OR dni LIKE CONCAT('%', ?, '%') ORDER BY id DESC"
        );
        $stmt->bind_param("ss", $term, $term);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $data;
    }

    // AGREGAR CIVIL
    public function add($nombre, $dni) {
        $nombre = $this->sanitize($nombre);
        $dni = $this->sanitize($dni);
        if (!$nombre || !$dni) return ["success" => false, "message" => "Nombre o DNI vacío"];
        $stmt = $this->conn->prepare("INSERT INTO civiles (nombre, dni) VALUES (?, ?)");
        $stmt->bind_param("ss", $nombre, $dni);
        $stmt->execute();
        $id = $stmt->insert_id;
        $stmt->close();
        return ["success" => true, "id" => $id];
    }

    // EDITAR CIVIL
    public function edit($id, $nombre, $dni) {
        $id = intval($id);
        $nombre = $this->sanitize($nombre);
        $dni = $this->sanitize($dni);
        if (!$id || !$nombre || !$dni) return ["success" => false, "message" => "Datos inválidos"];
        $stmt = $this->conn->prepare("UPDATE civiles SET nombre=?, dni=? WHERE id=?");
        $stmt->bind_param("ssi", $nombre, $dni, $id);
        $stmt->execute();
        $stmt->close();
        return ["success" => true];
    }

    // ELIMINAR CIVIL
    public function delete($id) {
        $id = intval($id);
        if (!$id) return ["success" => false, "message" => "ID inválido"];
        $stmt = $this->conn->prepare("DELETE FROM civiles WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        return ["success" => true];
    }
}

// --- ACCIÓN ---
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$civil = new Civil($mysqli);

switch ($action) {
    case 'list':
        echo json_encode($civil->list());
        break;

    case 'search':
        $term = $_POST['search'] ?? '';
        echo json_encode($civil->search($term));
        break;

    case 'add':
        $nombre = $_POST['nombre'] ?? '';
        $dni = $_POST['dni'] ?? '';
        echo json_encode($civil->add($nombre, $dni));
        break;

    case 'edit':
        $id = $_POST['id'] ?? 0;
        $nombre = $_POST['nombre'] ?? '';
        $dni = $_POST['dni'] ?? '';
        echo json_encode($civil->edit($id, $nombre, $dni));
        break;

    case 'delete':
        $id = $_POST['id'] ?? 0;
        echo json_encode($civil->delete($id));
        break;

    default:
        echo json_encode(["success" => false, "message" => "Acción no válida"]);
        break;
}

$mysqli->close();
?>
