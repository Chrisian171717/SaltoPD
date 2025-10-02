<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Plataforma de Denuncias</title>
  <link rel="stylesheet" href="../Front-end/estilos.css">
</head>
<body>

<?php
// Incluir las funciones de denuncias
include("denuncias.php");

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['agregar'])) {
        $titulo = $_POST['titulo'] ?? '';
        $descripcion = $_POST['descripcion'] ?? '';
        
        if (!empty($titulo) && !empty($descripcion)) {
            $resultado = agregarDenuncia($titulo, '', $descripcion);
            if ($resultado === "success") {
                echo "<script>alert('Denuncia agregada correctamente');</script>";
            } else {
                echo "<script>alert('Error: " . addslashes($resultado) . "');</script>";
            }
        }
    } elseif (isset($_POST['modificar'])) {
        $id = $_POST['id'] ?? 0;
        $titulo = $_POST['titulo'] ?? '';
        $descripcion = $_POST['descripcion'] ?? '';
        
        if ($id > 0 && !empty($titulo) && !empty($descripcion)) {
            $resultado = editarDenuncia($id, $titulo, '', $descripcion);
            if ($resultado === "success") {
                echo "<script>alert('Denuncia modificada correctamente');</script>";
            } else {
                echo "<script>alert('Error: " . addslashes($resultado) . "');</script>";
            }
        }
    }
}

// Procesar eliminación
if (isset($_GET['eliminar'])) {
    $id = $_GET['eliminar'];
    if ($id > 0) {
        $resultado = eliminarDenuncia($id);
        if ($resultado === "success") {
            echo "<script>alert('Denuncia eliminada correctamente');</script>";
        } else {
            echo "<script>alert('Error: " . addslashes($resultado) . "');</script>";
        }
    }
}

// Obtener denuncias
$denuncias = listarDenuncias();
?>

<!-- ===== Menú principal ===== -->
<nav class="nav-tabs" aria-label="Navegación principal">
  <ul class="nav-tabs__list">
    <li><a href="../Front-end/civiles.html" class="nav-tabs__link"><img src="../Front-end/Civil.png" alt="Sección Civil" class="nav-tabs__icon" /></a></li>
    <li><a href="../Front-end/Radio.html" class="nav-tabs__link"><img src="../Front-end/radio-policia-2507338-2102444.png" alt="Sección Radio" class="nav-tabs__icon" /></a></li>
    <li><a href="../Front-end/Vehiculo2.0.html" class="nav-tabs__link"><img src="../Front-end/Vehiculo.png" alt="Sección Vehículo" class="nav-tabs__icon" /></a></li>
    <li><a href="../Front-end/Mapa.html" class="nav-tabs__link"><img src="../Front-end/mapa.png" alt="Sección Mapa" class="nav-tabs__icon" /></a></li>
    <li><a href="../Front-end/principal.html" class="nav-tabs__link"><img src="../Front-end/Logo.png" alt="Sección principal" class="nav-tabs__icon" /></a></li>
    <li><a href="../Front-end/Escaner Facial.html" class="nav-tabs__link"><img src="../Front-end/Escaner Facial.png" alt="Sección Escaner" class="nav-tabs__icon" /></a></li>
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
      <form method="POST" action="GestionDenuncia.php">
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
      <?php if (!empty($denuncias)): ?>
        <?php foreach ($denuncias as $fila): ?>
          <article class="denuncia-card">
            <strong>#<?php echo $fila['id']; ?></strong> — <?php echo htmlspecialchars($fila['nombre_civil']); ?>
            <p><?php echo htmlspecialchars($fila['descripcion']); ?></p>
            <small><?php echo $fila['Fecha']; ?></small>

            <!-- Botón eliminar -->
            <a href="GestionDenuncia.php?eliminar=<?php echo $fila['id']; ?>" 
               onclick="return confirm('¿Eliminar esta denuncia?')" 
               class="btn btn-danger">🗑️ Eliminar</a>

            <!-- Form modificar -->
            <form method="POST" action="GestionDenuncia.php" class="form-modificar">
              <input type="hidden" name="id" value="<?php echo $fila['id']; ?>">
              <input type="text" name="titulo" value="<?php echo htmlspecialchars($fila['nombre_civil']); ?>" required>
              <textarea name="descripcion"><?php echo htmlspecialchars($fila['descripcion']); ?></textarea>
              <button type="submit" name="modificar" class="btn btn-warning">✏️ Modificar</button>
            </form>
          </article>
        <?php endforeach; ?>
      <?php else: ?>
        <p>No hay denuncias registradas.</p>
      <?php endif; ?>
    </div>
  </section>

</main>

<!-- ===== Footer ===== -->
<footer class="footer">
  <p class="footer__text">&copy; 2025 Plataforma de Denuncias. Todos los derechos reservados.</p>
</footer>

</body>
</html>