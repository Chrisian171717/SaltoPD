<?php 
// Configuración de conexión a la base de datos
$host = "localhost";
$user = "root";
$pass = "";
$db = "saltopd";

// Crear conexión
$conn = new mysqli($host, $user, $pass, $db);

// Verificar conexión
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// Recibir datos del formulario
$nombre     = $_POST['nombre'] ?? '';
$apellido   = $_POST['apellido'] ?? '';
$Num_Placa  = $_POST['placa'] ?? '';  // Cambiado a Num_Placa
$correo     = $_POST['correo'] ?? '';
$contrasena = $_POST['contrasena'] ?? '';
$rol        = $_POST['rol'] ?? 'usuario'; // Valor por defecto

// Validar que los campos no estén vacíos
if (empty($nombre) || empty($apellido) || empty($Num_Placa) || empty($correo) || empty($contrasena)) {
    die("Por favor, complete todos los campos.");
}

// Verificar si ya existe un usuario con ese correo o placa
$sql_check = "SELECT * FROM usuarios WHERE correo = ? OR Num_Placa = ?";
$stmt_check = $conn->prepare($sql_check);
$stmt_check->bind_param("ss", $correo, $Num_Placa);
$stmt_check->execute();
$result = $stmt_check->get_result();

if ($result->num_rows > 0) {
    die("Ya existe un usuario con ese correo o placa.");
}

// Hashear contraseña
$hash = password_hash($contrasena, PASSWORD_DEFAULT);

// Insertar nuevo usuario (ajustado a los nombres de tu tabla)
$sql = "INSERT INTO usuarios (nombre, apellido, Num_Placa, correo, contrasena, rol, fecha_registro) 
        VALUES (?, ?, ?, ?, ?, ?, NOW())";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssssss", $nombre, $apellido, $Num_Placa, $correo, $hash, $rol);

if ($stmt->execute()) {
    echo "Registro exitoso. Ahora puede iniciar sesión.";
    header("refresh:2; url=../Front-end/inicio de sesion.html"); 
} else {
    echo "Error al registrar: " . $conn->error;
}

// Cerrar conexiones
$stmt_check->close();
$stmt->close();
$conn->close();
?>