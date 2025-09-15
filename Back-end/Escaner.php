<?php 

include("conexion.php");

$host="localhost";
$user= "root";
$pass= "";
$db= "saltopd";

$conn=mysqli_connect($host,$user,$pass,$db);
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_FILES["imagen"])) {
    // Guardar la imagen subida en /uploads
    $target_dir = __DIR__ . "/uploads/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $target_file = $target_dir . basename($_FILES["imagen"]["name"]);
    move_uploaded_file($_FILES["imagen"]["tmp_name"], $target_file);

    // Ejecutar el script Python con la ruta de la imagen
    $command = escapeshellcmd("python3 Back-end/scanner.py " . escapeshellarg($target_file));
    $output = shell_exec($command);
    $resultado = json_decode($output, true);

    header('Content-Type: application/json');

// Ejecutar script Python de reconocimiento facial
$command = escapeshellcmd("python3 ../Python/mian.py");
$output = shell_exec($command);

// Suponiendo que el script Python devuelva JSON
echo $output;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Sistema de Escáner Facial</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <!-- Menú de navegación -->
  <nav class="nav-tabs" aria-label="Navegación principal">
    <ul class="nav-tabs__list">
      <li><a href="civiles.html"><img src="Civil.png" alt="Civil"></a></li>
      <li><a href="Denuncias.html"><img src="Denuncias.png" alt="Denuncias"></a></li>
      <li><a href="Vehiculo2.0.html"><img src="Vehiculo.png" alt="Vehículo"></a></li>
      <li><a href="Radio.html"><img src="radio-policia.png" alt="Radio"></a></li>
      <li><a href="principal.html"><img src="Logo.png" alt="Principal"></a></li>
      <li><a href="Mapa.html"><img src="mapa.png" alt="Mapa"></a></li>
    </ul>
  </nav>

  <!-- Sistema de escaneo -->
  <section class="scanner-system" aria-label="Sistema de escaneo biométrico">
    <header>
      <h1>🧠 Sistema de Escáner Facial</h1>
      <p>Tecnología avanzada de reconocimiento biométrico</p>
    </header>

    <main>
      <form method="POST" enctype="multipart/form-data">
        <h2>Subir foto o documento</h2>
        <input type="file" name="imagen" accept="image/*" required>
        <button type="submit">🔍 Escanear</button>
      </form>

      <?php if (isset($resultado)): ?>
        <section class="resultados">
          <h3>📊 Resultados del Escaneo</h3>
          <ul>
            <li><strong>Estado del Escáner:</strong> <?= htmlspecialchars($resultado["estado"]) ?></li>
            <li><strong>Precisión:</strong> <?= htmlspecialchars($resultado["precision"]) ?>%</li>
            <li><strong>Documento:</strong> <?= htmlspecialchars($resultado["documento"]) ?></li>
          </ul>
        </section>
      <?php endif; ?>
    </main>
  </section>
</body>
</html>