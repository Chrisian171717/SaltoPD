<?php 
include("conexion.php");

// Verificar conexión
if (!$conn || $conn->connect_error) {
    die("Error de conexión a la base de datos. Verifica la configuración.");
}

// Mostrar mensajes de éxito/error
$alert_message = "";
$alert_type = "";

if (isset($_GET['message'])) {
    $alert_message = urldecode($_GET['message']);
    $alert_type = isset($_GET['success']) && $_GET['success'] == '1' ? 'success' : 'error';
}

// Determinar pestaña activa desde URL
$tab_activa = 'unidades'; // default
if (isset($_GET['tab'])) {
    $tab_activa = $_GET['tab'];
}

// Manejar acciones AJAX y POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    $action = $_POST['action'];
    
    switch($action) {
        case 'delete_unidad':
            $id = intval($_POST['id']);
            $stmt = $conn->prepare("DELETE FROM unidades WHERE id = ?");
            $stmt->bind_param("i", $id);
            $result = $stmt->execute();
            echo json_encode(['success' => $result]);
            $stmt->close();
            exit;
            
        case 'delete_comunicacion':
            $id = intval($_POST['id']);
            $stmt = $conn->prepare("DELETE FROM comunicaciones WHERE id = ?");
            $stmt->bind_param("i", $id);
            $result = $stmt->execute();
            echo json_encode(['success' => $result]);
            $stmt->close();
            exit;

        case 'editar_comunicacion':
            $id = intval($_POST['id']);
            $emisor = trim($_POST['emisor']);
            $mensaje = trim($_POST['mensaje']);
            
            $stmt = $conn->prepare("UPDATE comunicaciones SET emisor=?, mensaje=? WHERE id=?");
            $stmt->bind_param("ssi", $emisor, $mensaje, $id);
            $result = $stmt->execute();
            echo json_encode(['success' => $result]);
            $stmt->close();
            exit;

        case 'editar_emergencia':
            $id = intval($_POST['id']);
            $codigo = trim($_POST['codigo']);
            $descripcion = trim($_POST['descripcion']);
            $ubicacion = trim($_POST['ubicacion']);
            $unidades_asignadas = trim($_POST['unidades_asignadas']);
            $activa = intval($_POST['activa']);
            
            $stmt = $conn->prepare("UPDATE emergencias SET codigo=?, descripcion=?, ubicacion=?, unidades_asignadas=?, activa=? WHERE id=?");
            $stmt->bind_param("ssssii", $codigo, $descripcion, $ubicacion, $unidades_asignadas, $activa, $id);
            $result = $stmt->execute();
            echo json_encode(['success' => $result]);
            $stmt->close();
            exit;
            
        case 'delete_emergencia':
            $id = intval($_POST['id']);
            $stmt = $conn->prepare("DELETE FROM emergencias WHERE id = ?");
            $stmt->bind_param("i", $id);
            $result = $stmt->execute();
            echo json_encode(['success' => $result]);
            $stmt->close();
            exit;

        case 'editar_ubicacion':
            $id = intval($_POST['id']);
            $nombre = trim($_POST['nombre']);
            $descripcion = trim($_POST['descripcion']);
            $lat = floatval($_POST['lat']);
            $lng = floatval($_POST['lng']);
            $tipo = trim($_POST['tipo']);
            
            $stmt = $conn->prepare("UPDATE ubicaciones SET nombre=?, descripcion=?, lat=?, lng=?, tipo=? WHERE id=?");
            $stmt->bind_param("ssddsi", $nombre, $descripcion, $lat, $lng, $tipo, $id);
            $result = $stmt->execute();
            echo json_encode(['success' => $result]);
            $stmt->close();
            exit;

        case 'delete_ubicacion':
            $id = intval($_POST['id']);
            $stmt = $conn->prepare("DELETE FROM ubicaciones WHERE id = ?");
            $stmt->bind_param("i", $id);
            $result = $stmt->execute();
            echo json_encode(['success' => $result]);
            $stmt->close();
            exit;
    }
}

// Si es una petición AJAX para obtener logs
if (isset($_GET['action']) && $_GET['action'] == 'log') {
    header('Content-Type: application/json');
    
    $result = $conn->query("SELECT id, emisor, mensaje, fecha FROM comunicaciones ORDER BY fecha DESC LIMIT 10");
    $logs = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $logs[] = [
                'id' => $row['id'],
                'emisor' => $row['emisor'],
                'mensaje' => $row['mensaje'],
                'fecha' => $row['fecha']
            ];
        }
    }
    echo json_encode($logs);
    exit;
}

// Obtener datos de la base de datos
$unidades = [];
$comunicaciones = [];
$emergencias = [];
$ubicaciones = [];

// Unidades
$resultUnidades = $conn->query("SELECT * FROM unidades ORDER BY id DESC");
if ($resultUnidades && $resultUnidades->num_rows > 0) {
    while ($row = $resultUnidades->fetch_assoc()) {
        $unidades[] = $row;
    }
}

// Comunicaciones
$resultComs = $conn->query("SELECT * FROM comunicaciones ORDER BY fecha DESC LIMIT 20");
if ($resultComs && $resultComs->num_rows > 0) {
    while ($row = $resultComs->fetch_assoc()) {
        $comunicaciones[] = $row;
    }
}

// Emergencias
$resultEmerg = $conn->query("SELECT * FROM emergencias ORDER BY fecha DESC");
if ($resultEmerg && $resultEmerg->num_rows > 0) {
    while ($row = $resultEmerg->fetch_assoc()) {
        $emergencias[] = $row;
    }
}

// Ubicaciones
$resultUbicaciones = $conn->query("SELECT * FROM ubicaciones ORDER BY id DESC");
if ($resultUbicaciones && $resultUbicaciones->num_rows > 0) {
    while ($row = $resultUbicaciones->fetch_assoc()) {
        $ubicaciones[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Radio Policial</title>
  <link rel="stylesheet" href="../Front-end/radio.css">
  <link rel="icon" href="../Front-end/logo.png" type="image/png">
</head>
<body>

<nav class="nav-tabs" aria-label="Navegación principal">
    <ul class="nav-tabs__list">
        <li><a href="../Front-end/civiles.html" class="nav-tabs__link"><img src="../Front-end/Civil.png" alt="Sección Civil" class="nav-tabs__icon" /></a></li>
        <li><a href="../Front-end/Denuncias.html" class="nav-tabs__link"><img src="../Front-end/Denuncias.png" alt="Sección Denuncias" class="nav-tabs__icon" /></a></li>
        <li><a href="../Front-end/Vehiculo2.0.html" class="nav-tabs__link"><img src="../Front-end/Vehiculo.png" alt="Sección Vehículo" class="nav-tabs__icon" /></a></li>
        <li><a href="../Front-end/Mapa.html" class="nav-tabs__link"><img src="../Front-end/mapa.png" alt="Sección Mapa" class="nav-tabs__icon" /></a></li>
        <li><a href="../Front-end/principal.html" class="nav-tabs__link"><img src="../Front-end/Logo.png" alt="Sección principal" class="nav-tabs__icon" /></a></li>
        <li><a href="../Front-end/Escaner Facial.html" class="nav-tabs__link"><img src="../Front-end/Escaner Facial.png" alt="Sección Escaner" class="nav-tabs__icon" /></a></li>
    </ul>
</nav>

<div class="radio-dashboard">

<header class="radio-header" aria-label="Encabezado del sistema de radio policial">
    <h1>🚔 RADIO POLICIAL</h1>
    <p>Sistema de Comunicaciones – Distrito Central</p>
</header>

<!-- Mostrar mensajes de éxito/error -->
<?php if (!empty($alert_message)): ?>
    <div class="alert alert-<?php echo $alert_type; ?>">
        <?php echo htmlspecialchars($alert_message); ?>
    </div>
<?php endif; ?>

<!-- Información de oficiales -->
<section class="officer-section" aria-label="Información de oficiales">
    <div class="officer-card">
    <h3>👮‍♂️ Oficial de Servicio</h3>
    <ul>
        <li><strong>Nombre:</strong> Sargento Rodriguez</li>
        <li><strong>Rango:</strong> Sargento Primero</li>
        <li><strong>ID:</strong> 10347</li>
    </ul>
    </div>
    <div class="officer-card">
    <h3>📡 Central de Comunicaciones</h3>
    <ul>
        <li><strong>Operador:</strong> Cabo Martinez</li>
        <li><strong>Turno:</strong> 14:00 - 22:00</li>
        <li><strong>Frecuencia:</strong> 460.125 MHz</li>
    </ul>
    </div>
</section>

<!-- Navegación por pestañas -->
<nav class="radio-tabs" aria-label="Secciones del sistema">
    <button class="tab-button <?php echo $tab_activa == 'unidades' ? 'active' : ''; ?>" data-tab="unidades">📋 Unidades</button>
    <button class="tab-button <?php echo $tab_activa == 'radio' ? 'active' : ''; ?>" data-tab="radio">📻 Radio</button>
    <button class="tab-button <?php echo $tab_activa == 'emergencias' ? 'active' : ''; ?>" data-tab="emergencias">🚨 Emergencias</button>
    <button class="tab-button <?php echo $tab_activa == 'registro' ? 'active' : ''; ?>" data-tab="registro">📝 Registro</button>
</nav>

<!-- Sección Unidades -->
<section id="unidades" class="tab-content <?php echo $tab_activa == 'unidades' ? 'active' : ''; ?>" aria-label="Estado de unidades">
    <h2>🚔 Estado de Unidades</h2>
    
    <!-- Formulario para agregar/editar unidad -->
    <div class="form-container">
        <h3 id="form-unidad-titulo">➕ Agregar Nueva Unidad</h3>
        <form id="form-unidad" method="POST" action="RadioGuardar.php" class="crud-form">
            <input type="hidden" name="tipo_guardar" value="unidad">
            <input type="hidden" name="id_unidad" id="id_unidad" value="">
            <input type="hidden" name="tab_activa" value="unidades">
            
            <div class="form-row">
                <input type="text" name="codigo" id="codigo_unidad" placeholder="Código (ej: Zeta-01)" required>
                <select name="tipo" id="tipo_unidad" required>
                    <option value="">Seleccionar tipo</option>
                    <option value="moto">Moto</option>
                    <option value="auto">Auto</option>
                    <option value="camioneta">Camioneta</option>
                    <option value="helicoptero">Helicóptero</option>
                </select>
            </div>
            
            <div class="form-row">
                <select name="estado" id="estado_unidad" required>
                    <option value="">Seleccionar estado</option>
                    <option value="disponible">Disponible</option>
                    <option value="ocupado">Ocupado</option>
                    <option value="fuera_servicio">Fuera de servicio</option>
                </select>
                <input type="text" name="oficial_nombre" id="oficial_nombre_unidad" placeholder="Nombre del oficial" required>
            </div>
            
            <div class="form-row">
                <input type="text" name="oficial_rango" id="oficial_rango_unidad" placeholder="Rango del oficial" required>
                <input type="text" name="sector" id="sector_unidad" placeholder="Sector" required>
            </div>
            
            <div class="form-buttons">
                <button type="submit" class="btn-guardar">💾 Guardar Unidad</button>
                <button type="button" class="btn-cancelar" onclick="cancelarEdicionUnidad()">❌ Cancelar</button>
            </div>
        </form>
    </div>
    
    <div class="units-grid">
        <?php if(count($unidades) > 0): ?>
            <?php foreach($unidades as $u): ?>
                <article class="unit-card <?php echo htmlspecialchars($u['estado']); ?>">
                    <h3><?php echo htmlspecialchars(ucfirst($u['tipo']) . " " . $u['codigo']); ?></h3>
                    <p><strong>Estado:</strong> <?php echo ucfirst(str_replace('_', ' ', $u['estado'])); ?></p>
                    <p><strong>Oficial:</strong> <?php echo htmlspecialchars($u['oficial_nombre'] . " (" . $u['oficial_rango'] . ")"); ?></p>
                    <p><strong>Sector:</strong> <?php echo htmlspecialchars($u['sector']); ?></p>
                    <div class="unit-actions">
                        <button class="btn-editar" onclick="editarUnidad(<?php echo $u['id']; ?>, '<?php echo addslashes($u['codigo']); ?>', '<?php echo addslashes($u['tipo']); ?>', '<?php echo addslashes($u['estado']); ?>', '<?php echo addslashes($u['oficial_nombre']); ?>', '<?php echo addslashes($u['oficial_rango']); ?>', '<?php echo addslashes($u['sector']); ?>')">✏️ Editar</button>
                        <button class="btn-eliminar" onclick="eliminarUnidad(<?php echo $u['id']; ?>)">🗑️ Eliminar</button>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-data">
                <p>No hay unidades registradas. Agrega la primera unidad usando el formulario arriba.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Sección Radio -->
<section id="radio" class="tab-content <?php echo $tab_activa == 'radio' ? 'active' : ''; ?>" aria-label="Control de radio">
    <h2>📻 Control de Radio</h2>
    <div class="radio-controls">
        <div class="frequency-display">FREQ: <span id="frequency">460.125</span> MHz</div>
        <div class="control-buttons">
            <button type="button" class="control-btn" id="transmitirBtn">🎤 Transmitir</button>
            <button type="button" class="control-btn" onclick="cambiarFrecuencia()">📡 Cambiar Frecuencia</button>
            <button type="button" class="control-btn" onclick="reportarEmergencia()">🚨 Emergencia</button>
            <button type="button" class="control-btn" onclick="limpiarCanal()">🔇 Limpiar Canal</button>
        </div>
    </div>
    
    <!-- Gestión de Comunicaciones -->
    <div class="form-container">
        <h3>📨 Gestión de Comunicaciones</h3>
        
        <!-- Formulario para nueva comunicación -->
        <div class="comms-management">
            <h4>➕ Nueva Comunicación</h4>
            <form method="POST" action="RadioEnviar.php" class="message-form">
                <input type="hidden" name="tab_activa" value="radio">
                <div class="form-row">
                    <input type="text" name="emisor" placeholder="Tu nombre" required>
                    <input type="text" name="mensaje" placeholder="Escribe tu mensaje..." required>
                    <button type="submit">🎤 Transmitir Mensaje</button>
                </div>
            </form>
        </div>

        <!-- Formulario para editar comunicación -->
        <div class="comms-edit-form" id="comms-edit-form" style="display: none;">
            <h4>✏️ Editar Comunicación</h4>
            <form id="form-editar-comunicacion" class="message-form">
                <input type="hidden" name="id_comunicacion" id="id_comunicacion">
                <div class="form-row">
                    <input type="text" name="emisor" id="emisor_edit" placeholder="Emisor" required>
                    <input type="text" name="mensaje" id="mensaje_edit" placeholder="Mensaje" required>
                </div>
                <div class="form-buttons">
                    <button type="submit" class="btn-guardar">💾 Guardar Cambios</button>
                    <button type="button" class="btn-cancelar" onclick="cancelarEdicionComunicacion()">❌ Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <div class="log-container">
        <h3>📋 Comunicaciones Recientes</h3>
        <div id="radioLog">
            <?php if(count($comunicaciones) > 0): ?>
                <?php foreach($comunicaciones as $c): ?>
                    <div class="comunicacion-item" id="comunicacion-<?php echo $c['id']; ?>">
                        <div class="comms-content">
                            <p><strong><?php echo htmlspecialchars($c['emisor']); ?>:</strong> <?php echo htmlspecialchars($c['mensaje']); ?></p>
                            <small><?php echo $c['fecha']; ?></small>
                        </div>
                        <div class="comms-actions">
                            <button class="btn-editar-small" onclick="editarComunicacion(<?php echo $c['id']; ?>, '<?php echo addslashes($c['emisor']); ?>', '<?php echo addslashes($c['mensaje']); ?>')">✏️</button>
                            <button class="btn-eliminar-small" onclick="eliminarComunicacion(<?php echo $c['id']; ?>)">🗑️</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-data">
                    <p>No hay comunicaciones recientes. Envía el primer mensaje usando el formulario arriba.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Sección Emergencias -->
<section id="emergencias" class="tab-content <?php echo $tab_activa == 'emergencias' ? 'active' : ''; ?>" aria-label="Sistema de emergencias">
    <h2>🚨 Sistema de Emergencias</h2>
    
    <!-- Formulario para agregar/editar emergencia -->
    <div class="form-container">
        <h3 id="form-emergencia-titulo">🚨 Nueva Emergencia</h3>
        <form id="form-emergencia" method="POST" action="RadioGuardar.php" class="crud-form">
            <input type="hidden" name="tipo_guardar" value="emergencia">
            <input type="hidden" name="id_emergencia" id="id_emergencia" value="">
            <input type="hidden" name="tab_activa" value="emergencias">
            
            <div class="form-row">
                <input type="text" name="codigo" id="codigo_emergencia" placeholder="Código (ej: 10-54)" required>
                <input type="text" name="ubicacion" id="ubicacion_emergencia" placeholder="Ubicación" required>
            </div>
            
            <div class="form-row">
                <textarea name="descripcion" id="descripcion_emergencia" placeholder="Descripción de la emergencia" required></textarea>
            </div>
            
            <div class="form-row">
                <input type="text" name="unidades_asignadas" id="unidades_asignadas_emergencia" placeholder="Unidades asignadas (ej: Zeta-01, Zeta-02)">
                <select name="activa" id="activa_emergencia" required>
                    <option value="1">Activa</option>
                    <option value="0">Inactiva</option>
                </select>
            </div>
            
            <div class="form-buttons">
                <button type="submit" class="btn-guardar">💾 Guardar Emergencia</button>
                <button type="button" class="btn-cancelar" onclick="cancelarEdicionEmergencia()">❌ Cancelar</button>
            </div>
        </form>
    </div>
    
    <div class="emergencies-grid">
        <?php if(count($emergencias) > 0): ?>
            <?php foreach($emergencias as $e): ?>
                <div class="emergency-alert <?php echo $e['activa'] ? 'activa' : 'inactiva'; ?>">
                    <h3>⚠️ Código: <?php echo htmlspecialchars($e['codigo']); ?></h3>
                    <p><strong>Descripción:</strong> <?php echo htmlspecialchars($e['descripcion']); ?></p>
                    <p><strong>Ubicación:</strong> <?php echo htmlspecialchars($e['ubicacion']); ?></p>
                    <p><strong>Unidades:</strong> <?php echo htmlspecialchars($e['unidades_asignadas']); ?></p>
                    <p><strong>Estado:</strong> <?php echo $e['activa'] ? '🟢 Activa' : '🔴 Inactiva'; ?></p>
                    <p><em><?php echo $e['fecha']; ?></em></p>
                    <div class="emergency-actions">
                        <button class="btn-editar" onclick="editarEmergencia(<?php echo $e['id']; ?>, '<?php echo addslashes($e['codigo']); ?>', '<?php echo addslashes($e['descripcion']); ?>', '<?php echo addslashes($e['ubicacion']); ?>', '<?php echo addslashes($e['unidades_asignadas']); ?>', <?php echo $e['activa']; ?>)">✏️ Editar</button>
                        <button class="btn-eliminar" onclick="eliminarEmergencia(<?php echo $e['id']; ?>)">🗑️ Eliminar</button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-data">
                <p>No hay emergencias registradas. Agrega la primera emergencia usando el formulario arriba.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Información adicional sobre códigos de emergencia -->
    <div class="units-grid">
        <article class="unit-card">
            <h3>🚨 Códigos de Emergencia</h3>
            <ul>
                <li><strong>10-13:</strong> Oficial necesita ayuda</li>
                <li><strong>10-54:</strong> Accidente de tráfico</li>
                <li><strong>10-56:</strong> Persona intoxicada</li>
                <li><strong>Code 3:</strong> Máxima prioridad</li>
            </ul>
        </article>
        <article class="unit-card">
            <h3>📍 Zonas de Riesgo Alto</h3>
            <ul>
                <li><strong>Zona Roja:</strong> Centro Histórico</li>
                <li><strong>Zona Amarilla:</strong> Sector Industrial</li>
                <li><strong>Patrulla Extra:</strong> Parque Central</li>
            </ul>
        </article>
    </div>
</section>

<!-- Sección Registro -->
<section id="registro" class="tab-content <?php echo $tab_activa == 'registro' ? 'active' : ''; ?>" aria-label="Registro de actividades">
    <h2>📝 Registro Completo de Actividades</h2>
    
    <div class="registro-tabs">
        <button class="subtab-button active" data-subtab="comunicaciones">Comunicaciones</button>
        <button class="subtab-button" data-subtab="emergencias">Emergencias</button>
        <button class="subtab-button" data-subtab="ubicaciones">Ubicaciones</button>
    </div>
    
    <!-- Sub-pestaña Comunicaciones -->
    <div id="comunicaciones" class="subtab-content active">
        <h3>📨 Historial de Comunicaciones</h3>
        <div class="log-container">
            <?php if(count($comunicaciones) > 0): ?>
                <?php foreach($comunicaciones as $c): ?>
                    <div class="registro-item">
                        <div class="comms-content">
                            <p><strong><?php echo htmlspecialchars($c['emisor']); ?>:</strong> <?php echo htmlspecialchars($c['mensaje']); ?></p>
                            <small><?php echo $c['fecha']; ?></small>
                        </div>
                        <div class="comms-actions">
                            <button class="btn-editar-small" onclick="editarComunicacion(<?php echo $c['id']; ?>, '<?php echo addslashes($c['emisor']); ?>', '<?php echo addslashes($c['mensaje']); ?>')">✏️</button>
                            <button class="btn-eliminar-small" onclick="eliminarComunicacion(<?php echo $c['id']; ?>)">🗑️</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-data">
                    <p>No hay comunicaciones registradas.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Sub-pestaña Emergencias -->
    <div id="emergencias-registro" class="subtab-content">
        <h3>🚨 Historial de Emergencias</h3>
        <div class="log-container">
            <?php if(count($emergencias) > 0): ?>
                <?php foreach($emergencias as $e): ?>
                    <div class="registro-item">
                        <div class="emergency-content">
                            <h4>⚠️ <?php echo htmlspecialchars($e['codigo']); ?> - <?php echo htmlspecialchars($e['ubicacion']); ?></h4>
                            <p><?php echo htmlspecialchars($e['descripcion']); ?></p>
                            <p><strong>Unidades:</strong> <?php echo htmlspecialchars($e['unidades_asignadas']); ?></p>
                            <small><?php echo $e['fecha']; ?> - <?php echo $e['activa'] ? 'Activa' : 'Inactiva'; ?></small>
                        </div>
                        <div class="emergency-actions">
                            <button class="btn-editar-small" onclick="editarEmergencia(<?php echo $e['id']; ?>, '<?php echo addslashes($e['codigo']); ?>', '<?php echo addslashes($e['descripcion']); ?>', '<?php echo addslashes($e['ubicacion']); ?>', '<?php echo addslashes($e['unidades_asignadas']); ?>', <?php echo $e['activa']; ?>)">✏️</button>
                            <button class="btn-eliminar-small" onclick="eliminarEmergencia(<?php echo $e['id']; ?>)">🗑️</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-data">
                    <p>No hay emergencias registradas.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Sub-pestaña Ubicaciones -->
    <div id="ubicaciones" class="subtab-content">
        <h3>📍 Gestión de Ubicaciones</h3>
        
        <!-- Formulario para agregar/editar ubicación -->
        <div class="form-container">
            <h4 id="form-ubicacion-titulo">➕ Nueva Ubicación</h4>
            <form id="form-ubicacion" method="POST" action="RadioGuardar.php" class="crud-form">
                <input type="hidden" name="tipo_guardar" value="ubicacion">
                <input type="hidden" name="id_ubicacion" id="id_ubicacion" value="">
                <input type="hidden" name="tab_activa" value="registro">
                
                <div class="form-row">
                    <input type="text" name="nombre" id="nombre_ubicacion" placeholder="Nombre de la ubicación" required>
                    <select name="tipo" id="tipo_ubicacion" required>
                        <option value="">Tipo</option>
                        <option value="denuncia">Denuncia</option>
                        <option value="vehiculo">Vehículo</option>
                        <option value="civil">Civil</option>
                        <option value="otro">Otro</option>
                    </select>
                </div>
                
                <div class="form-row">
                    <input type="text" name="lat" id="lat_ubicacion" placeholder="Latitud" required>
                    <input type="text" name="lng" id="lng_ubicacion" placeholder="Longitud" required>
                </div>
                
                <div class="form-row">
                    <textarea name="descripcion" id="descripcion_ubicacion" placeholder="Descripción"></textarea>
                </div>
                
                <div class="form-buttons">
                    <button type="submit" class="btn-guardar">💾 Guardar Ubicación</button>
                    <button type="button" class="btn-cancelar" onclick="cancelarEdicionUbicacion()">❌ Cancelar</button>
                </div>
            </form>
        </div>
        
        <div class="ubicaciones-grid">
            <h4>📍 Ubicaciones Registradas</h4>
            <?php if(count($ubicaciones) > 0): ?>
                <?php foreach($ubicaciones as $u): ?>
                    <div class="ubicacion-item">
                        <div class="ubicacion-content">
                            <h5>📍 <?php echo htmlspecialchars($u['nombre']); ?></h5>
                            <p><strong>Tipo:</strong> <?php echo ucfirst($u['tipo']); ?></p>
                            <p><strong>Coordenadas:</strong> <?php echo $u['lat']; ?>, <?php echo $u['lng']; ?></p>
                            <?php if($u['descripcion']): ?>
                                <p><?php echo htmlspecialchars($u['descripcion']); ?></p>
                            <?php endif; ?>
                            <small>ID: <?php echo $u['id']; ?></small>
                        </div>
                        <div class="ubicacion-actions">
                            <button class="btn-editar" onclick="editarUbicacion(<?php echo $u['id']; ?>, '<?php echo addslashes($u['nombre']); ?>', '<?php echo addslashes($u['descripcion']); ?>', '<?php echo $u['lat']; ?>', '<?php echo $u['lng']; ?>', '<?php echo addslashes($u['tipo']); ?>')">✏️ Editar</button>
                            <button class="btn-eliminar" onclick="eliminarUbicacion(<?php echo $u['id']; ?>)">🗑️ Eliminar</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-data">
                    <p>No hay ubicaciones registradas. Agrega la primera ubicación usando el formulario arriba.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

</div>

<script>
// Funciones para Unidades (definirlas directamente en el HTML)
function editarUnidad(id, codigo, tipo, estado, oficial_nombre, oficial_rango, sector) {
    document.getElementById('id_unidad').value = id;
    document.getElementById('codigo_unidad').value = codigo;
    document.getElementById('tipo_unidad').value = tipo;
    document.getElementById('estado_unidad').value = estado;
    document.getElementById('oficial_nombre_unidad').value = oficial_nombre;
    document.getElementById('oficial_rango_unidad').value = oficial_rango;
    document.getElementById('sector_unidad').value = sector;
    document.getElementById('form-unidad-titulo').textContent = '✏️ Editar Unidad';
    
    // Scroll al formulario
    document.getElementById('form-unidad').scrollIntoView({ behavior: 'smooth' });
}

function cancelarEdicionUnidad() {
    document.getElementById('id_unidad').value = '';
    document.getElementById('codigo_unidad').value = '';
    document.getElementById('tipo_unidad').value = '';
    document.getElementById('estado_unidad').value = '';
    document.getElementById('oficial_nombre_unidad').value = '';
    document.getElementById('oficial_rango_unidad').value = '';
    document.getElementById('sector_unidad').value = '';
    document.getElementById('form-unidad-titulo').textContent = '➕ Agregar Nueva Unidad';
}

function eliminarUnidad(id) {
    if (confirm('¿Estás seguro de que quieres eliminar esta unidad?')) {
        fetch('Radio.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=delete_unidad&id=${id}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error al eliminar la unidad');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al eliminar la unidad');
        });
    }
}

// Funciones para Comunicaciones
function editarComunicacion(id, emisor, mensaje) {
    document.getElementById('id_comunicacion').value = id;
    document.getElementById('emisor_edit').value = emisor;
    document.getElementById('mensaje_edit').value = mensaje;
    
    // Mostrar formulario de edición
    document.getElementById('comms-edit-form').style.display = 'block';
    
    // Scroll al formulario
    document.getElementById('comms-edit-form').scrollIntoView({ behavior: 'smooth' });
}

function cancelarEdicionComunicacion() {
    document.getElementById('id_comunicacion').value = '';
    document.getElementById('emisor_edit').value = '';
    document.getElementById('mensaje_edit').value = '';
    document.getElementById('comms-edit-form').style.display = 'none';
}

function eliminarComunicacion(id) {
    if (confirm('¿Estás seguro de que quieres eliminar esta comunicación?')) {
        fetch('Radio.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=delete_comunicacion&id=${id}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error al eliminar la comunicación');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al eliminar la comunicación');
        });
    }
}

// Funciones para Emergencias
function editarEmergencia(id, codigo, descripcion, ubicacion, unidades_asignadas, activa) {
    document.getElementById('id_emergencia').value = id;
    document.getElementById('codigo_emergencia').value = codigo;
    document.getElementById('descripcion_emergencia').value = descripcion;
    document.getElementById('ubicacion_emergencia').value = ubicacion;
    document.getElementById('unidades_asignadas_emergencia').value = unidades_asignadas;
    document.getElementById('activa_emergencia').value = activa;
    document.getElementById('form-emergencia-titulo').textContent = '✏️ Editar Emergencia';
    
    // Scroll al formulario
    document.getElementById('form-emergencia').scrollIntoView({ behavior: 'smooth' });
}

function cancelarEdicionEmergencia() {
    document.getElementById('id_emergencia').value = '';
    document.getElementById('codigo_emergencia').value = '';
    document.getElementById('descripcion_emergencia').value = '';
    document.getElementById('ubicacion_emergencia').value = '';
    document.getElementById('unidades_asignadas_emergencia').value = '';
    document.getElementById('activa_emergencia').value = '1';
    document.getElementById('form-emergencia-titulo').textContent = '🚨 Nueva Emergencia';
}

function eliminarEmergencia(id) {
    if (confirm('¿Estás seguro de que quieres eliminar esta emergencia?')) {
        fetch('Radio.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=delete_emergencia&id=${id}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error al eliminar la emergencia');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al eliminar la emergencia');
        });
    }
}

// Funciones para Ubicaciones
function editarUbicacion(id, nombre, descripcion, lat, lng, tipo) {
    document.getElementById('id_ubicacion').value = id;
    document.getElementById('nombre_ubicacion').value = nombre;
    document.getElementById('descripcion_ubicacion').value = descripcion;
    document.getElementById('lat_ubicacion').value = lat;
    document.getElementById('lng_ubicacion').value = lng;
    document.getElementById('tipo_ubicacion').value = tipo;
    document.getElementById('form-ubicacion-titulo').textContent = '✏️ Editar Ubicación';
    
    // Scroll al formulario
    document.getElementById('form-ubicacion').scrollIntoView({ behavior: 'smooth' });
}

function cancelarEdicionUbicacion() {
    document.getElementById('id_ubicacion').value = '';
    document.getElementById('nombre_ubicacion').value = '';
    document.getElementById('descripcion_ubicacion').value = '';
    document.getElementById('lat_ubicacion').value = '';
    document.getElementById('lng_ubicacion').value = '';
    document.getElementById('tipo_ubicacion').value = '';
    document.getElementById('form-ubicacion-titulo').textContent = '➕ Nueva Ubicación';
}

function eliminarUbicacion(id) {
    if (confirm('¿Estás seguro de que quieres eliminar esta ubicación?')) {
        fetch('Radio.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=delete_ubicacion&id=${id}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error al eliminar la ubicación');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al eliminar la ubicación');
        });
    }
}

// Funciones de control de radio
function cambiarFrecuencia() {
    const frecuencias = ['460.125', '460.250', '460.375', '460.500'];
    const frecuenciaDisplay = document.getElementById('frequency');
    const currentFreq = frecuenciaDisplay.textContent;
    const currentIndex = frecuencias.indexOf(currentFreq);
    const nextIndex = (currentIndex + 1) % frecuencias.length;
    frecuenciaDisplay.textContent = frecuencias[nextIndex];
    alert(`Frecuencia cambiada a: ${frecuencias[nextIndex]} MHz`);
}

function reportarEmergencia() {
    const emisor = prompt('Ingresa tu identificador:');
    if (emisor) {
        const mensaje = '🚨 REPORTANDO EMERGENCIA - NECESITO ASISTENCIA INMEDIATA';
        // Enviar automáticamente como comunicación de emergencia
        fetch('RadioEnviar.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `emisor=${encodeURIComponent(emisor)}&mensaje=${encodeURIComponent(mensaje)}&tab_activa=radio`
        })
        .then(() => {
            alert('Emergencia reportada. Refuerzos en camino.');
            location.reload();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al reportar emergencia');
        });
    }
}

function limpiarCanal() {
    if (confirm('¿Estás seguro de que quieres limpiar todas las comunicaciones recientes?')) {
        alert('Canal limpiado. Todas las comunicaciones han sido archivadas.');
    }
}

function guardarEdicionComunicacion() {
    const id = document.getElementById('id_comunicacion').value;
    const emisor = document.getElementById('emisor_edit').value;
    const mensaje = document.getElementById('mensaje_edit').value;
    
    if (!id || !emisor || !mensaje) {
        alert('Por favor completa todos los campos');
        return;
    }
    
    fetch('Radio.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=editar_comunicacion&id=${id}&emisor=${encodeURIComponent(emisor)}&mensaje=${encodeURIComponent(mensaje)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Comunicación actualizada correctamente');
            location.reload();
        } else {
            alert('Error al actualizar la comunicación');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al actualizar la comunicación');
    });
}

// Función para limpiar formularios después de guardar
function limpiarFormularios() {
    // Limpiar formulario de unidades
    document.getElementById('id_unidad').value = '';
    document.getElementById('codigo_unidad').value = '';
    document.getElementById('tipo_unidad').value = '';
    document.getElementById('estado_unidad').value = '';
    document.getElementById('oficial_nombre_unidad').value = '';
    document.getElementById('oficial_rango_unidad').value = '';
    document.getElementById('sector_unidad').value = '';
    document.getElementById('form-unidad-titulo').textContent = '➕ Agregar Nueva Unidad';
    
    // Limpiar formulario de emergencias
    document.getElementById('id_emergencia').value = '';
    document.getElementById('codigo_emergencia').value = '';
    document.getElementById('descripcion_emergencia').value = '';
    document.getElementById('ubicacion_emergencia').value = '';
    document.getElementById('unidades_asignadas_emergencia').value = '';
    document.getElementById('activa_emergencia').value = '1';
    document.getElementById('form-emergencia-titulo').textContent = '🚨 Nueva Emergencia';
    
    // Limpiar formulario de ubicaciones
    document.getElementById('id_ubicacion').value = '';
    document.getElementById('nombre_ubicacion').value = '';
    document.getElementById('descripcion_ubicacion').value = '';
    document.getElementById('lat_ubicacion').value = '';
    document.getElementById('lng_ubicacion').value = '';
    document.getElementById('tipo_ubicacion').value = '';
    document.getElementById('form-ubicacion-titulo').textContent = '➕ Nueva Ubicación';
}

// Función para actualizar logs de radio
function actualizarLogsRadio() {
    const radioLog = document.getElementById('radioLog');
    const radioTab = document.getElementById('radio');
    
    // Solo actualizar si la pestaña de Radio está activa
    if (radioLog && radioTab && radioTab.classList.contains('active')) {
        fetch('Radio.php?action=log')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error en la respuesta del servidor');
                }
                return response.json();
            })
            .then(data => {
                if (Array.isArray(data)) {
                    radioLog.innerHTML = '';
                    if (data.length > 0) {
                        data.forEach(item => {
                            const div = document.createElement('div');
                            div.className = 'comunicacion-item';
                            div.id = 'comunicacion-' + item.id;
                            div.innerHTML = `
                                <div class="comms-content">
                                    <p><strong>${item.emisor}:</strong> ${item.mensaje}</p>
                                    <small>${item.fecha}</small>
                                </div>
                                <div class="comms-actions">
                                    <button class="btn-editar-small" onclick="editarComunicacion(${item.id}, '${item.emisor.replace(/'/g, "\\'")}', '${item.mensaje.replace(/'/g, "\\'")}')">✏️</button>
                                    <button class="btn-eliminar-small" onclick="eliminarComunicacion(${item.id})">🗑️</button>
                                </div>
                            `;
                            radioLog.appendChild(div);
                        });
                    } else {
                        radioLog.innerHTML = '<div class="no-data"><p>No hay comunicaciones recientes.</p></div>';
                    }
                }
            })
            .catch(err => { 
                console.error('Error al cargar logs:', err); 
            });
    }
}

// Sistema de pestañas
document.addEventListener('DOMContentLoaded', function() {
    // Si hay un mensaje de éxito, limpiar los formularios
    const alertSuccess = document.querySelector('.alert-success');
    if (alertSuccess) {
        limpiarFormularios();
    }
    
    // Ocultar mensaje después de 5 segundos
    setTimeout(() => {
        const alert = document.querySelector('.alert');
        if (alert) {
            alert.style.display = 'none';
        }
    }, 5000);

    // Funcionalidad de pestañas principales
    const tabButtons = document.querySelectorAll('.tab-button');
    const tabContents = document.querySelectorAll('.tab-content');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            const tabId = button.getAttribute('data-tab');
            
            // Remover clase active de todos los botones y contenidos
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));
            
            // Agregar clase active al botón y contenido actual
            button.classList.add('active');
            document.getElementById(tabId).classList.add('active');
            
            // Si es la pestaña de radio, actualizar logs inmediatamente
            if (tabId === 'radio') {
                actualizarLogsRadio();
            }
        });
    });

    // Funcionalidad de sub-pestañas en registro
    const subtabButtons = document.querySelectorAll('.subtab-button');
    const subtabContents = document.querySelectorAll('.subtab-content');
    
    subtabButtons.forEach(button => {
        button.addEventListener('click', () => {
            const subtabId = button.getAttribute('data-subtab');
            
            // Remover clase active de todos los botones y contenidos
            subtabButtons.forEach(btn => btn.classList.remove('active'));
            subtabContents.forEach(content => content.classList.remove('active'));
            
            // Agregar clase active al botón y contenido actual
            button.classList.add('active');
            
            // Manejar el ID especial para emergencias en registro
            const contentId = subtabId === 'emergencias' ? 'emergencias-registro' : subtabId;
            document.getElementById(contentId).classList.add('active');
        });
    });

    // Botón de transmitir
    const transmitirBtn = document.getElementById('transmitirBtn');
    if (transmitirBtn) {
        transmitirBtn.addEventListener('click', function() {
            alert('Modo transmisión activado - Habla ahora');
        });
    }

    // Inicializar formulario de edición de comunicaciones
    const formEditarComunicacion = document.getElementById('form-editar-comunicacion');
    if (formEditarComunicacion) {
        formEditarComunicacion.addEventListener('submit', function(e) {
            e.preventDefault();
            guardarEdicionComunicacion();
        });
    }
});

// Actualizar logs cada 5 segundos
setInterval(actualizarLogsRadio, 5000);
</script>

</body>
</html>