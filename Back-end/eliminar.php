<?php
include("conexion.php");

$id = $_GET["id"];

$sql = "DELETE FROM usuarios WHERE id= $id";
if (mysqli_query($conn, $sql)) {
    echo "usuarios eliminado correctamente";
  } else {
    echo "Error: ".mysqli_error($conn);
}
?>

<a href="listar.php">Volver</a>