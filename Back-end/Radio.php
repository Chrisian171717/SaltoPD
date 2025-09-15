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
$logs = [
    "Zeta-01: En patrulla",
    "Auto-12: Detenido ciudadano",
    "Helicóptero-03: Sobrevuelo zona roja"
];

echo json_encode($logs);


// Obtener Unidades
$stmtUnidades = $pdo->query("SELECT * FROM unidades");
$unidades = $stmtUnidades->fetchAll(PDO::FETCH_ASSOC);

// Obtener Comunicaciones
$stmtComs = $pdo->query("SELECT * FROM comunicaciones ORDER BY fecha DESC LIMIT 10");
$comunicaciones = $stmtComs->fetchAll(PDO::FETCH_ASSOC);

// Obtener Emergencias
$stmtEmerg = $pdo->query("SELECT * FROM emergencias WHERE activa=1");
$emergencias = $stmtEmerg->fetchAll(PDO::FETCH_ASSOC);


?>


<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Radio Policial</title>
  <link rel="stylesheet" href="radio.css">
</head>
<body>

<!-- Pegás aquí tu HTML de radio-dashboard adaptado -->
<div class="radio-dashboard">

<header class="radio-header">
    <h1>🚔 RADIO POLICIAL</h1>
    <p>Sistema de Comunicaciones – Distrito Central</p>
</header>

<!-- Sección Unidades -->
<section id="unidades" class="tab-content active">
  <h2>🚔 Estado de Unidades</h2>
  <div class="units-grid">
    <?php foreach($unidades as $u): ?>
      <article class="unit-card <?= $u['estado'] ?>">
        <h3><?= $u['tipo']." ".$u['codigo'] ?></h3>
        <p><strong>Estado:</strong> <?= ucfirst($u['estado']) ?></p>
        <p><strong>Oficial:</strong> <?= $u['oficial_nombre']." (".$u['oficial_rango'].")" ?></p>
        <p><strong>Sector:</strong> <?= $u['sector'] ?></p>
      </article>
    <?php endforeach; ?>
  </div>
</section>

<!-- Sección Radio -->
<section id="radio" class="tab-content">
  <h2>📻 Control de Radio</h2>
  <form method="POST" action="radio_enviar.php">
      <input type="text" name="emisor" placeholder="Tu nombre" required>
      <input type="text" name="mensaje" placeholder="Escribe tu mensaje..." required>
      <button type="submit">🎤 Transmitir</button>
  </form>

  <div class="log-container">
    <h3>📋 Comunicaciones Recientes</h3>
    <div id="radioLog">
      <?php foreach($comunicaciones as $c): ?>
        <p><strong><?= $c['emisor'] ?>:</strong> <?= $c['mensaje'] ?> <em>(<?= $c['fecha'] ?>)</em></p>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Sección Emergencias -->
<section id="emergencias" class="tab-content">
  <h2>🚨 Emergencias Activas</h2>
  <?php foreach($emergencias as $e): ?>
    <div class="emergency-alert">
      <h3>⚠️ Código: <?= $e['codigo'] ?></h3>
      <p><strong>Descripción:</strong> <?= $e['descripcion'] ?></p>
      <p><strong>Ubicación:</strong> <?= $e['ubicacion'] ?></p>
      <p><strong>Unidades:</strong> <?= $e['unidades_asignadas'] ?></p>
      <p><em><?= $e['fecha'] ?></em></p>
    </div>
  <?php endforeach; ?>
</section>

<!-- Sección Registro -->
<section id="registro" class="tab-content">
  <h2>📝 Registro de Actividades</h2>
  <!-- Podés listar todas las comunicaciones y emergencias -->
</section>

</div>
</body>
</html>

