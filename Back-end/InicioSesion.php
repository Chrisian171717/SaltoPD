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

// DEBUG
error_log("======= INICIO SESIÓN =======");
error_log("📧 Email: '$email'");
error_log("🚔 Placa: '$placa'");
error_log("👮 Rol: '$rol'");

// Validaciones básicas
$errors = [];

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Correo electrónico no válido';
}

if (empty($placa) || !preg_match('/^[A-Z]{3}[0-9]{4}$/', $placa)) {
    $errors['placa'] = 'Formato de placa no válido. Debe ser ABC1234';
}

if (empty($password)) {
    $errors['password'] = 'La contraseña es requerida';
}

// Solo validar confirmación si se proporciona
if (!empty($confipassword) && $password !== $confipassword) {
    $errors['confipassword'] = 'Las contraseñas no coinciden';
}

// Solo permitir policia y administrador
if (empty($rol) || !in_array($rol, ['policia', 'administrador'])) {
    $errors['rolLogin'] = 'Selecciona un rol válido (Policía o Administrador)';
}

if (!empty($errors)) {
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

// BUSCAR USUARIO
error_log("--- BUSCANDO USUARIO ---");
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
    error_log("❌ USUARIO NO ENCONTRADO");
    
    // Búsquedas parciales para diagnóstico
    $partial_errors = [];
    
    // Verificar email
    $email_check = $conn->prepare("SELECT correo FROM usuarios WHERE correo = ?");
    $email_check->bind_param("s", $email);
    $email_check->execute();
    $email_check->store_result();
    if ($email_check->num_rows === 0) {
        $partial_errors[] = "El correo no existe";
    }
    $email_check->close();
    
    // Verificar placa
    $placa_check = $conn->prepare("SELECT Num_Placa FROM usuarios WHERE Num_Placa = ?");
    $placa_check->bind_param("s", $placa);
    $placa_check->execute();
    $placa_check->store_result();
    if ($placa_check->num_rows === 0) {
        $partial_errors[] = "La placa no existe";
    }
    $placa_check->close();
    
    // Verificar combinación email-placa
    $combo_check = $conn->prepare("SELECT correo, Num_Placa FROM usuarios WHERE correo = ? AND Num_Placa = ?");
    $combo_check->bind_param("ss", $email, $placa);
    $combo_check->execute();
    $combo_check->store_result();
    if ($combo_check->num_rows === 0) {
        $partial_errors[] = "La combinación de correo y placa no coincide";
    }
    $combo_check->close();
    
    echo json_encode([
        'success' => false, 
        'message' => 'Credenciales incorrectas. ' . implode(', ', $partial_errors)
    ]);
    exit;
}

// USUARIO ENCONTRADO - VERIFICAR CONTRASEÑA
$user = $result->fetch_assoc();
error_log("✅ USUARIO ENCONTRADO: " . $user['nombre'] . " " . $user['apellido']);

// VERIFICAR CONTRASEÑA - Método principal: password_verify
if (password_verify($password, $user['contrasena'])) {
    error_log("✅ Contraseña válida (password_hash)");
    
    // LOGIN EXITOSO
    error_log("🎉 LOGIN EXITOSO");
    
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['nombre'] . ' ' . $user['apellido'];
    $_SESSION['user_email'] = $email;
    $_SESSION['user_placa'] = $placa;
    $_SESSION['user_role'] = $rol;
    
    // Crear ID único para el perfil
    $_SESSION['profile_id'] = uniqid('profile_');
    $_SESSION['profile_created_at'] = date('d/m/Y H:i:s');
    
    // =============================================
    // 🎯 REDIRECCIÓN SEGÚN EL ROL - CORREGIDO
    // =============================================
    $redirect_url = '';
    if ($rol === 'administrador') {
        $redirect_url = 'PrincipalAd.html';
    } else {
        $redirect_url = 'principal.html';
    }
    
    error_log("🎯 Redirigiendo a: $redirect_url");
    
    $stmt->close();
    
    echo json_encode([
        'success' => true, 
        'redirect' => $redirect_url,
        'message' => '¡Inicio de sesión exitoso!',
        'user' => [
            'id' => $user['id'],
            'name' => $user['nombre'] . ' ' . $user['apellido'],
            'rol' => $rol,
            'role' => $rol
        ],
        'email' => $email,
        'placa' => $placa
    ]);
    exit;
} else {
    error_log("❌ CONTRASEÑA INCORRECTA");
    echo json_encode([
        'success' => false, 
        'message' => 'Contraseña incorrecta.'
    ]);
}

$stmt->close();
?>