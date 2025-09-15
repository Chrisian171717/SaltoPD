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

header('Content-Type: application/json');
// Ejemplo de marcadores fijos de Salto
$markers = [
    ["title"=>"Comisaría 1","position"=>["lat"=>-31.3833,"lng"=>-57.9667]],
    ["title"=>"Hospital Central","position"=>["lat"=>-31.3840,"lng"=>-57.9680]],
    ["title"=>"Plaza Artigas","position"=>["lat"=>-31.3820,"lng"=>-57.9650]],
];
echo json_encode($markers);

// Obtener ubicaciones de la BD
$stmt = $pdo->query("SELECT * FROM ubicaciones");
$ubicaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mapa Interactivo</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>
    <style>
        #map { height: 500px; width: 100%; border-radius: 10px; }
        .mapa-title { font-size: 2em; margin: 0.5em 0; }
        .mapa-description { color: #555; margin-bottom: 1em; }
    </style>
</head>
<body>

<!-- Navegación -->
<nav class="nav-tabs" aria-label="Navegación principal">
    <ul class="nav-tabs__list">
        <li><a href="civiles.html"><img src="Civil.png" alt="Civil" /></a></li>
        <li><a href="Denuncias.html"><img src="Denuncias.png" alt="Denuncias" /></a></li>
        <li><a href="Vehiculo2.0.html"><img src="Vehiculo.png" alt="Vehículos" /></a></li>
        <li><a href="Radio.html"><img src="radio-policia-2507338-2102444.png" alt="Radio" /></a></li>
        <li><a href="principal.html"><img src="Logo.png" alt="Principal" /></a></li>
        <li><a href="Escaner Facial.html"><img src="Escaner Facial.png" alt="Escáner" /></a></li>
    </ul>
</nav>

<!-- Sección del mapa -->
<section class="mapa-section" aria-label="Mapa interactivo de Salto">
    <header class="mapa-header">
        <h1 class="mapa-title">🗺️ Mapa de Salto</h1>
        <p class="mapa-description">Visualización geográfica del distrito central</p>
    </header>

    <div id="map"></div>
</section>

<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script>
// Inicializar mapa centrado en Salto (ejemplo)
var map = L.map('map').setView([-31.3833, -57.9667], 13);

// Capa de mapa
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors'
}).addTo(map);

// Ubicaciones desde PHP
var ubicaciones = <?php echo json_encode($ubicaciones); ?>;

// Pintar marcadores
ubicaciones.forEach(function(u) {
    L.marker([u.lat, u.lng])
      .addTo(map)
      .bindPopup("<b>" + u.nombre + "</b><br>" + u.descripcion + "<br>Tipo: " + u.tipo);
});
</script>
</body>
</html>
?>