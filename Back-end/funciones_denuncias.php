<?php
include("denuncias.php"); 

header("Content-Type: application/json; charset=UTF-8");

$accion = $_POST['accion'] ?? $_GET['accion'] ?? '';

switch ($accion) {
    case "agregar":
        $nombre = $_POST['nombre_civil'] ?? '';
        $codigo = $_POST['codigo_penal'] ?? '';
        $descripcion = $_POST['descripcion'] ?? '';
        $res = agregarDenuncia($nombre, $codigo, $descripcion);
        echo json_encode(["status" => $res === "success" ? "ok" : "error", "mensaje" => $res]);
        break;

    case "editar":
        $id = $_POST['id'] ?? 0;
        $nombre = $_POST['nombre_civil'] ?? '';
        $codigo = $_POST['codigo_penal'] ?? '';
        $descripcion = $_POST['descripcion'] ?? '';
        $res = editarDenuncia($id, $nombre, $codigo, $descripcion);
        echo json_encode(["status" => $res === "success" ? "ok" : "error", "mensaje" => $res]);
        break;

    case "eliminar":
        $id = $_POST['id'] ?? 0;
        $res = eliminarDenuncia($id);
        echo json_encode(["status" => $res === "success" ? "ok" : "error", "mensaje" => $res]);
        break;

    case "buscar":
        $busqueda = $_POST['busqueda'] ?? '';
        $codigo = $_POST['codigo_busqueda'] ?? '';
        $res = buscarDenuncias($busqueda, $codigo);
        echo json_encode(["status" => "ok", "data" => $res]);
        break;

    case "listar":
        $res = listarDenuncias();
        echo json_encode(["status" => "ok", "data" => $res]);
        break;

    default:
        echo json_encode(["status" => "error", "mensaje" => "Acción no válida"]);
        break;
}
