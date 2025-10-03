<?php
session_start();
header('Content-Type: application/json');

// Incluir conexión
include "conexion.php";

if (!verificarConexion()) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos']);
    exit;
}

// Obtener datos
$email = $_POST['email'] ?? '';
$placa = $_POST['placa'] ?? '';
$password = $_POST['password'] ?? '';
$confipassword = $_POST['confipassword'] ?? '';
$rol = $_POST['rolLogin'] ?? '';

// Limpiar datos
$email = trim($email);
$placa = strtoupper(trim($placa));

// DEBUG COMPLETO
error_log("======= DEBUG INICIO SESIÓN =======");
error_log("📧 Email recibido: '$email'");
error_log("🚔 Placa recibida: '$placa'");
error_log("👮 Rol recibido: '$rol'");
error_log("🔑 Password recibida: '" . str_repeat('*', strlen($password)) . "'");
error_log("🔑 Confirmación: '" . str_repeat('*', strlen($confipassword)) . "'");

// Validaciones
$errors = [];

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Correo electrónico no válido';
}

if (empty($placa) || !preg_match('/^[A-Z]{3}[0-9]{4}$/', $placa)) {
    $errors['placa'] = 'Formato de placa no válido. Debe ser ABC1234';
}

if (empty($password) || strlen($password) < 8) {
    $errors['password'] = 'La contraseña debe tener al menos 8 caracteres';
}

if (empty($confipassword) || $password !== $confipassword) {
    $errors['confipassword'] = 'Las contraseñas no coinciden';
}

if (empty($rol) || !in_array($rol, ['policia', 'administrador'])) {
    $errors['rolLogin'] = 'Selecciona un rol válido';
}

if (!empty($errors)) {
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

// PRIMERO: Mostrar TODOS los usuarios de la base de datos
error_log("--- TODOS LOS USUARIOS EN BD ---");
$all_sql = "SELECT id, nombre, apellido, correo, Num_Placa, rol, LENGTH(contrasena) as pass_len FROM usuarios";
$all_result = $conn->query($all_sql);
$all_users = [];
if ($all_result) {
    while ($row = $all_result->fetch_assoc()) {
        $all_users[] = $row;
        error_log("👤 Usuario: " . json_encode($row));
    }
} else {
    error_log("❌ Error al obtener usuarios: " . $conn->error);
}

// SEGUNDO: Buscar usuario EXACTO
error_log("--- BUSCANDO USUARIO EXACTO ---");
error_log("🔍 Buscando: email='$email', placa='$placa', rol='$rol'");

$sql = "SELECT id, nombre, apellido, contrasena, rol FROM usuarios WHERE correo = ? AND Num_Placa = ? AND rol = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    error_log("❌ Error en prepare: " . $conn->error);
    echo json_encode(['success' => false, 'message' => 'Error en la base de datos']);
    exit;
}

$stmt->bind_param("sss", $email, $placa, $rol);

if (!$stmt->execute()) {
    error_log("❌ Error en execute: " . $stmt->error);
    echo json_encode(['success' => false, 'message' => 'Error al buscar usuario']);
    exit;
}

$result = $stmt->get_result();
$user_count = $result->num_rows;

error_log("📊 Usuarios encontrados: $user_count");

if ($user_count === 0) {
    error_log("❌ USUARIO NO ENCONTRADO CON CRITERIOS EXACTOS");
    
    // TERCERO: Búsquedas parciales para diagnóstico
    error_log("--- BÚSQUEDAS PARCIALES ---");
    
    // Buscar solo por email
    $email_sql = "SELECT correo, Num_Placa, rol FROM usuarios WHERE correo = ?";
    $email_stmt = $conn->prepare($email_sql);
    if ($email_stmt) {
        $email_stmt->bind_param("s", $email);
        $email_stmt->execute();
        $email_result = $email_stmt->get_result();
        $email_users = [];
        while ($row = $email_result->fetch_assoc()) {
            $email_users[] = $row;
        }
        $email_stmt->close();
        error_log("📧 Usuarios con email '$email': " . json_encode($email_users));
    }
    
    // Buscar solo por placa
    $placa_sql = "SELECT correo, Num_Placa, rol FROM usuarios WHERE Num_Placa = ?";
    $placa_stmt = $conn->prepare($placa_sql);
    if ($placa_stmt) {
        $placa_stmt->bind_param("s", $placa);
        $placa_stmt->execute();
        $placa_result = $placa_stmt->get_result();
        $placa_users = [];
        while ($row = $placa_result->fetch_assoc()) {
            $placa_users[] = $row;
        }
        $placa_stmt->close();
        error_log("🚔 Usuarios con placa '$placa': " . json_encode($placa_users));
    }
    
    // Buscar solo por rol
    $rol_sql = "SELECT correo, Num_Placa, rol FROM usuarios WHERE rol = ?";
    $rol_stmt = $conn->prepare($rol_sql);
    if ($rol_stmt) {
        $rol_stmt->bind_param("s", $rol);
        $rol_stmt->execute();
        $rol_result = $rol_stmt->get_result();
        $rol_users = [];
        while ($row = $rol_result->fetch_assoc()) {
            $rol_users[] = $row;
        }
        $rol_stmt->close();
        error_log("👮 Usuarios con rol '$rol': " . json_encode($rol_users));
    }
    
    echo json_encode([
        'success' => false, 
        'message' => 'Usuario no encontrado con estas credenciales exactas.',
        'debug_info' => [
            'buscado' => [
                'email' => $email,
                'placa' => $placa, 
                'rol' => $rol
            ],
            'usuarios_con_email' => $email_users ?? [],
            'usuarios_con_placa' => $placa_users ?? [],
            'usuarios_con_rol' => $rol_users ?? [],
            'todos_los_usuarios' => $all_users
        ]
    ]);
    exit;
}

// USUARIO ENCONTRADO
$user = $result->fetch_assoc();
error_log("✅ USUARIO ENCONTRADO: " . $user['nombre'] . " " . $user['apellido']);
error_log("🔑 Contraseña almacenada: " . $user['contrasena']);
error_log("📏 Longitud contraseña: " . strlen($user['contrasena']));

// VERIFICAR CONTRASEÑA
$stored_password = $user['contrasena'];
$passwordValid = false;
$passwordMethod = 'none';

error_log("--- VERIFICANDO CONTRASEÑA ---");

// Método 1: Password hash
if (password_verify($password, $stored_password)) {
    $passwordValid = true;
    $passwordMethod = 'password_hash';
    error_log("✅ Contraseña válida (password_hash)");
} 
// Método 2: Texto plano
else if ($password === $stored_password) {
    $passwordValid = true;
    $passwordMethod = 'texto_plano';
    error_log("✅ Contraseña válida (texto plano)");
}
// Método 3: MD5
else if (md5($password) === $stored_password) {
    $passwordValid = true;
    $passwordMethod = 'md5';
    error_log("✅ Contraseña válida (md5)");
}

if (!$passwordValid) {
    error_log("❌ CONTRASEÑA INCORRECTA");
    error_log("🔑 Contraseña recibida: '$password'");
    error_log("🛠️ Método usado: $passwordMethod");
    
    echo json_encode([
        'success' => false, 
        'message' => 'Contraseña incorrecta.',
        'debug_info' => 'Método de verificación: ' . $passwordMethod
    ]);
    $stmt->close();
    exit;
}

// ✅ LOGIN EXITOSO
error_log("🎉 LOGIN EXITOSO - Usuario: " . $user['nombre'] . " " . $user['apellido']);
error_log("🛠️ Método de contraseña: $passwordMethod");

$_SESSION['user_id'] = $user['id'];
$_SESSION['user_name'] = $user['nombre'] . ' ' . $user['apellido'];
$_SESSION['user_email'] = $email;
$_SESSION['user_placa'] = $placa;
$_SESSION['user_role'] = $rol;

$stmt->close();

echo json_encode([
    'success' => true, 
    'redirect' => 'principal.html',
    'message' => '¡Inicio de sesión exitoso!',
    'user' => [
        'name' => $user['nombre'] . ' ' . $user['apellido'],
        'email' => $email,
        'placa' => $placa,
        'rol' => $rol
    ]
]);
exit;
?>