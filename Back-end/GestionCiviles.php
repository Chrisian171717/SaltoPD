<?php
// Back-end/GestionCiviles.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include("conexion.php");

session_start();
if (!isset($_SESSION['usuario'])) {
    $_SESSION['usuario'] = 'Operador';
}

$mensaje = '';

// Agregar nuevo civil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['agregar'])) {
    $nombre = $conn->real_escape_string($_POST['nombre']);
    $dni = $conn->real_escape_string($_POST['dni']);
    
    // Foto por defecto si no se sube ninguna
    $foto = '1769039.png'; // Puedes cambiar esto por una imagen por defecto
    
    $sql = "INSERT INTO civiles (nombre, dni, foto, dato_extra) 
            VALUES ('$nombre', '$dni', '$foto', '')";
    
    if ($conn->query($sql)) {
        $civil_id = $conn->insert_id;
        if (function_exists('registrarActividad')) {
            registrarActividad($conn, $_SESSION['usuario'], "Agregó civil: $nombre (DNI: $dni)", "civiles", $civil_id);
        }
        $mensaje = "✅ Civil agregado exitosamente";
    } else {
        $mensaje = "❌ Error al agregar civil: " . $conn->error;
    }
}

// Buscar civiles
$search = '';
$where = '';
if (isset($_POST['search']) && !empty($_POST['search'])) {
    $search = $conn->real_escape_string($_POST['search']);
    $where = "WHERE nombre LIKE '%$search%' OR dni LIKE '%$search%'";
}

// Obtener todos los civiles
$sql = "SELECT * FROM civiles $where ORDER BY nombre ASC";
$civiles = $conn->query($sql);

// Verificar si la consulta fue exitosa
if (!$civiles) {
    die("Error en la consulta: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Civiles</title>
  <link rel="stylesheet" href="estilos.css">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    
    body {
      font-family: Arial, sans-serif;
      background: #f5f5f5;
    }
    
    .header-civiles {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      padding: 20px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    .header-civiles__branding {
      display: flex;
      align-items: center;
      gap: 15px;
      margin-bottom: 20px;
    }
    
    .header-civiles__icon {
      width: 50px;
      height: 50px;
    }
    
    .header-civiles__title {
      font-size: 2em;
      margin: 0;
    }
    
    .header-civiles__search {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
    }
    
    .header-civiles__search input {
      padding: 10px;
      border: none;
      border-radius: 5px;
      font-size: 16px;
      flex: 1;
      min-width: 150px;
    }
    
    .header-civiles__search button {
      padding: 10px 20px;
      border: none;
      border-radius: 5px;
      background: #27ae60;
      color: white;
      font-size: 18px;
      cursor: pointer;
      transition: background 0.3s;
    }
    
    .header-civiles__search button:hover {
      background: #219a52;
    }
    
    .nav-tabs {
      background: #2c3e50;
      padding: 10px 0;
    }
    
    .nav-tabs__list {
      list-style: none;
      display: flex;
      justify-content: center;
      gap: 20px;
      flex-wrap: wrap;
    }
    
    .nav-tabs__list li a img {
      width: 40px;
      height: 40px;
      transition: transform 0.3s;
    }
    
    .nav-tabs__list li a:hover img {
      transform: scale(1.1);
    }
    
    .civiles-main {
      padding: 20px;
      max-width: 1400px;
      margin: 0 auto;
    }
    
    .alert {
      padding: 15px;
      margin: 20px 0;
      border-radius: 6px;
      font-weight: bold;
      text-align: center;
    }
    
    .alert-success {
      background: #d4edda;
      color: #155724;
      border: 1px solid #c3e6cb;
    }
    
    .alert-danger {
      background: #f8d7da;
      color: #721c24;
      border: 1px solid #f5c6cb;
    }
    
    .civiles-list {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
      gap: 20px;
    }
    
    .civil-card {
      background: white;
      border-radius: 10px;
      padding: 20px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
      transition: transform 0.3s, box-shadow 0.3s;
    }
    
    .civil-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 5px 20px rgba(0,0,0,0.2);
    }
    
    .civil-card__photo {
      width: 100%;
      height: 200px;
      object-fit: cover;
      border-radius: 8px;
      margin-bottom: 15px;
    }
    
    .civil-card__info {
      display: flex;
      flex-direction: column;
      gap: 10px;
    }
    
    .civil-card__name {
      font-size: 1.3em;
      font-weight: bold;
      color: #2c3e50;
    }
    
    .civil-card__dni {
      color: #7f8c8d;
      font-size: 1em;
    }
    
    .civil-card__extra {
      padding: 8px;
      border: 1px solid #ddd;
      border-radius: 5px;
      font-size: 14px;
    }
    
    .no-results {
      text-align: center;
      padding: 60px 20px;
      background: white;
      border-radius: 10px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    .no-results h2 {
      color: #7f8c8d;
      margin-bottom: 10px;
    }
    
    @media (max-width: 768px) {
      .civiles-list {
        grid-template-columns: 1fr;
      }
      
      .header-civiles__search {
        flex-direction: column;
      }
      
      .header-civiles__search input,
      .header-civiles__search button {
        width: 100%;
      }
    }
  </style>
</head>
<body>

<?php if ($mensaje): ?>
  <div class="alert <?= strpos($mensaje, '✅') !== false ? 'alert-success' : 'alert-danger' ?>">
    <?= $mensaje ?>
  </div>
<?php endif; ?>

<form action="GestionCiviles.php" method="POST">
  <header class="header-civiles">
    <div class="header-civiles__branding">
      <img src="1769039.png" alt="Icono de sección civiles" class="header-civiles__icon" />
      <h1 class="header-civiles__title">Civiles</h1>
    </div>

    <div class="header-civiles__search">
      <!-- Buscar -->
      <input type="search" name="search" placeholder="Buscar persona..." class="search-input" aria-label="Buscar persona por nombre o DNI" value="<?= htmlspecialchars($search) ?>" />
      
      <!-- Agregar -->
      <input type="text" name="nombre" placeholder="Nombre" />
      <input type="text" name="dni" placeholder="DNI" />
      <button type="submit" name="agregar">➕ Agregar</button>
    </div>
  </header>
</form>

<nav class="nav-tabs">
  <ul class="nav-tabs__list">
    <li><a href="principal.html"><img src="Logo.png" alt="Principal"></a></li>
    <li><a href="Denuncias.html"><img src="Denuncias.png" alt="Denuncias"></a></li>
    <li><a href="Vehiculo2.0.html"><img src="Vehiculo.png" alt="Vehículo"></a></li>
    <li><a href="Mapa.html"><img src="mapa.png" alt="Mapa"></a></li>
    <li><a href="Radio.php"><img src="radio-policia-2507338-2102444.png" alt="Radio"></a></li>
    <li><a href="Escaner Facial.html"><img src="Escaner Facial.png" alt="Escáner"></a></li>
  </ul>
</nav>

<main class="civiles-main">
  <section class="civiles-list">
    <?php if ($civiles && $civiles->num_rows > 0): ?>
      <?php while($fila = $civiles->fetch_assoc()): ?>
        <article class="civil-card">
          <img src="<?php echo htmlspecialchars($fila['foto']); ?>" alt="Foto de <?php echo htmlspecialchars($fila['nombre']); ?>" class="civil-card__photo" onerror="this.src='1769039.png'" />
          <div class="civil-card__info">
            <span class="civil-card__name"><?php echo htmlspecialchars($fila['nombre']); ?></span>
            <span class="civil-card__dni">DNI: <?php echo htmlspecialchars($fila['dni']); ?></span>
            <input type="text" placeholder="Dato adicional" class="civil-card__extra" value="<?php echo htmlspecialchars($fila['dato_extra']); ?>" readonly />
          </div>
        </article>
      <?php endwhile; ?>
    <?php else: ?>
      <div class="no-results">
        <h2>No se encontraron civiles</h2>
        <p>Agrega un nuevo civil usando el formulario superior</p>
      </div>
    <?php endif; ?>
  </section>
</main>

</body>
</html>
