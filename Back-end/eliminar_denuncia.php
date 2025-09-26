<?php
include("conexion.php");

$id = isset($_GET["id"]) ? (int) $_GET["id"] : 0;

if ($id <= 0) {
    die("ID inválido.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $sql = "DELETE FROM usuarios WHERE id = $id";
    if (mysqli_query($conn, $sql)) {
        header("Location: listar.php");
        exit();
    } else {
        echo "Error al eliminar: " . mysqli_error($conn);
    }
}
?>

<h2>¿Seguro que quieres eliminar este usuario?</h2>
<form method="POST">
    <button type="submit">Sí, eliminar</button>
    <a href="listar.php">Cancelar</a>
</form>
