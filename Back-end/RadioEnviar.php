<?php 
include("conexion.php");

// Verificar conexión
if (!$conn || $conn->connect_error) {
    die("Error de conexión a la base de datos.");
}

$success = false;
$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emisor = trim($_POST['emisor']);
    $mensaje = trim($_POST['mensaje']);

    if (!empty($emisor) && !empty($mensaje)) {
        $stmt = $conn->prepare("INSERT INTO comunicaciones (emisor, mensaje) VALUES (?, ?)");
        $stmt->bind_param("ss", $emisor, $mensaje);
        
        if ($stmt->execute()) {
            $success = true;
            $message = "Mensaje enviado correctamente";
        } else {
            $message = "Error al enviar mensaje: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $message = "Emisor y mensaje son requeridos";
    }
} else {
    $message = "Método no permitido";
}

// Redirigir de vuelta a la página principal con mensaje
$redirect_url = "Radio.php";
if ($message) {
    $redirect_url .= "?message=" . urlencode($message) . "&success=" . ($success ? "1" : "0");
}

header("Location: $redirect_url");
exit;
?>
