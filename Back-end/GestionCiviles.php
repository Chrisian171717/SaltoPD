<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Civiles</title>
  <link rel="stylesheet" href="estilos.css">
</head>
<body>

<form action="Civiles.php" method="POST">
  <header class="header-civiles">
    <div class="header-civiles__branding">
      <img src="1769039.png" alt="Icono de sección civiles" class="header-civiles__icon" />
      <h1 class="header-civiles__title">Civiles</h1>
    </div>

    <div class="header-civiles__search">
      <!-- Buscar -->
      <input type="search" name="search" placeholder="Buscar persona..." class="search-input" aria-label="Buscar persona por nombre o DNI" />
      
      <!-- Agregar -->
      <input type="text" name="nombre" placeholder="Nombre" required />
      <input type="text" name="dni" placeholder="DNI" required />
      <button type="submit" name="agregar">➕</button>
    </div>
  </header>
</form>

<nav class="nav-tabs">
  <ul class="nav-tabs__list">
    <li><a href="principal.html"><img src="Logo.png" alt="Principal"></a></li>
    <li><a href="Denuncias.html"><img src="Denuncias.png" alt="Denuncias"></a></li>
    <li><a href="Vehiculo2.0.html"><img src="Vehiculo.png" alt="Vehículo"></a></li>
    <li><a href="Mapa.html"><img src="mapa.png" alt="Mapa"></a></li>
    <li><a href="Radio.html"><img src="radio-policia-2507338-2102444.png" alt="Radio"></a></li>
    <li><a href="Escaner Facial.html"><img src="Escaner Facial.png" alt="Escáner"></a></li>
  </ul>
</nav>

<main class="civiles-main">
  <section class="civiles-list">
    <?php while($fila = $civiles->fetch_assoc()): ?>
      <article class="civil-card">
        <img src="<?php echo $fila['foto']; ?>" alt="Foto de <?php echo $fila['nombre']; ?>" class="civil-card__photo" />
        <div class="civil-card__info">
          <span class="civil-card__name"><?php echo htmlspecialchars($fila['nombre']); ?></span>
          <span class="civil-card__dni">DNI: <?php echo htmlspecialchars($fila['dni']); ?></span>
          <input type="text" placeholder="Dato adicional" class="civil-card__extra" value="<?php echo $fila['dato_extra']; ?>" />
        </div>
      </article>
    <?php endwhile; ?>
  </section>
</main>

</body>
</html>
