<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Plataforma de Denuncias</title>
  <link rel="stylesheet" href="estilos.css">
</head>
<body>

<!-- ===== Menú principal ===== -->
<nav class="nav-tabs" aria-label="Navegación principal">
  <ul class="nav-tabs__list">
    <li><a href="civiles.html" class="nav-tabs__link"><img src="Civil.png" alt="Sección Civil" class="nav-tabs__icon" /></a></li>
    <li><a href="Radio.html" class="nav-tabs__link"><img src="radio-policia-2507338-2102444.png" alt="Sección Radio" class="nav-tabs__icon" /></a></li>
    <li><a href="Vehiculo2.0.html" class="nav-tabs__link"><img src="Vehiculo.png" alt="Sección Vehículo" class="nav-tabs__icon" /></a></li>
    <li><a href="Mapa.html" class="nav-tabs__link"><img src="mapa.png" alt="Sección Mapa" class="nav-tabs__icon" /></a></li>
    <li><a href="principal.html" class="nav-tabs__link"><img src="Logo.png" alt="Sección principal" class="nav-tabs__icon" /></a></li>
    <li><a href="Escaner Facial.html" class="nav-tabs__link"><img src="Escaner Facial.png" alt="Sección Escaner" class="nav-tabs__icon" /></a></li>
  </ul>
</nav>

<main class="denuncias-main">

  <!-- ===== Banner ===== -->
  <section class="banner" aria-label="Mensaje ciudadano">
    <div class="banner__content">
      <h2 class="banner__title">La voz ciudadana cuenta</h2>
      <p class="banner__text">Denuncia, participa y ayuda a crear una comunidad más segura.</p>
    </div>
  </section>

  <!-- ===== Formulario Agregar Denuncia ===== -->
  <section class="denuncia-form" aria-label="Agregar nueva denuncia">
    <div class="denuncia-form__content">
      <h3 class="denuncia-form__title">¿Querés reportar algo?</h3>
      <form method="POST" action="Denuncias.php">
        <input type="text" name="titulo" placeholder="Título de la denuncia" required>
        <textarea name="descripcion" placeholder="Describa lo ocurrido" required></textarea>
        <button type="submit" name="agregar" class="btn btn-primary">Agregar Denuncia</button>
      </form>
    </div>
  </section>

  <!-- ===== Info de la plataforma ===== -->
  <section class="platform-info" aria-label="Información sobre la plataforma">
    <div class="platform-info__content">
      <h3 class="platform-info__title">¿Qué es esta plataforma?</h3>
      <p class="platform-info__text">
        Un sistema transparente para visualizar y reportar denuncias públicas. Cada voz cuenta, cada reporte construye seguridad.
      </p>
    </div>
  </section>

  <!-- ===== Denuncias recientes ===== -->
  <section class="recent-denuncias" aria-label="Denuncias recientes">
    <h3 class="recent-denuncias__title">Últimas Denuncias</h3>
    <div class="denuncia-cards">
      <?php while($fila = $denuncias->fetch_assoc()): ?>
        <article class="denuncia-card">
          <strong>#<?php echo $fila['id']; ?></strong> — <?php echo htmlspecialchars($fila['titulo']); ?>
          <p><?php echo htmlspecialchars($fila['descripcion']); ?></p>
          <small><?php echo $fila['fecha']; ?></small>

          <!-- Botón eliminar -->
          <a href="Denuncias.php?eliminar=<?php echo $fila['id']; ?>" 
             onclick="return confirm('¿Eliminar esta denuncia?')" 
             class="btn btn-danger">🗑️ Eliminar</a>

          <!-- Form modificar -->
          <form method="POST" action="Denuncias.php" class="form-modificar">
            <input type="hidden" name="id" value="<?php echo $fila['id']; ?>">
            <input type="text" name="titulo" value="<?php echo $fila['titulo']; ?>" required>
            <textarea name="descripcion"><?php echo $fila['descripcion']; ?></textarea>
            <button type="submit" name="modificar" class="btn btn-warning">✏️ Modificar</button>
          </form>
        </article>
      <?php endwhile; ?>
    </div>
  </section>

</main>

<!-- ===== Footer ===== -->
<footer class="footer">
  <p class="footer__text">&copy; 2025 Plataforma de Denuncias. Todos los derechos reservados.</p>
</footer>

</body>
</html>