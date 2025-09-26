<?php
include("conexion.php");

$id = isset($_GET["id"]) ? (int) $_GET["id"] : 0;

if ($id <= 0) {
    die("ID inválido.");
}

$result = mysqli_query($conn, "SELECT * FROM usuarios WHERE id = $id");
$usuario = mysqli_fetch_assoc($result);

if (!$usuario) {
    die("Usuario no encontrado.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = mysqli_real_escape_string($conn, $_POST['nombre']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);

    $sql = "UPDATE usuarios SET nombre = '$nombre', email = '$email' WHERE id = $id";
    
    if (mysqli_query($conn, $sql)) {
        header("Location: listar.php");
        exit();
    } else {
        echo "Error al actualizar: " . mysqli_error($conn);
    }
}
?>

<h2>Editar usuario</h2>
<form method="POST" action="">
    <label>Nombre:</label>
    <input type="text" name="nombre" value="<?php echo htmlspecialchars($usuario['nombre']); ?>" required><br>

    <label>Email:</label>
    <input type="email" name="email" value="<?php echo htmlspecialchars($usuario['email']); ?>" required><br>

    <button type="submit">Actualizar</button>
</form>
