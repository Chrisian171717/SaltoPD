<?php
include("conexion.php");

if($_SERVER["REQUEST_METHOD"] == "POST"){
    $nombre = mysqli_real_escape_string($conn, $_POST['nombre']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);

    if (!empty($nombre) && !empty($email)) {
        $sql = "INSERT INTO usuario (nombre, email) values ('$nombre', '$email')";

        if (mysqli_query($conn, $sql)) {
            echo "Usuario agregado correctamente";
        } else {
            echo "Error: " .mysqli_error($conn) ;
        }
    } else {
        echo "porfavor complete todos los campos";
    }   
}

?>

<form method="POST" action="">
    Nombre: <input type="text" name="nombre"> <br>
    Email: <input type="text" name="email"> <br>
    <button type="submit">

</form>