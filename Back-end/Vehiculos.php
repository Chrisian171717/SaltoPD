<?php
// Configuración de CORS para permitir requests desde el frontend
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
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
// GESTIÓN DE DENUNCIAS - API ENDPOINTS
// =============================================

// Obtener denuncias de un vehículo
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

// Agregar nueva denuncia
if (isset($_GET['action']) && $_GET['action'] === 'add_denuncia' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $matricula = $conn->real_escape_string($data['vehiculo_matricula'] ?? '');
    $fecha = $conn->real_escape_string($data['fecha_denuncia'] ?? '');
    $tipo = $conn->real_escape_string($data['tipo_denuncia'] ?? '');
    $descripcion = $conn->real_escape_string($data['descripcion'] ?? '');
    $estado = $conn->real_escape_string($data['estado'] ?? 'Activa');
    
    // Validar que el vehículo existe
    $check_vehiculo = $conn->query("SELECT Matricula FROM automovil WHERE Matricula = '$matricula'");
    if (!$check_vehiculo || $check_vehiculo->num_rows === 0) {
        header('Content-Type: application/json');
        echo json_encode(["error" => "El vehículo con matrícula $matricula no existe"]);
        exit;
    }
    
    $sql = "INSERT INTO denuncias_vehiculos (vehiculo_matricula, fecha_denuncia, tipo_denuncia, descripcion, estado) 
            VALUES ('$matricula', '$fecha', '$tipo', '$descripcion', '$estado')";
    
    if ($conn->query($sql)) {
        $nueva_denuncia_id = $conn->insert_id;
        header('Content-Type: application/json');
        echo json_encode([
            "success" => true,
            "message" => "Denuncia agregada correctamente",
            "id" => $nueva_denuncia_id
        ]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(["error" => "Error al agregar denuncia: " . $conn->error]);
    }
    exit;
}

// Actualizar denuncia
if (isset($_GET['action']) && $_GET['action'] === 'update_denuncia' && $_SERVER['REQUEST_METHOD'] === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $id = intval($data['id'] ?? 0);
    $fecha = $conn->real_escape_string($data['fecha_denuncia'] ?? '');
    $tipo = $conn->real_escape_string($data['tipo_denuncia'] ?? '');
    $descripcion = $conn->real_escape_string($data['descripcion'] ?? '');
    $estado = $conn->real_escape_string($data['estado'] ?? 'Activa');
    
    $sql = "UPDATE denuncias_vehiculos SET 
            fecha_denuncia = '$fecha', 
            tipo_denuncia = '$tipo', 
            descripcion = '$descripcion', 
            estado = '$estado' 
            WHERE id = $id";
    
    if ($conn->query($sql)) {
        header('Content-Type: application/json');
        echo json_encode([
            "success" => true,
            "message" => "Denuncia actualizada correctamente"
        ]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(["error" => "Error al actualizar denuncia: " . $conn->error]);
    }
    exit;
}

// Eliminar denuncia
if (isset($_GET['action']) && $_GET['action'] === 'delete_denuncia' && $_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $id = intval($_GET['id'] ?? 0);
    
    $sql = "DELETE FROM denuncias_vehiculos WHERE id = $id";
    
    if ($conn->query($sql)) {
        header('Content-Type: application/json');
        echo json_encode([
            "success" => true,
            "message" => "Denuncia eliminada correctamente"
        ]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(["error" => "Error al eliminar denuncia: " . $conn->error]);
    }
    exit;
}

// =============================================
// GESTIÓN DE VEHÍCULOS (CÓDIGO ORIGINAL)
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
$action = $_GET['action'] ?? 'list';
$error = "";
$success = "";
$vehiculos = []; // INICIALIZAR LA VARIABLE VEHÍCULOS

// PROCESAR AGREGAR VEHÍCULO
if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $matricula = trim($conn->real_escape_string($_POST['matricula'] ?? ''));
    $marca = trim($conn->real_escape_string($_POST['marca'] ?? ''));
    $modelo = trim($conn->real_escape_string($_POST['modelo'] ?? ''));
    $tipo_vehiculo = trim($conn->real_escape_string($_POST['tipo_vehiculo'] ?? ''));

    // Validaciones
    if (empty($matricula) || empty($marca) || empty($modelo) || empty($tipo_vehiculo)) {
        $error = "Todos los campos son obligatorios.";
    } elseif (strlen($matricula) > 15) {
        $error = "La matrícula no puede tener más de 15 caracteres.";
    } elseif (strlen($marca) > 30) {
        $error = "La marca no puede tener más de 30 caracteres.";
    } elseif (strlen($modelo) > 30) {
        $error = "El modelo no puede tener más de 30 caracteres.";
    } else {
        // Verificar si la matrícula ya existe
        $check_sql = "SELECT Matricula FROM automovil WHERE Matricula = '$matricula'";
        $check_result = $conn->query($check_sql);
        
        if ($check_result && $check_result->num_rows > 0) {
            $error = "La matrícula <strong>'$matricula'</strong> ya existe en el sistema.";
        } else {
            $sql = "INSERT INTO automovil (Matricula, Marca, Modelo, Tipo_Vehiculo) 
                    VALUES ('$matricula', '$marca', '$modelo', '$tipo_vehiculo')";
            
            if ($conn->query($sql)) {
                $success = "✅ Vehículo <strong>'$matricula'</strong> agregado correctamente.";
                $action = 'list'; // Redirigir a la lista
            } else {
                $error = "❌ Error al agregar vehículo: " . $conn->error;
            }
        }
    }
}

// PROCESAR EDITAR VEHÍCULO
if ($action === 'edit') {
    $matricula_original = $_GET['matricula'] ?? '';
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Procesar actualización
        $nueva_matricula = trim($conn->real_escape_string($_POST['matricula'] ?? ''));
        $marca = trim($conn->real_escape_string($_POST['marca'] ?? ''));
        $modelo = trim($conn->real_escape_string($_POST['modelo'] ?? ''));
        $tipo_vehiculo = trim($conn->real_escape_string($_POST['tipo_vehiculo'] ?? ''));

        // Validaciones
        if (empty($nueva_matricula) || empty($marca) || empty($modelo) || empty($tipo_vehiculo)) {
            $error = "Todos los campos son obligatorios.";
        } else {
            // Si cambió la matrícula, verificar que no exista
            if ($nueva_matricula !== $matricula_original) {
                $check_sql = "SELECT Matricula FROM automovil WHERE Matricula = '$nueva_matricula'";
                $check_result = $conn->query($check_sql);
                
                if ($check_result && $check_result->num_rows > 0) {
                    $error = "La matrícula <strong>'$nueva_matricula'</strong> ya existe en el sistema.";
                } else {
                    $sql = "UPDATE automovil SET Matricula='$nueva_matricula', Marca='$marca', Modelo='$modelo', Tipo_Vehiculo='$tipo_vehiculo' 
                            WHERE Matricula='$matricula_original'";
                    if ($conn->query($sql)) {
                        $success = "✅ Vehículo <strong>'$matricula_original'</strong> actualizado correctamente a <strong>'$nueva_matricula'</strong>.";
                        $action = 'list';
                    } else {
                        $error = "❌ Error al actualizar: " . $conn->error;
                    }
                }
            } else {
                // No cambió la matrícula, actualizar directamente
                $sql = "UPDATE automovil SET Marca='$marca', Modelo='$modelo', Tipo_Vehiculo='$tipo_vehiculo' 
                        WHERE Matricula='$matricula_original'";
                if ($conn->query($sql)) {
                    $success = "✅ Vehículo <strong>'$matricula_original'</strong> actualizado correctamente.";
                    $action = 'list';
                } else {
                    $error = "❌ Error al actualizar: " . $conn->error;
                }
            }
        }
    } else {
        // Cargar datos del vehículo para editar
        if (empty($matricula_original)) {
            $error = "Matrícula inválida.";
            $action = 'list';
        } else {
            $res = $conn->query("SELECT * FROM automovil WHERE Matricula='$matricula_original'");
            if ($res && $res->num_rows > 0) {
                $vehiculo = $res->fetch_assoc();
            } else {
                $error = "❌ Vehículo no encontrado.";
                $action = 'list';
            }
        }
    }
}

// PROCESAR ELIMINAR VEHÍCULO
if ($action === 'delete') {
    $matricula = $_GET['matricula'] ?? '';
    if (!empty($matricula)) {
        // Verificar que el vehículo existe
        $check_sql = "SELECT Matricula FROM automovil WHERE Matricula = '$matricula'";
        $check_result = $conn->query($check_sql);
        
        if ($check_result && $check_result->num_rows > 0) {
            // Primero eliminar las denuncias asociadas (si existen)
            $conn->query("DELETE FROM denuncias_vehiculos WHERE vehiculo_matricula = '$matricula'");
            
            // Luego eliminar el vehículo
            $sql = "DELETE FROM automovil WHERE Matricula='$matricula'";
            if ($conn->query($sql)) {
                $success = "✅ Vehículo <strong>'$matricula'</strong> y sus denuncias asociadas eliminados correctamente.";
            } else {
                $error = "❌ Error al eliminar: " . $conn->error;
            }
        } else {
            $error = "❌ El vehículo con matrícula <strong>'$matricula'</strong> no existe.";
        }
    } else {
        $error = "❌ Matrícula inválida para eliminar.";
    }
    $action = 'list';
}

// CARGAR LISTA DE VEHÍCULOS - SIEMPRE EJECUTAR PARA LIST
if ($action === 'list') {
    $res = $conn->query("SELECT * FROM automovil ORDER BY Matricula");
    if ($res) {
        $vehiculos = $res->fetch_all(MYSQLI_ASSOC);
    } else {
        $error = "❌ Error al cargar vehículos: " . $conn->error;
        $vehiculos = [];
    }
}

// Obtener estadísticas - CON VALIDACIÓN
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
    <title>Gestión de Vehículos - Sistema Completo</title>
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
            <!-- Imagen de vehículo pequeña en el header -->
            <h1>
                <img src="../Front-end/Vehiculo.png" alt="Icono de vehículo" class="header-icon" />
                Sistema de Gestión de Vehículos y Denuncias
            </h1>
            <p>Panel de Administración - CRUD Completo</p>
        </div>
        
        <div class="content">
            <?php if($error): ?>
                <div class="alert alert-error">
                    <?= $error ?>
                </div>
            <?php endif; ?>
            
            <?php if($success): ?>
                <div class="alert alert-success">
                    <?= $success ?>
                </div>
            <?php endif; ?>

            <?php if($action === 'list'): ?>
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

                <!-- Acciones Rápidas -->
                <div class="quick-actions">
                    <h3>🚗 Acciones Rápidas</h3>
                    <div class="actions-container">
                        <a href="?action=add" class="btn btn-add">
                            <span class="icon-large">➕</span> Agregar Nuevo Automóvil
                        </a>
                        <a href="../Front-end/Vehiculo2.0.html" class="btn btn-view" target="_blank">
                            <span class="icon-large">👁️</span> Ver Vista de Búsqueda
                        </a>
                    </div>
                </div>

                <div class="actions-header">
                    <h2 class="page-title">
                        <img src="../Front-end/Vehiculo.png" alt="Vehículos" style="width: 35px; height: 35px; object-fit: contain;" />
                        Lista de Automóviles Registrados
                    </h2>
                    <div class="actions-buttons">
                        <a href="?action=add" class="btn btn-add">
                            <span class="icon-large">➕</span> Agregar Automóvil
                        </a>
                    </div>
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
                                            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                                                <a href="?action=edit&matricula=<?= urlencode($v['Matricula']) ?>" class="btn btn-edit" title="Editar vehículo">
                                                    ✏️ Editar
                                                </a>
                                                <a href="?action=delete&matricula=<?= urlencode($v['Matricula']) ?>" 
                                                   class="btn btn-delete" 
                                                   onclick="return confirm('¿Está seguro de que desea ELIMINAR permanentemente el vehículo <?= htmlspecialchars($v['Matricula']) ?>?')"
                                                   title="Eliminar vehículo">
                                                    🗑️ Eliminar
                                                </a>
                                                <button class="btn btn-info" onclick="mostrarDenuncias('<?= htmlspecialchars($v['Matricula']) ?>')" title="Ver denuncias">
                                                    📋 Denuncias
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <!-- Imagen de vehículo pequeña en estado vacío -->
                        <img src="../Front-end/Vehiculo.png" alt="Sin vehículos" />
                        <h3>No hay automóviles registrados</h3>
                        <p>El sistema está listo para agregar automóviles. Comienza registrando el primer vehículo.</p>
                        <a href="?action=add" class="btn btn-add-large">
                            <span class="icon-large">➕</span> Agregar Primer Automóvil
                        </a>
                    </div>
                <?php endif; ?>

                <!-- Sección de Denuncias (se muestra dinámicamente) -->
                <div class="denuncias-section" id="denuncias-section" style="display: none;">
                    <div class="denuncias-header">
                        <h2 class="denuncias-title">
                            📋 Denuncias del Vehículo
                            <span id="denuncias-vehiculo-info" style="font-size: 0.7em; color: #6c757d;"></span>
                        </h2>
                        <button id="agregar-denuncia" class="btn btn-success">
                            <span class="icon-large">➕</span> Agregar Denuncia
                        </button>
                    </div>
                    <div class="denuncias-lista" id="lista-denuncias">
                        <div class="sin-denuncias">Cargando denuncias...</div>
                    </div>
                </div>

                <!-- Panel de Administración Adicional -->
                <div class="admin-panel">
                    <a href="?action=add" class="btn btn-add">
                        <span class="icon-large">🚗</span> Agregar Más Automóviles
                    </a>
                    <a href="../Front-end/Vehiculo2.0.html" class="btn btn-admin" target="_blank">
                        <span class="icon-large">⚙️</span> Ir a Búsqueda Avanzada
                    </a>
                </div>

            <?php elseif($action === 'add' || $action === 'edit'): 
                $vehiculo = $vehiculo ?? ['Matricula'=>'','Marca'=>'','Modelo'=>'','Tipo_Vehiculo'=>''];
                $is_edit = $action === 'edit';
            ?>
                <div class="form-container">
                    <h2 style="text-align: center; margin-bottom: 30px; color: #2c3e50; display: flex; align-items: center; justify-content: center; gap: 10px;">
                        <img src="../Front-end/Vehiculo.png" alt="Vehículo" style="width: 35px; height: 35px; object-fit: contain;" />
                        <?= $is_edit ? '✏️ Editar Automóvil' : '➕ Agregar Nuevo Automóvil' ?>
                        <?= $is_edit ? '<br><small style="font-size: 0.6em; color: #7f8c8d;">Matrícula: ' . htmlspecialchars($vehiculo['Matricula']) . '</small>' : '' ?>
                    </h2>
                    
                    <form method="POST">
                        <div class="form-group">
                            <label for="matricula">📋 Matrícula:</label>
                            <input type="text" id="matricula" name="matricula" 
                                   value="<?= htmlspecialchars($vehiculo['Matricula']) ?>" 
                                   <?= $is_edit ? 'readonly' : '' ?>
                                   required maxlength="15" 
                                   placeholder="Ej: ABC123, XYZ789"
                                   pattern="[A-Za-z0-9]+"
                                   title="Solo letras y números, sin espacios">
                            <small style="color: #666; font-size: 0.9em;">
                                <?= $is_edit ? 'La matrícula no se puede modificar' : 'La matrícula es el identificador único del vehículo' ?>
                            </small>
                        </div>

                        <div class="form-group">
                            <label for="marca">🏷️ Marca:</label>
                            <input type="text" id="marca" name="marca" 
                                   value="<?= htmlspecialchars($vehiculo['Marca']) ?>" 
                                   required maxlength="30" 
                                   placeholder="Ej: Toyota, Ford, Chevrolet, Honda">
                        </div>

                        <div class="form-group">
                            <label for="modelo">🚀 Modelo:</label>
                            <input type="text" id="modelo" name="modelo" 
                                   value="<?= htmlspecialchars($vehiculo['Modelo']) ?>" 
                                   required maxlength="30" 
                                   placeholder="Ej: Corolla, Focus, Civic, Spark">
                        </div>

                        <div class="form-group">
                            <label for="tipo_vehiculo">🚙 Tipo de Vehículo:</label>
                            <select id="tipo_vehiculo" name="tipo_vehiculo" required>
                                <option value="">-- Seleccione un tipo --</option>
                                <option value="Sedán" <?= $vehiculo['Tipo_Vehiculo'] === 'Sedán' ? 'selected' : '' ?>>Sedán</option>
                                <option value="SUV" <?= $vehiculo['Tipo_Vehiculo'] === 'SUV' ? 'selected' : '' ?>>SUV</option>
                                <option value="Camioneta" <?= $vehiculo['Tipo_Vehiculo'] === 'Camioneta' ? 'selected' : '' ?>>Camioneta</option>
                                <option value="Hatchback" <?= $vehiculo['Tipo_Vehiculo'] === 'Hatchback' ? 'selected' : '' ?>>Hatchback</option>
                                <option value="Deportivo" <?= $vehiculo['Tipo_Vehiculo'] === 'Deportivo' ? 'selected' : '' ?>>Deportivo</option>
                                <option value="Motocicleta" <?= $vehiculo['Tipo_Vehiculo'] === 'Motocicleta' ? 'selected' : '' ?>>Motocicleta</option>
                                <option value="Camión" <?= $vehiculo['Tipo_Vehiculo'] === 'Camión' ? 'selected' : '' ?>>Camión</option>
                                <option value="Otro" <?= $vehiculo['Tipo_Vehiculo'] === 'Otro' ? 'selected' : '' ?>>Otro</option>
                            </select>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn" style="font-size: 18px; padding: 16px 32px;">
                                <?= $is_edit ? '💾 Guardar Cambios' : '✅ Agregar Automóvil' ?>
                            </button>
                            <a href="?action=list" class="btn btn-back" style="font-size: 18px; padding: 16px 32px;">❌ Cancelar</a>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal para agregar/editar denuncia -->
    <div id="modal-denuncia" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="modal-titulo">Agregar Denuncia</h3>
                <button class="close-modal" id="cerrar-modal">&times;</button>
            </div>
            <form id="form-denuncia">
                <input type="hidden" id="denuncia-id" value="">
                <input type="hidden" id="vehiculo-matricula" value="">
                
                <div class="form-group">
                    <label for="fecha-denuncia">Fecha de la denuncia:</label>
                    <input type="date" id="fecha-denuncia" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="tipo-denuncia">Tipo de denuncia:</label>
                    <select id="tipo-denuncia" class="form-control" required>
                        <option value="">Seleccione un tipo</option>
                        <option value="Robo">Robo</option>
                        <option value="Accidente">Accidente</option>
                        <option value="Infracción">Infracción</option>
                        <option value="Abandono">Abandono</option>
                        <option value="Estacionamiento indebido">Estacionamiento indebido</option>
                        <option value="Daños a propiedad">Daños a propiedad</option>
                        <option value="Otro">Otro</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="descripcion-denuncia">Descripción:</label>
                    <textarea id="descripcion-denuncia" class="form-control" placeholder="Describa los detalles de la denuncia..." required></textarea>
                </div>
                
                <div class="form-group">
                    <label for="estado-denuncia">Estado:</label>
                    <select id="estado-denuncia" class="form-control" required>
                        <option value="Activa">Activa</option>
                        <option value="En investigación">En investigación</option>
                        <option value="Resuelta">Resuelta</option>
                        <option value="Archivada">Archivada</option>
                    </select>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" id="cancelar-denuncia">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="guardar-denuncia">Guardar Denuncia</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal de confirmación para eliminar -->
    <div id="modal-confirmacion" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Confirmar Eliminación</h3>
                <button class="close-modal" id="cerrar-confirmacion">&times;</button>
            </div>
            <p>¿Está seguro de que desea eliminar esta denuncia? Esta acción no se puede deshacer.</p>
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" id="cancelar-eliminar">Cancelar</button>
                <button type="button" class="btn btn-danger" id="confirmar-eliminar">Eliminar</button>
            </div>
        </div>
    </div>

    <script>
        // Variables globales
        let denunciaAEliminar = null;
        let vehiculoActual = null;

        // Funciones para gestión de denuncias
        function mostrarDenuncias(matricula) {
            vehiculoActual = matricula;
            const seccion = document.getElementById('denuncias-section');
            const info = document.getElementById('denuncias-vehiculo-info');
            
            seccion.style.display = 'block';
            info.textContent = `(Matrícula: ${matricula})`;
            document.getElementById('vehiculo-matricula').value = matricula;
            
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
                        <div class="denuncia-acciones">
                            <button class="btn btn-primary" onclick="editarDenuncia(${denuncia.id})">
                                <span class="icon-small">✏️</span> Editar
                            </button>
                            <button class="btn btn-danger" onclick="solicitarEliminarDenuncia(${denuncia.id})">
                                <span class="icon-small">🗑️</span> Eliminar
                            </button>
                        </div>
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

        function mostrarModalAgregar() {
            document.getElementById('modal-titulo').textContent = 'Agregar Denuncia';
            document.getElementById('form-denuncia').reset();
            document.getElementById('denuncia-id').value = '';
            document.getElementById('fecha-denuncia').valueAsDate = new Date();
            document.getElementById('modal-denuncia').style.display = 'flex';
        }

        function editarDenuncia(id) {
            // Cargar datos de la denuncia
            fetch(`?action=get_denuncias&matricula=${vehiculoActual}`)
                .then(response => response.json())
                .then(denuncias => {
                    const denuncia = denuncias.find(d => d.id === id);
                    if (!denuncia) return;
                    
                    document.getElementById('modal-titulo').textContent = 'Editar Denuncia';
                    document.getElementById('denuncia-id').value = denuncia.id;
                    document.getElementById('fecha-denuncia').value = denuncia.fecha_denuncia;
                    document.getElementById('tipo-denuncia').value = denuncia.tipo_denuncia;
                    document.getElementById('descripcion-denuncia').value = denuncia.descripcion;
                    document.getElementById('estado-denuncia').value = denuncia.estado;
                    
                    document.getElementById('modal-denuncia').style.display = 'flex';
                })
                .catch(error => {
                    console.error('Error al cargar denuncia:', error);
                    mostrarNotificacion('Error al cargar la denuncia', 'error');
                });
        }

        function cerrarModal() {
            document.getElementById('modal-denuncia').style.display = 'none';
        }

        function guardarDenuncia(e) {
            e.preventDefault();
            
            const id = document.getElementById('denuncia-id').value;
            const fecha = document.getElementById('fecha-denuncia').value;
            const tipo = document.getElementById('tipo-denuncia').value;
            const descripcion = document.getElementById('descripcion-denuncia').value;
            const estado = document.getElementById('estado-denuncia').value;
            const vehiculoMatricula = document.getElementById('vehiculo-matricula').value;
            
            const denunciaData = {
                fecha_denuncia: fecha,
                tipo_denuncia: tipo,
                descripcion: descripcion,
                estado: estado,
                vehiculo_matricula: vehiculoMatricula
            };
            
            const url = id ? `?action=update_denuncia&id=${id}` : '?action=add_denuncia';
            const method = id ? 'PUT' : 'POST';
            
            fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(denunciaData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    throw new Error(data.error);
                }
                
                mostrarNotificacion(data.message, 'success');
                cerrarModal();
                cargarDenunciasVehiculo(vehiculoMatricula);
            })
            .catch(error => {
                console.error('Error al guardar denuncia:', error);
                mostrarNotificacion(error.message, 'error');
            });
        }

        function solicitarEliminarDenuncia(id) {
            denunciaAEliminar = id;
            document.getElementById('modal-confirmacion').style.display = 'flex';
        }

        function cerrarConfirmacion() {
            document.getElementById('modal-confirmacion').style.display = 'none';
            denunciaAEliminar = null;
        }

        function eliminarDenuncia() {
            if (denunciaAEliminar) {
                fetch(`?action=delete_denuncia&id=${denunciaAEliminar}`, {
                    method: 'DELETE'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        throw new Error(data.error);
                    }
                    
                    mostrarNotificacion(data.message, 'success');
                    cerrarConfirmacion();
                    cargarDenunciasVehiculo(vehiculoActual);
                })
                .catch(error => {
                    console.error('Error al eliminar denuncia:', error);
                    mostrarNotificacion(error.message, 'error');
                });
            }
        }

        // Funciones auxiliares
        function formatearFecha(fechaStr) {
            const opciones = { year: 'numeric', month: 'long', day: 'numeric' };
            return new Date(fechaStr).toLocaleDateString('es-ES', opciones);
        }

        function mostrarNotificacion(mensaje, tipo) {
            const notification = document.createElement('div');
            notification.className = `notification ${tipo}`;
            notification.textContent = mensaje;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.remove();
            }, 5000);
        }

        // Event Listeners
        document.addEventListener('DOMContentLoaded', function() {
            // Configurar eventos de denuncias
            document.getElementById('agregar-denuncia').addEventListener('click', mostrarModalAgregar);
            document.getElementById('cerrar-modal').addEventListener('click', cerrarModal);
            document.getElementById('cancelar-denuncia').addEventListener('click', cerrarModal);
            document.getElementById('form-denuncia').addEventListener('submit', guardarDenuncia);
            document.getElementById('cerrar-confirmacion').addEventListener('click', cerrarConfirmacion);
            document.getElementById('cancelar-eliminar').addEventListener('click', cerrarConfirmacion);
            document.getElementById('confirmar-eliminar').addEventListener('click', eliminarDenuncia);
            
            // Cerrar modal al hacer clic fuera del contenido
            document.getElementById('modal-denuncia').addEventListener('click', function(e) {
                if (e.target === this) cerrarModal();
            });
            
            document.getElementById('modal-confirmacion').addEventListener('click', function(e) {
                if (e.target === this) cerrarConfirmacion();
            });
        });

        // ... (el resto del código JavaScript original se mantiene igual)
    </script>
</body>
</html>