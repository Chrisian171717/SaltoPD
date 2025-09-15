<?php 
include("conexion.php");

// Iniciar sesión
session_start();

// Si el usuario no está logueado, lo mandamos al login
if (!isset($_SESSION['usuario_id'])) {
    header("Location: IniciarSesion.php");
    exit();
}

// ===== Conexión a la base de datos =====
$host = "localhost";   // Servidor
$user = "root";        // Usuario de BD
$pass = "";            // Contraseña (vacío en XAMPP)
$db   = "saltopd";     // Nombre de la BD

$conn = new mysqli($host, $user, $pass, $db);

// Verificar conexión
if ($conn->connect_error) {
    die("Error en la conexión a la BD: " . $conn->connect_error);
}

// Recibir datos del formulario
$email        = $_POST['email'] ?? '';
$placa        = $_POST['placa'] ?? '';
$password     = $_POST['password'] ?? '';
$confipass    = $_POST['confipassword'] ?? '';
$rol          = $_POST['rolLogin'] ?? '';

// Validaciones básicas
if (empty($email) || empty($placa) || empty($password) || empty($confipass) || empty($rol)) {
    die("⚠️ Debes completar todos los campos.");
}

if ($password !== $confipass) {
    die("❌ Las contraseñas no coinciden.");
}

// Buscar usuario
$sql = "SELECT * FROM usuarios WHERE correo = ? AND placa = ? AND rol = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sss", $email, $placa, $rol);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();

    // Verificar contraseña
    if (password_verify($password, $user['contrasena'])) {
        // Crear sesión
        $_SESSION['usuario_id'] = $user['id'];
        $_SESSION['nombre']     = $user['nombre'];
        $_SESSION['rol']        = $user['rol'];

        // Redirigir según el rol
        if ($rol === "administrador") {
            header("Location: ../Front-end/panel_admin.php");
        } elseif ($rol === "policia") {
            header("Location: ../Front-end/panel_policia.php");
        } else {
            echo "Rol no válido.";
        }
        exit;
    } else {
        echo "❌ Contraseña incorrecta.";
    }
} else {
    echo "❌ No existe un usuario con esos datos.";
}

$conn->close();
?>

