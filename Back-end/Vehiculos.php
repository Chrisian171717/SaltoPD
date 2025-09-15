<?php 

include("conexion.php");

$host="localhost";
$user= "root";
$pass= "";
$db= "saltopd";

$conn=mysqli_connect($host,$user,$pass,$db);

header('Content-Type: application/json');
$mysqli = new mysqli("localhost","root","","saltopd");

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// Variables
$busqueda = $_POST['busqueda'] ?? '';

$resultados = [];

if (!empty($busqueda)) {
    // Buscar por matrícula o nombre del civil
    $sql = "SELECT v.matricula, v.marca, v.modelo, c.nombre, c.dni 
            FROM vehiculos v
            INNER JOIN civiles c ON v.civil_id = c.id
            WHERE v.matricula LIKE ? OR c.nombre LIKE ?";
    $stmt = $conn->prepare($sql);
    $like = "%" . $busqueda . "%";
    $stmt->bind_param("ss", $like, $like);
    $stmt->execute();
    $resultados = $stmt->get_result();
}


if ($mysqli->connect_errno) { echo json_encode([]); exit(); }

$action = $_REQUEST['action'] ?? '';
if($action==='list'){
    $res = $mysqli->query("SELECT * FROM vehiculos");
    $vehiculos = [];
    while($row=$res->fetch_assoc()) $vehiculos[]=$row;
    echo json_encode($vehiculos);
}
$mysqli->close();

?>
