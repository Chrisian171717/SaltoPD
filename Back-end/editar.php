<?php
include("conexion.php");

$id = $_GET["id"];
$result = mysqli_query($conn, "SELECT * FROM usuario WHERE id= $id");
$usuario = mysqli_fetch_assoc($result);

if ($_SERVER["REQUEST_METHOD"] == "POST"){
    $nombre = mysqli_real_escape_string($conn, $_POST['nombre']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);

    $sql = "UPDATE usuarios SET nombre= '$nombre', email= '$email' WHERE id= $id";
    if (mysqli_query($connt, $sql)){
        echo "Usuario actualizado correctamente.";

    }else{
        echo "Error: ".mysqli_error($conn);
   
  }
}
?>

<form method="POST" action="">
    Nombre: <input type="text" name="nombre" value="<?php echo $usuario['nombre']; ?>"><br>
    Email: <input type="email" name="email" value="<?php echo $usuario['email']; ?>"><br>
    <button type="submit">Actualizar</button>
</form>