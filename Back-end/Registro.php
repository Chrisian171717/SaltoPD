<?php 
include("conexion.php");

$host="localhost";
$user= "root";
$pass= "";
$db= "saltopd";

$conn=mysqli_connect($host,$user,$pass,$db);
$conn = new mysqli($host, $user, $pass, $db);


// Recibir datos del formulario
$nombre     = $_POST['nombre'] ?? '';
$apellido   = $_POST['apellido'] ?? '';
$placa      = $_POST['placa'] ?? '';
$correo     = $_POST['correo'] ?? '';
$contrasena = $_POST['contrasena'] ?? '';
$rol        = $_POST['rol'] ?? '';

// Validar que los campos no estén vacíos
if (empty($nombre) || empty($apellido) || empty($placa) || empty($correo) || empty($contrasena) || empty($rol)) {
    die("Por favor, complete todos los campos.");
}

// Verificar si ya existe un usuario con ese correo o placa
$sql_check = "SELECT * FROM usuarios WHERE correo = ? OR placa = ?";
$stmt_check = $conn->prepare($sql_check);
$stmt_check->bind_param("ss", $correo, $placa);
$stmt_check->execute();
$result = $stmt_check->get_result();

if ($result->num_rows > 0) {
    die("Ya existe un usuario con ese correo o placa.");
}

// Hashear contraseña
$hash = password_hash($contrasena, PASSWORD_DEFAULT);

// Insertar nuevo usuario
$sql = "INSERT INTO usuarios (nombre, apellido, placa, correo, contrasena, rol) VALUES (?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssssss", $nombre, $apellido, $placa, $correo, $hash, $rol);

if ($stmt->execute()) {
    echo "Registro exitoso. Ahora puede iniciar sesión.";
    header("refresh:2; url=../Front-end/inicio_sesion.html"); 
} else {
    echo "Error al registrar: " . $conn->error;
}

$conn->close();
?>