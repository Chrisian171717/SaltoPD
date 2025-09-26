<?php
include("conexion.php");

error_reporting(E_ALL);
ini_set('display_errors', 1);

function sanitizarDato($dato) {
    return htmlspecialchars(trim($dato));
}

function refValues($arr) {
    $refs = [];
    foreach ($arr as $key => $value) {
        $refs[$key] = &$arr[$key];
    }
    return $refs;
}

function obtenerConexion() {
    $res = conectarDB();

    if (is_array($res)) {
        if (isset($res['success']) && $res['success'] === true && isset($res['connection'])) {
            return $res['connection'];
        } else {
            return false;
        }
    } elseif ($res instanceof mysqli) {
        return $res;
    } else {
        return false;
    }
}

function agregarDenuncia($nombre_civil, $codigo_penal, $descripcion) {
    $mysqli = obtenerConexion();
    if (!$mysqli) return "Error al conectar con la base de datos";

    $nombre_civil = sanitizarDato($nombre_civil);
    $codigo_penal = sanitizarDato($codigo_penal);
    $descripcion = sanitizarDato($descripcion);

    if (empty($nombre_civil) || empty($codigo_penal) || empty($descripcion)) {
        $mysqli->close();
        return "Todos los campos son obligatorios";
    }

    try {
        $stmt = $mysqli->prepare("INSERT INTO denuncias (nombre_civil, CodigoPenal, descripcion, Fecha) VALUES (?, ?, ?, NOW())");
        if (!$stmt) throw new Exception("Error al preparar consulta: " . $mysqli->error);

        $stmt->bind_param("sss", $nombre_civil, $codigo_penal, $descripcion);

        if (!$stmt->execute()) throw new Exception("Error al ejecutar consulta: " . $stmt->error);

        $stmt->close();
        $mysqli->close();
        return "success";

    } catch (Exception $e) {
        if (isset($stmt) && $stmt) $stmt->close();
        $mysqli->close();
        return $e->getMessage();
    }
}

function buscarDenuncias($busqueda = '', $codigo_busqueda = '') {
    $mysqli = obtenerConexion();
    if (!$mysqli) {
        error_log("Error al conectar DB en buscarDenuncias");
        return false;
    }

    $where = [];
    $params = [];
    $types = "";

    if (!empty($busqueda)) {
        $where[] = "nombre_civil LIKE CONCAT('%', ?, '%')";
        $params[] = $busqueda;
        $types .= "s";
    }
    if (!empty($codigo_busqueda)) {
        $where[] = "CodigoPenal LIKE CONCAT('%', ?, '%')";
        $params[] = $codigo_busqueda;
        $types .= "s";
    }

    $sql = "SELECT id, nombre_civil, CodigoPenal, descripcion, Fecha FROM denuncias";
    if (!empty($where)) $sql .= " WHERE " . implode(" AND ", $where);
    $sql .= " ORDER BY Fecha DESC LIMIT 100";

    try {
        $stmt = $mysqli->prepare($sql);
        if (!$stmt) throw new Exception("Error prepare: " . $mysqli->error);

        if (!empty($params)) {
            $bind_params = array_merge([$types], $params);
            call_user_func_array([$stmt, 'bind_param'], refValues($bind_params));
        }

        if (!$stmt->execute()) throw new Exception("Error execute: " . $stmt->error);

        $result = $stmt->get_result();
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }

        $stmt->close();
        $mysqli->close();
        return $rows;

    } catch (Exception $e) {
        error_log($e->getMessage());
        if (isset($stmt) && $stmt) $stmt->close();
        $mysqli->close();
        return false;
    }
}

function buscarDenunciaPorId($id) {
    $mysqli = obtenerConexion();
    if (!$mysqli) return false;

    $id = intval($id);
    if ($id <= 0) {
        $mysqli->close();
        return null;
    }

    try {
        $stmt = $mysqli->prepare("SELECT id, nombre_civil, CodigoPenal, descripcion, Fecha FROM denuncias WHERE id = ?");
        if (!$stmt) throw new Exception("Error prepare: " . $mysqli->error);

        $stmt->bind_param("i", $id);
        if (!$stmt->execute()) throw new Exception("Error execute: " . $stmt->error);

        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        $stmt->close();
        $mysqli->close();
        return $row ? $row : null;

    } catch (Exception $e) {
        error_log($e->getMessage());
        if (isset($stmt) && $stmt) $stmt->close();
        $mysqli->close();
        return false;
    }
}

function listarDenuncias($limit = 100) {
    $mysqli = obtenerConexion();
    if (!$mysqli) {
        error_log("Error al conectar DB en listarDenuncias");
        return false;
    }

    $limit = intval($limit);
    if ($limit <= 0) $limit = 100;

    try {
        $stmt = $mysqli->prepare("SELECT id, nombre_civil, CodigoPenal, descripcion, Fecha FROM denuncias ORDER BY Fecha DESC LIMIT ?");
        if (!$stmt) throw new Exception("Error prepare: " . $mysqli->error);

        $stmt->bind_param("i", $limit);
        if (!$stmt->execute()) throw new Exception("Error execute: " . $stmt->error);

        $result = $stmt->get_result();
        $rows = [];
        while ($row = $result->fetch_assoc()) $rows[] = $row;

        $stmt->close();
        $mysqli->close();
        return $rows;

    } catch (Exception $e) {
        error_log($e->getMessage());
        if (isset($stmt) && $stmt) $stmt->close();
        $mysqli->close();
        return false;
    }
}

function editarDenuncia($id, $nombre_civil, $codigo_penal, $descripcion) {
    $mysqli = obtenerConexion();
    if (!$mysqli) return "Error al conectar con la base de datos";

    $id = intval($id);
    if ($id <= 0) {
        $mysqli->close();
        return "ID inválido";
    }

    $nombre_civil = sanitizarDato($nombre_civil);
    $codigo_penal = sanitizarDato($codigo_penal);
    $descripcion = sanitizarDato($descripcion);

    if (empty($nombre_civil) || empty($codigo_penal) || empty($descripcion)) {
        $mysqli->close();
        return "Todos los campos son obligatorios";
    }

    try {
        $stmt = $mysqli->prepare("UPDATE denuncias SET nombre_civil = ?, CodigoPenal = ?, descripcion = ? WHERE id = ?");
        if (!$stmt) throw new Exception("Error al preparar consulta: " . $mysqli->error);

        $stmt->bind_param("sssi", $nombre_civil, $codigo_penal, $descripcion, $id);
        if (!$stmt->execute()) throw new Exception("Error al ejecutar consulta: " . $stmt->error);

        $stmt->close();
        $mysqli->close();
        return "success";

    } catch (Exception $e) {
        if (isset($stmt) && $stmt) $stmt->close();
        $mysqli->close();
        return $e->getMessage();
    }
}

function eliminarDenuncia($id) {
    $mysqli = obtenerConexion();
    if (!$mysqli) return "Error al conectar con la base de datos";

    $id = intval($id);
    if ($id <= 0) {
        $mysqli->close();
        return "ID inválido";
    }

    try {
        $stmt = $mysqli->prepare("DELETE FROM denuncias WHERE id = ?");
        if (!$stmt) throw new Exception("Error al preparar consulta: " . $mysqli->error);

        $stmt->bind_param("i", $id);
        if (!$stmt->execute()) throw new Exception("Error al ejecutar: " . $stmt->error);

        $stmt->close();
        $mysqli->close();
        return "success";

    } catch (Exception $e) {
        if (isset($stmt) && $stmt) $stmt->close();
        $mysqli->close();
        return $e->getMessage();
    }
}

function generarTablaDenuncias($denuncias, $showActions = true) {
    if ($denuncias === false) {
        return "<p class='error'>Error al conectar con la base de datos. Verifica la conexión.</p>";
    }

    if (empty($denuncias)) {
        return "<p class='no-data'>No se encontraron denuncias.</p>";
    }

    $html = "<table class='denuncias-table' border='1' cellpadding='6' cellspacing='0' style='border-collapse:collapse; width:100%;'>";
    $html .= "<thead><tr><th>ID</th><th>Nombre Civil</th><th>Código Penal</th><th>Descripción</th><th>Fecha</th>";
    if ($showActions) $html .= "<th>Acciones</th>";
    $html .= "</tr></thead><tbody>";

    foreach ($denuncias as $row) {
        $html .= "<tr>";
        $html .= "<td>" . htmlspecialchars($row['id']) . "</td>";
        $html .= "<td>" . htmlspecialchars($row['nombre_civil']) . "</td>";
        $html .= "<td>" . htmlspecialchars($row['CodigoPenal']) . "</td>";
        $html .= "<td>" . htmlspecialchars($row['descripcion']) . "</td>";
        $html .= "<td>" . htmlspecialchars($row['Fecha']) . "</td>";

        if ($showActions) {
            $html .= "<td style='text-align:center;'>";
            $html .= "<a href='editar.php?id=" . urlencode($row['id']) . "'>Editar</a> | ";
            $html .= "<a href='eliminar.php?id=" . urlencode($row['id']) . "' onclick=\"return confirm('¿Seguro que querés eliminar?')\">Eliminar</a>";
            $html .= "</td>";
        }

        $html .= "</tr>";
    }

    $html .= "</tbody></table>";
    $html .= "<p style='text-align: center; color: #666; margin-top: 15px;'>Mostrando " . count($denuncias) . " denuncia(s)</p>";

    return $html;
}
?>
