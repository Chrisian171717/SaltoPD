<?php 
include("../ConexionSPD.php");

$host="localhost";
$user= "root";
$pass= "";
$db= "saltodp";

$conn=mysqli_connect($host,$user,$pass,$db);
$conn = new mysqli($host, $user, $pass, $db);


// Verifica la conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Verifica si se envió el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitiza y recoge los datos
    $nombre = htmlspecialchars(trim($_POST["nombre"]));
    $apellido = htmlspecialchars(trim($_POST["apellido"]));
    $placa = htmlspecialchars(trim($_POST["placa"]));
    $correo = filter_var(trim($_POST["correo"]), FILTER_SANITIZE_EMAIL);
    $contrasena = password_hash($_POST["contrasena"], PASSWORD_DEFAULT); // Encriptar contraseña

    // Validación básica
    if (!empty($nombre) && !empty($apellido) && !empty($placa) && filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        // Inserta en la base de datos
        $sql = "INSERT INTO usuarios (nombre, apellido, placa, correo, contrasena) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssss", $nombre, $apellido, $placa, $correo, $contrasena);

        if ($stmt->execute()) {
            echo "Registro exitoso 🎉";
        } else {
            echo "Error al registrar: " . $stmt->error;
        }

        $stmt->close();
    } else {
        echo "Por favor completa todos los campos correctamente.";
    }
}

$conn->close();

?>