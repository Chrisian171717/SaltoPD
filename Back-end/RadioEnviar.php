<?php 

include("conexion.php");

$host="localhost";
$user= "root";
$pass= "";
$db= "saltopd";

$conn=mysqli_connect($host,$user,$pass,$db);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emisor = $_POST['emisor'];
    $mensaje = $_POST['mensaje'];

    $stmt = $pdo->prepare("INSERT INTO comunicaciones (emisor, mensaje) VALUES (?, ?)");
    $stmt->execute([$emisor, $mensaje]);
}

header("Location: Radio.php");
?>