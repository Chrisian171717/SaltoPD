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
$confipassword = $_POST['confipassword'] ?? '';
$rol        = $_POST['rol'] ?? '';

// DEBUG - Registrar datos recibidos
error_log("======= REGISTRO NUEVO USUARIO =======");
error_log("📝 Nombre: $nombre");
error_log("📝 Apellido: $apellido");
error_log("🚔 Placa: $Num_Placa");
error_log("📧 Correo: $correo");
error_log("🔑 Contraseña: " . str_repeat('*', strlen($contrasena)));
error_log("🔑 Confirmación: " . str_repeat('*', strlen($confipassword)));
error_log("👮 Rol: $rol");

// Validar que los campos no estén vacíos
$campos_requeridos = [
    'nombre' => $nombre,
    'apellido' => $apellido,
    'placa' => $Num_Placa,
    'correo' => $correo,
    'contrasena' => $contrasena,
    'confirmación de contraseña' => $confipassword,
    'rol' => $rol
];

$campos_vacios = [];
foreach ($campos_requeridos as $campo => $valor) {
    if (empty(trim($valor))) {
        $campos_vacios[] = $campo;
    }
}

if (!empty($campos_vacios)) {
    die(json_encode([
        'success' => false,
        'message' => 'Por favor, complete todos los campos: ' . implode(', ', $campos_vacios)
    ]));
}

// Normalizar datos
$nombre = trim($nombre);
$apellido = trim($apellido);
$Num_Placa = strtoupper(trim($Num_Placa));
$correo = trim($correo);
$rol = trim($rol);

// Validar formato de placa
if (!preg_match('/^[A-Z]{3}[0-9]{4}$/', $Num_Placa)) {
    die(json_encode([
        'success' => false,
        'message' => 'Formato de placa inválido. Debe ser 3 letras seguidas de 4 números (ej: ABC1234).'
    ]));
}

// Validar formato de correo
if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    die(json_encode([
        'success' => false,
        'message' => 'Formato de correo electrónico inválido.'
    ]));
}

// Validar longitud de contraseña
if (strlen($contrasena) < 8) {
    die(json_encode([
        'success' => false,
        'message' => 'La contraseña debe tener al menos 8 caracteres.'
    ]));
}

// Validar que las contraseñas coincidan
if ($contrasena !== $confipassword) {
    die(json_encode([
        'success' => false,
        'message' => 'Las contraseñas no coinciden.'
    ]));
}

// Validar que el rol sea uno de los permitidos
$roles_permitidos = ['usuario', 'policia', 'administrador'];
if (!in_array($rol, $roles_permitidos)) {
    die(json_encode([
        'success' => false,
        'message' => 'Rol no válido. Los roles permitidos son: ' . implode(', ', $roles_permitidos)
    ]));
}

// Validar fortaleza de contraseña (opcional pero recomendado)
if (!preg_match('/[A-Z]/', $contrasena) || !preg_match('/[a-z]/', $contrasena) || !preg_match('/[0-9]/', $contrasena)) {
    die(json_encode([
        'success' => false,
        'message' => 'La contraseña debe contener al menos una letra mayúscula, una minúscula y un número.'
    ]));
}

// Verificar si ya existe un usuario con ese correo o placa
$sql_check = "SELECT id, correo, Num_Placa FROM usuarios WHERE correo = ? OR Num_Placa = ?";
$stmt_check = $conn->prepare($sql_check);

if (!$stmt_check) {
    die(json_encode([
        'success' => false,
        'message' => 'Error en la preparación de la consulta: ' . $conn->error
    ]));
}

$stmt_check->bind_param("ss", $correo, $Num_Placa);
$stmt_check->execute();
$result = $stmt_check->get_result();

if ($result->num_rows > 0) {
    $duplicados = [];
    while ($row = $result->fetch_assoc()) {
        if ($row['correo'] === $correo) {
            $duplicados[] = 'correo electrónico';
        }
        if ($row['Num_Placa'] === $Num_Placa) {
            $duplicados[] = 'placa';
        }
    }
    
    die(json_encode([
        'success' => false,
        'message' => 'Ya existe un usuario con ese ' . implode(' y ', $duplicados) . '.'
    ]));
}
$stmt_check->close();

// Hashear contraseña de forma segura
$hash = password_hash($contrasena, PASSWORD_DEFAULT);

// Verificar que el hash se creó correctamente
if ($hash === false) {
    die(json_encode([
        'success' => false,
        'message' => 'Error al crear el hash de la contraseña.'
    ]));
}

error_log("🔐 Hash de contraseña creado correctamente");

// Insertar nuevo usuario
$sql = "INSERT INTO usuarios (nombre, apellido, Num_Placa, correo, contrasena, rol, fecha_registro) 
        VALUES (?, ?, ?, ?, ?, ?, NOW())";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    die(json_encode([
        'success' => false,
        'message' => 'Error en la preparación de la consulta de inserción: ' . $conn->error
    ]));
}

$stmt->bind_param("ssssss", $nombre, $apellido, $Num_Placa, $correo, $hash, $rol);

if ($stmt->execute()) {
    error_log("✅ USUARIO REGISTRADO EXITOSAMENTE: $nombre $apellido ($correo)");
    
    // Obtener el ID del usuario recién insertado
    $user_id = $stmt->insert_id;
    
    // Registro exitoso - Redirigir inmediatamente
    $response = [
        'success' => true,
        'message' => 'Usuario registrado exitosamente',
        'redirect' => '../Front-end/InicioDeSesion.html',
        'user' => [
            'id' => $user_id,
            'nombre' => $nombre,
            'apellido' => $apellido,
            'correo' => $correo,
            'placa' => $Num_Placa,
            'rol' => $rol
        ]
    ];
    
    // Si es una solicitud AJAX, devolver JSON
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode($response);
    } else {
        // Redirección tradicional
        header("Location: ../Front-end/InicioDeSesion.html");
    }
    
    exit();
    
} else {
    error_log("❌ ERROR AL REGISTRAR USUARIO: " . $stmt->error);
    
    $error_message = "Ha ocurrido un error al crear tu cuenta: " . $stmt->error;
    
    // Si es una solicitud AJAX, devolver JSON
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => $error_message
        ]);
    } else {
        // Mostrar página de error tradicional
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
                .btn:hover {
                    background: #357ae8;
                }
            </style>
        </head>
        <body>
            <div class='error-message'>
                <div class='error-icon'>✗</div>
                <h1>Error en el Registro</h1>
                <p>$error_message</p>
                <a href='../Front-end/Registro.html' class='btn'>Volver al Registro</a>
            </div>
        </body>
        </html>";
    }
}

// Cerrar conexiones
if (isset($stmt)) {
    $stmt->close();
}
$conn->close();
?>