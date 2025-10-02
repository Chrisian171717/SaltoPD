<?php
session_start();
header('Content-Type: application/json');

// Validar que la solicitud sea POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Obtener y validar datos del formulario
$email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
$placa = strtoupper(trim($_POST['placa'] ?? ''));
$password = $_POST['password'] ?? '';
$confipassword = $_POST['confipassword'] ?? '';
$rol = $_POST['rolLogin'] ?? '';

// Validaciones básicas
$errors = [];

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Correo electrónico no válido';
}

if (!preg_match('/^[A-Z]{3}[0-9]{4}$/', $placa)) {
    $errors['placa'] = 'Formato de placa no válido. Debe ser ABC1234';
}

if (strlen($password) < 8) {
    $errors['password'] = 'La contraseña debe tener al menos 8 caracteres';
}

if ($password !== $confipassword) {
    $errors['confipassword'] = 'Las contraseñas no coinciden';
}

if (empty($rol) || !in_array($rol, ['policia', 'administrador'])) {
    $errors['rolLogin'] = 'Selecciona un rol válido';
}

// Si hay errores, devolverlos
if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

// Incluir conexión a la base de datos 
include "conexion.php";

// Buscar usuario en la base de datos 
$stmt = $conn->prepare("SELECT id, nombre, password, rol FROM usuarios WHERE email = ? AND placa = ? AND rol = ?");
$stmt->bind_param("sss", $email, $placa, $rol);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Credenciales incorrectas']);
    $stmt->close();
    $conn->close();
    exit;
}

$user = $result->fetch_assoc();

// Verificar contraseña (asumiendo que está hasheada con password_hash)
if (!password_verify($password, $user['password'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Credenciales incorrectas']);
    $stmt->close();
    $conn->close();
    exit;
}

// Iniciar sesión 
$_SESSION['user_id'] = $user['id'];
$_SESSION['user_name'] = $user['nombre'];
$_SESSION['user_email'] = $email;
$_SESSION['user_placa'] = $placa;
$_SESSION['user_role'] = $rol;

$stmt->close();
$conn->close();

// REDIRECCIÓN CORREGIDA: Ir a principal.html después del login
$redirect = 'principal.html';

echo json_encode(['success' => true, 'redirect' => $redirect]);
exit;
?>