<?php
// principal.php

// Definir todas las secciones y subsecciones
$secciones = [
    'inicio' => [
        'titulo' => 'Información de la Aplicación',
        'contenido' => 'Esta plataforma está diseñada para fortalecer la seguridad ciudadana en Salto. 
                        Desde el registro de civiles y vehículos hasta la gestión de denuncias y 
                        visualización geográfica, cada módulo está pensado para facilitar el trabajo 
                        policial y la participación ciudadana.',
        'icono' => ''
    ],
    'civiles' => [
        'titulo' => 'Registro de Civiles',
        'contenido' => 'Aquí podrás gestionar la información de los ciudadanos.',
        'icono' => 'Civil.png',
        'subsecciones' => [
            'nuevo' => ['titulo' => 'Nuevo Civil', 'contenido' => 'Formulario para registrar un nuevo ciudadano.'],
            'listar' => ['titulo' => 'Listar Civiles', 'contenido' => 'Listado completo de ciudadanos registrados.']
        ]
    ],
    'denuncias' => [
        'titulo' => 'Gestión de Denuncias',
        'contenido' => 'Sección principal de denuncias.',
        'icono' => 'Denuncias.png',
        'subsecciones' => [
            'registrar' => ['titulo' => 'Registrar Denuncia', 'contenido' => 'Formulario para registrar denuncias.'],
            'listar' => ['titulo' => 'Listar Denuncias', 'contenido' => 'Listado completo de denuncias.']
        ]
    ],
    'vehiculo' => [
        'titulo' => 'Control de Vehículos',
        'contenido' => 'Gestión general de vehículos.',
        'icono' => 'Vehiculo.png'
    ],
    'mapa' => [
        'titulo' => 'Mapa Interactivo',
        'contenido' => 'Visualiza zonas de riesgo y reportes geográficos.',
        'icono' => 'Mapa.png'
    ],
    'radio' => [
        'titulo' => 'Radio Policial',
        'contenido' => 'Comunicación y coordinación en tiempo real.',
        'icono' => 'radio-policia-2507338-2102444.png'
    ],
    'escaner' => [
        'titulo' => 'Escáner Facial',
        'contenido' => 'Reconocimiento facial para identificación rápida.',
        'icono' => 'Escaner Facial.png'
    ]
];

// Sección y subsección seleccionadas
$seccion = $_POST['seccion'] ?? 'inicio';
$subseccion = $_POST['subseccion'] ?? null;

// Validar sección
if (!array_key_exists($seccion, $secciones)) {
    $seccion = 'inicio';
    $subseccion = null;
}

// Validar subsección
if ($subseccion && (!isset($secciones[$seccion]['subsecciones'][$subseccion]))) {
    $subseccion = null;
}

// Año actual
$anio = date("Y");

// Función para mostrar contenido
function mostrarContenido($seccion, $subseccion = null, $secciones) {
    if ($subseccion && isset($secciones[$seccion]['subsecciones'][$subseccion])) {
        $datos = $secciones[$seccion]['subsecciones'][$subseccion];
        echo "<h2>{$datos['titulo']}</h2>";
        echo "<p>{$datos['contenido']}</p>";
    } else {
        $datos = $secciones[$seccion];
        echo "<h2>{$datos['titulo']}</h2>";
        echo "<p>{$datos['contenido']}</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SALTODP - <?php echo htmlspecialchars($secciones[$seccion]['titulo']); ?></title>
<link rel="stylesheet" href="../Front-end/estilos.css">
<style>
/* Resaltar sección y subsección activa */
.nav-tabs__link.active { border: 2px solid #007bff; border-radius: 8px; background-color: #e0f0ff; }
.submenu button.active { background-color: #cce5ff; }
.nav-tabs__link img { display: block; margin: 0 auto; }
.submenu { margin-left: 20px; margin-top: 5px; }
.submenu button { display: block; margin: 2px 0; }
</style>
</head>
<body>

<!-- HEADER -->
<header class="header">
    <div class="header__branding">
        <img src="../Front-end/Logo.png" alt="Logo SALTODP" class="header__logo" />
        <h1 class="header__title">Página Principal</h1>
    </div>
</header>

<!-- NAV -->
<form action="principal.php" method="post">
<nav class="nav-tabs" aria-label="Navegación principal">
<ul class="nav-tabs__list">
<?php
foreach ($secciones as $key => $datos) {
    if ($key === 'inicio') continue;
    $claseActiva = ($key === $seccion) ? 'active' : '';
    echo '<li>';
    echo '<button type="submit" name="seccion" value="' . htmlspecialchars($key) . '" class="nav-tabs__link ' . $claseActiva . '">
            <img src="../Front-end/' . htmlspecialchars($datos['icono']) . '" alt="Sección ' . htmlspecialchars($datos['titulo']) . '" class="nav-tabs__icon" />
          </button>';

    // Si tiene subsecciones y es la sección activa, mostrar submenú
    if (isset($datos['subsecciones']) && $key === $seccion) {
        echo '<div class="submenu">';
        foreach ($datos['subsecciones'] as $subKey => $subDatos) {
            $claseSubActiva = ($subKey === $subseccion) ? 'active' : '';
            echo '<button type="submit" name="subseccion" value="' . htmlspecialchars($subKey) . '" class="' . $claseSubActiva . '">
                    ' . htmlspecialchars($subDatos['titulo']) . '
                  </button>';
        }
        echo '</div>';
    }

    echo '</li>';
}
?>
</ul>
</nav>
</form>

<!-- MAIN -->
<main class="INFO">
<section class="info-section">
<?php mostrarContenido($seccion, $subseccion, $secciones); ?>
</section>
</main>

<!-- FOOTER -->
<footer class="footer">
<p class="footer__text">&copy; <?php echo $anio; ?> SALTODP. Todos los derechos reservados.</p>
</footer>

</body>
</html>

