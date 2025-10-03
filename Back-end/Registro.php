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
$Num_Placa  = $_POST['placa'] ?? '';
$correo     = $_POST['correo'] ?? '';
$contrasena = $_POST['contrasena'] ?? '';
$rol        = $_POST['rol'] ?? '';

// Validar que los campos no estén vacíos
if (empty($nombre) || empty($apellido) || empty($Num_Placa) || empty($correo) || empty($contrasena) || empty($rol)) {
    die("Por favor, complete todos los campos.");
}

// Validar formato de placa
if (!preg_match('/^[A-Z]{3}[0-9]{4}$/', $Num_Placa)) {
    die("Formato de placa inválido. Debe ser ABC1234.");
}

// Validar formato de correo
if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    die("Formato de correo electrónico inválido.");
}

// Validar longitud de contraseña
if (strlen($contrasena) < 8) {
    die("La contraseña debe tener al menos 8 caracteres.");
}

// Validar que el rol sea uno de los permitidos
$roles_permitidos = ['usuario', 'policia', 'administrador'];
if (!in_array($rol, $roles_permitidos)) {
    die("Rol no válido.");
}

// Verificar si ya existe un usuario con ese correo o placa
$sql_check = "SELECT * FROM usuarios WHERE correo = ? OR Num_Placa = ?";
$stmt_check = $conn->prepare($sql_check);
$stmt_check->bind_param("ss", $correo, $Num_Placa);
$stmt_check->execute();
$result = $stmt_check->get_result();

if ($result->num_rows > 0) {
    // Determinar qué campo está duplicado
    $row = $result->fetch_assoc();
    if ($row['correo'] === $correo) {
        die("Ya existe un usuario con ese correo electrónico.");
    } else {
        die("Ya existe un usuario con esa placa.");
    }
}

// Hashear contraseña
$hash = password_hash($contrasena, PASSWORD_DEFAULT);

// Insertar nuevo usuario
$sql = "INSERT INTO usuarios (nombre, apellido, Num_Placa, correo, contrasena, rol, fecha_registro) 
        VALUES (?, ?, ?, ?, ?, ?, NOW())";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssssss", $nombre, $apellido, $Num_Placa, $correo, $hash, $rol);

if ($stmt->execute()) {
    // Registro exitoso - Redirigir inmediatamente
    header("Location: ../Front-end/InicioDeSesion.html");
    exit();
} else {
    // Error en el registro
    echo "<!DOCTYPE html>
    <html lang='es'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Error en Registro</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                background-color: #f5f5f5;
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
                margin: 0;
            }
            .error-message {
                background: white;
                padding: 2rem;
                border-radius: 8px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                text-align: center;
                max-width: 400px;
            }
            .error-icon {
                color: #f44336;
                font-size: 3rem;
                margin-bottom: 1rem;
            }
            h1 {
                color: #333;
                margin-bottom: 1rem;
            }
            p {
                color: #666;
                margin-bottom: 1.5rem;
            }
            .btn {
                background: #4a90e2;
                color: white;
                padding: 0.75rem 1.5rem;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                text-decoration: none;
                display: inline-block;
            }
        </style>
    </head>
    <body>
        <div class='error-message'>
            <div class='error-icon'>✗</div>
            <h1>Error en el Registro</h1>
            <p>Ha ocurrido un error al crear tu cuenta: " . $conn->error . "</p>
            <a href='../Front-end/Registro.html' class='btn'>Volver al Registro</a>
        </div>
    </body>
    </html>";
}

// Cerrar conexiones
$stmt_check->close();
if (isset($stmt)) {
    $stmt->close();
}
$conn->close();
?>