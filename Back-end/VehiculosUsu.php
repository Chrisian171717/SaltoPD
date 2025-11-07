<?php
// Configuración de CORS para permitir requests desde el frontend
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Incluir y verificar conexión
include("conexion.php");

// Verificar si la conexión se estableció correctamente
if (!$conn) {
    // Si es una petición AJAX, retornar JSON
    if (isset($_GET['action']) && $_GET['action'] === 'search') {
        header('Content-Type: application/json');
        echo json_encode(["error" => "Error de conexión a la base de datos"]);
        exit;
    }
    die("<div style='color:red;text-align:center;padding:20px;font-size:18px;'>
         <h3>❌ Error de Conexión</h3>
         <p>No se pudo conectar a la base de datos. Verifique el archivo conexion.php</p>
         </div>");
}

// Manejar método OPTIONS para CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// =============================================
// GESTIÓN DE DENUNCIAS - SOLO VISUALIZACIÓN
// =============================================

// Obtener denuncias de un vehículo (SOLO LECTURA)
if (isset($_GET['action']) && $_GET['action'] === 'get_denuncias' && isset($_GET['matricula'])) {
    $matricula = $conn->real_escape_string($_GET['matricula']);
    
    $sql = "SELECT * FROM denuncias_vehiculos WHERE vehiculo_matricula = '$matricula' ORDER BY fecha_denuncia DESC";
    $result = $conn->query($sql);
    
    if ($result) {
        $denuncias = $result->fetch_all(MYSQLI_ASSOC);
        header('Content-Type: application/json');
        echo json_encode($denuncias);
    } else {
        header('Content-Type: application/json');
        echo json_encode(["error" => "Error al obtener denuncias: " . $conn->error]);
    }
    exit;
}

// =============================================
// GESTIÓN DE VEHÍCULOS - SOLO BÚSQUEDA
// =============================================

// Manejar búsqueda AJAX para el front-end
if (isset($_GET['action']) && $_GET['action'] === 'search') {
    $res = $conn->query("SELECT * FROM automovil ORDER BY Matricula");
    if ($res) {
        $vehiculos = $res->fetch_all(MYSQLI_ASSOC);
        header('Content-Type: application/json');
        echo json_encode($vehiculos);
        exit;
    } else {
        header('Content-Type: application/json');
        echo json_encode(["error" => "Error en la consulta: " . $conn->error]);
        exit;
    }
}

// Inicializar variables
$error = "";
$vehiculos = [];

// CARGAR LISTA DE VEHÍCULOS - SOLO VISUALIZACIÓN
$res = $conn->query("SELECT * FROM automovil ORDER BY Matricula");
if ($res) {
    $vehiculos = $res->fetch_all(MYSQLI_ASSOC);
} else {
    $error = "❌ Error al cargar vehículos: " . $conn->error;
    $vehiculos = [];
}

// Obtener estadísticas
$total_vehiculos = is_array($vehiculos) ? count($vehiculos) : 0;
$marcas_unicas = 0;
$tipos_unicos = 0;

if (is_array($vehiculos) && count($vehiculos) > 0) {
    $marcas_unicas = count(array_unique(array_column($vehiculos, 'Marca')));
    $tipos_unicos = count(array_unique(array_column($vehiculos, 'Tipo_Vehiculo')));
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Consulta de Vehículos - Sistema de Búsqueda</title>
    <link rel="stylesheet" href="../Front-end/vehiculo.css">
</head>
<body>
    <!-- Navegación con imágenes pequeñas -->
    <nav class="nav-tabs" aria-label="Navegación principal">
        <ul class="nav-tabs__list">
            <li><a href="../Front-end/principal.html" class="nav-tabs__link">
                <img src="../Front-end/Logo.png" alt="Sección principal" class="nav-tabs__icon" />
                <span>Principal</span>
            </a></li>
            <li><a href="../Front-end/civiles.html" class="nav-tabs__link">
                <img src="../Front-end/Civil.png" alt="Sección Civil" class="nav-tabs__icon" />
                <span>Civil</span>
            </a></li>
            <li><a href="../Front-end/Denuncias.html" class="nav-tabs__link">
                <img src="../Front-end/Denuncias.png" alt="Sección Denuncias" class="nav-tabs__icon" />
                <span>Denuncias</span>
            </a></li>
            <li><a href="../Front-end/Radio.html" class="nav-tabs__link">
                <img src="../Front-end/radio-policia-2507338-2102444.png" alt="Sección Radio" class="nav-tabs__icon" />
                <span>Radio</span>
            </a></li>
            <li><a href="../Front-end/Mapa.html" class="nav-tabs__link">
                <img src="../Front-end/mapa.png" alt="Sección Mapa" class="nav-tabs__icon" />
                <span>Mapa</span>
            </a></li>
            <li><a href="../Front-end/Escaner Facial.html" class="nav-tabs__link">
                <img src="../Front-end/Escaner Facial.png" alt="Sección Escaner" class="nav-tabs__icon" />
                <span>Escáner</span>
            </a></li>
        </ul>
    </nav>

    <div class="container">
        <div class="header">
            <h1>
                <img src="../Front-end/Vehiculo.png" alt="Icono de vehículo" class="header-icon" />
                Sistema de Consulta de Vehículos
            </h1>
            <p>Panel de Búsqueda - Solo Lectura</p>
        </div>
        
        <div class="content">
            <?php if($error): ?>
                <div class="alert alert-error">
                    <?= $error ?>
                </div>
            <?php endif; ?>

            <!-- Estadísticas -->
            <div class="stats">
                <div class="stat-card">
                    <div class="stat-number"><?= $total_vehiculos ?></div>
                    <div class="stat-label">Total de Vehículos</div>
                </div>
                <div class="stat-card marcas">
                    <div class="stat-number"><?= $marcas_unicas ?></div>
                    <div class="stat-label">Marcas Diferentes</div>
                </div>
                <div class="stat-card tipos">
                    <div class="stat-number"><?= $tipos_unicos ?></div>
                    <div class="stat-label">Tipos de Vehículos</div>
                </div>
            </div>

            <div class="actions-header">
                <h2 class="page-title">
                    <img src="../Front-end/Vehiculo.png" alt="Vehículos" style="width: 35px; height: 35px; object-fit: contain;" />
                    Lista de Automóviles Registrados
                </h2>
            </div>
            
            <?php if(!empty($vehiculos)): ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Matrícula</th>
                                <th>Marca</th>
                                <th>Modelo</th>
                                <th>Tipo de Vehículo</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($vehiculos as $v): 
                                $badge_class = 'badge-' . strtolower($v['Tipo_Vehiculo'] ?? 'otro');
                            ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($v['Matricula']) ?></strong></td>
                                    <td><?= htmlspecialchars($v['Marca']) ?></td>
                                    <td><?= htmlspecialchars($v['Modelo']) ?></td>
                                    <td>
                                        <span class="badge <?= $badge_class ?>">
                                            <?= htmlspecialchars($v['Tipo_Vehiculo']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-info" onclick="mostrarDenuncias('<?= htmlspecialchars($v['Matricula']) ?>')" title="Ver denuncias">
                                            📋 Ver Denuncias
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <img src="../Front-end/Vehiculo.png" alt="Sin vehículos" />
                    <h3>No hay automóviles registrados</h3>
                    <p>No se encontraron vehículos en el sistema.</p>
                </div>
            <?php endif; ?>

            <!-- Sección de Denuncias (se muestra dinámicamente - SOLO LECTURA) -->
            <div class="denuncias-section" id="denuncias-section" style="display: none;">
                <div class="denuncias-header">
                    <h2 class="denuncias-title">
                        📋 Denuncias del Vehículo
                        <span id="denuncias-vehiculo-info" style="font-size: 0.7em; color: #6c757d;"></span>
                    </h2>
                </div>
                <div class="denuncias-lista" id="lista-denuncias">
                    <div class="sin-denuncias">Cargando denuncias...</div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Variables globales
        let vehiculoActual = null;

        // Funciones para visualización de denuncias (SOLO LECTURA)
        function mostrarDenuncias(matricula) {
            vehiculoActual = matricula;
            const seccion = document.getElementById('denuncias-section');
            const info = document.getElementById('denuncias-vehiculo-info');
            
            seccion.style.display = 'block';
            info.textContent = `(Matrícula: ${matricula})`;
            
            // Scroll a la sección de denuncias
            seccion.scrollIntoView({ behavior: 'smooth' });
            
            // Cargar denuncias
            cargarDenunciasVehiculo(matricula);
        }

        function cargarDenunciasVehiculo(matricula) {
            const lista = document.getElementById('lista-denuncias');
            lista.innerHTML = '<div class="sin-denuncias">🔄 Cargando denuncias...</div>';
            
            fetch(`?action=get_denuncias&matricula=${matricula}`)
                .then(response => response.json())
                .then(denuncias => {
                    mostrarListaDenuncias(denuncias);
                })
                .catch(error => {
                    console.error('Error al cargar denuncias:', error);
                    lista.innerHTML = '<div class="sin-denuncias">❌ Error al cargar denuncias</div>';
                });
        }

        function mostrarListaDenuncias(denuncias) {
            const lista = document.getElementById('lista-denuncias');
            
            if (!denuncias || denuncias.length === 0) {
                lista.innerHTML = '<div class="sin-denuncias">No hay denuncias registradas para este vehículo.</div>';
                return;
            }
            
            if (denuncias.error) {
                lista.innerHTML = `<div class="sin-denuncias">❌ ${denuncias.error}</div>`;
                return;
            }
            
            lista.innerHTML = denuncias.map(denuncia => `
                <div class="denuncia-item">
                    <div class="denuncia-header">
                        <div class="denuncia-fecha">${formatearFecha(denuncia.fecha_denuncia)} - ${denuncia.tipo_denuncia}</div>
                    </div>
                    <div class="denuncia-descripcion">
                        <strong>Descripción:</strong> ${denuncia.descripcion}<br>
                        <strong>Estado:</strong> 
                        <span class="denuncia-estado estado-${denuncia.estado.toLowerCase().replace(' ', '-')}">
                            ${denuncia.estado}
                        </span>
                    </div>
                </div>
            `).join('');
        }

        function formatearFecha(fechaStr) {
            const opciones = { year: 'numeric', month: 'long', day: 'numeric' };
            return new Date(fechaStr).toLocaleDateString('es-ES', opciones);
        }
    </script>
</body>
</html>