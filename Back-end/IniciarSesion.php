<?php 
include("../ConexionSPD.php");

$host="localhost";
$user= "root";
$pass= "";
$db= "saltodp";

$conn=mysqli_connect($host,$user,$pass,$db);
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Verifica si se envió el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitiza los datos
    $email = filter_var(trim($_POST["email"]), FILTER_SANITIZE_EMAIL);
    $placa = htmlspecialchars(trim($_POST["placa"]));
    $password = $_POST["password"];
    $confirmPassword = $_POST["password"]; // Este campo debería tener un name distinto en el HTML
    $rol = htmlspecialchars(trim($_POST["rolLogin"]));

    // Validaciones
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "Correo inválido.";
        exit;
    }

    if (strlen($password) < 8) {
        echo "La contraseña debe tener al menos 8 caracteres.";
        exit;
    }

    if ($password !== $confirmPassword) {
        echo "Las contraseñas no coinciden.";
        exit;
    }

    if ($rol !== "policia" && $rol !== "administrador") {
        echo "Rol inválido.";
        exit;
    }

    // Encriptar contraseña
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    // Insertar en la base de datos
    $sql = "INSERT INTO usuarios (email, placa, contrasena, rol) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $email, $placa, $passwordHash, $rol);

    if ($stmt->execute()) {
        echo "Registro exitoso";
    } else {
        echo "Error al registrar: " . $stmt->error;
    }

    $stmt->close();
}

$conn->close();
?>
