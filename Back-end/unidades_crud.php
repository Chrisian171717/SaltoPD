<?php
// Back-end/unidades_crud.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include("conexion.php");

session_start();
if (!isset($_SESSION['usuario'])) {
    $_SESSION['usuario'] = 'Operador Radio';
}

$mensaje = '';

// Procesar formulario de crear/editar unidad
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo = $conn->real_escape_string($_POST['codigo']);
    $tipo = $conn->real_escape_string($_POST['tipo']);
    $estado = $conn->real_escape_string($_POST['estado']);
    $sector = $conn->real_escape_string($_POST['sector']);
    $frecuencia = $conn->real_escape_string($_POST['frecuencia']);
    $policias_seleccionados = $_POST['policias'] ?? [];
    $conductores = $_POST['conductor'] ?? [];
    
    // Validar que se hayan seleccionado al menos 2 policías
    if (count($policias_seleccionados) < 2) {
        $mensaje = "❌ Error: Se requieren al menos 2 policías por unidad";
    } else {
        // Iniciar transacción
        $conn->begin_transaction();
        
        try {
            if (isset($_POST['id']) && !empty($_POST['id'])) {
                // Editar unidad existente
                $id = intval($_POST['id']);
                $sql = "UPDATE unidades SET codigo='$codigo', tipo='$tipo', estado='$estado', 
                        sector='$sector', frecuencia='$frecuencia' WHERE id=$id";
                $accion = "editó";
            } else {
                // Crear nueva unidad
                $sql = "INSERT INTO unidades (codigo, tipo, estado, sector, frecuencia) 
                        VALUES ('$codigo', '$tipo', '$estado', '$sector', '$frecuencia')";
                $accion = "creó";
            }
            
            if ($conn->query($sql)) {
                $unidad_id = isset($_POST['id']) ? $_POST['id'] : $conn->insert_id;
                
                // Eliminar asignaciones anteriores (si es edición)
                $conn->query("DELETE FROM unidad_policias WHERE unidad_id = $unidad_id");
                
                // Asignar nuevos policías
                foreach ($policias_seleccionados as $num_placa) {
                    $es_conductor = in_array($num_placa, $conductores) ? 1 : 0;
                    $sql_asignacion = "INSERT INTO unidad_policias (unidad_id, num_placa, es_conductor) 
                                      VALUES ($unidad_id, $num_placa, $es_conductor)";
                    $conn->query($sql_asignacion);
                }
                
                $conn->commit();
                registrarActividad($conn, $_SESSION['usuario'], "$accion unidad: $codigo", "unidades", $unidad_id);
                $mensaje = "✅ Unidad guardada exitosamente con " . count($policias_seleccionados) . " policías";
            } else {
                throw new Exception($conn->error);
            }
        } catch (Exception $e) {
            $conn->rollback();
            $mensaje = "❌ Error: " . $e->getMessage();
        }
    }
}

// Eliminar unidad
if (isset($_GET['eliminar'])) {
    $id = intval($_GET['eliminar']);
    $sql = "DELETE FROM unidades WHERE id = $id";
    
    if ($conn->query($sql)) {
        registrarActividad($conn, $_SESSION['usuario'], "eliminó unidad ID: $id", "unidades", $id);
        $mensaje = "✅ Unidad eliminada";
    } else {
        $mensaje = "❌ Error al eliminar unidad: " . $conn->error;
    }
}

// Obtener todas las unidades con sus policías
$unidades = array();
$result = $conn->query("
    SELECT u.*, 
           GROUP_CONCAT(CONCAT(p.Nombre_P, ' ', p.Apellido_P, ' (', p.Rango, ')') SEPARATOR ', ') as policias_asignados,
           COUNT(up.num_placa) as total_policias
    FROM unidades u
    LEFT JOIN unidad_policias up ON u.id = up.unidad_id
    LEFT JOIN policias p ON up.num_placa = p.Num_Placa
    GROUP BY u.id
    ORDER BY u.codigo
");

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $unidades[] = $row;
    }
}

// Obtener unidad para editar
$unidad_editar = null;
$policias_asignados = [];
if (isset($_GET['editar'])) {
    $id_editar = intval($_GET['editar']);
    $result = $conn->query("SELECT * FROM unidades WHERE id = $id_editar");
    if ($result && $result->num_rows > 0) {
        $unidad_editar = $result->fetch_assoc();
        
        // Obtener policías asignados a esta unidad
        $result_policias = $conn->query("
            SELECT p.Num_Placa, p.Nombre_P, p.Apellido_P, p.Rango, up.es_conductor 
            FROM unidad_policias up 
            JOIN policias p ON up.num_placa = p.Num_Placa 
            WHERE up.unidad_id = $id_editar
        ");
        if ($result_policias && $result_policias->num_rows > 0) {
            while ($row = $result_policias->fetch_assoc()) {
                $policias_asignados[] = $row;
            }
        }
    }
}

// Obtener todos los policías disponibles
$policias_disponibles = array();
$result_policias = $conn->query("SELECT * FROM policias ORDER BY Rango, Nombre_P, Apellido_P");
if ($result_policias && $result_policias->num_rows > 0) {
    while ($row = $result_policias->fetch_assoc()) {
        $policias_disponibles[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Unidades</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        .units-management {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2c3e50;
            margin-bottom: 20px;
            border-bottom: 3px solid #3498db;
            padding-bottom: 10px;
        }
        .form-container {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 8px;
            margin-bottom: 30px;
            border: 1px solid #e9ecef;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #495057;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ced4da;
            border-radius: 6px;
            font-size: 16px;
        }
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            margin: 5px;
            font-size: 16px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }
        .btn-primary { background: #3498db; color: white; }
        .btn-danger { background: #e74c3c; color: white; }
        .btn-warning { background: #f39c12; color: white; }
        .btn-success { background: #27ae60; color: white; }
        .units-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }
        .unit-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-left: 5px solid #27ae60;
        }
        .unit-card.ocupado { border-left-color: #f39c12; }
        .unit-card.fuera_servicio { border-left-color: #e74c3c; }
        .unit-card h3 {
            color: #2c3e50;
            margin-bottom: 15px;
            font-size: 1.3em;
        }
        .unit-actions {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }
        .alert {
            padding: 15px;
            margin: 20px 0;
            border-radius: 6px;
            font-weight: bold;
        }
        .alert-success { background: #d4edda; color: #155724; }
        .alert-danger { background: #f8d7da; color: #721c24; }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        .policias-selection {
            background: white;
            padding: 15px;
            border-radius: 6px;
            border: 1px solid #ced4da;
            max-height: 300px;
            overflow-y: auto;
        }
        .policia-option {
            display: flex;
            align-items: center;
            padding: 10px;
            border-bottom: 1px solid #f0f0f0;
        }
        .policia-option:last-child {
            border-bottom: none;
        }
        .policia-option input[type="checkbox"] {
            margin-right: 10px;
            transform: scale(1.2);
        }
        .policia-info {
            flex: 1;
        }
        .conductor-checkbox {
            margin-left: 15px;
        }
        .conductor-checkbox label {
            display: flex;
            align-items: center;
            font-size: 0.9em;
            color: #666;
        }
        .min-policias-warning {
            color: #e74c3c;
            font-size: 0.9em;
            margin-top: 5px;
        }
        .policias-count {
            font-weight: bold;
            color: #3498db;
        }
    </style>
</head>
<body>
    <div class="units-management">
        <h1>🚔 Gestión de Unidades Policiales</h1>
        
        <?php if ($mensaje): ?>
            <div class="alert <?= strpos($mensaje, '✅') !== false ? 'alert-success' : 'alert-danger' ?>">
                <?= $mensaje ?>
            </div>
        <?php endif; ?>

        <!-- Formulario para crear/editar unidad -->
        <div class="form-container">
            <h2><?= $unidad_editar ? '✏️ Editar Unidad' : '➕ Crear Nueva Unidad' ?></h2>
            <form method="POST">
                <?php if ($unidad_editar): ?>
                    <input type="hidden" name="id" value="<?= $unidad_editar['id'] ?>">
                <?php endif; ?>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Código de Unidad:</label>
                        <input type="text" name="codigo" value="<?= $unidad_editar ? htmlspecialchars($unidad_editar['codigo']) : '' ?>" 
                               placeholder="Ej: Zeta-01" required>
                    </div>
                    <div class="form-group">
                        <label>Tipo de Unidad:</label>
                        <select name="tipo" required>
                            <option value="moto" <?= $unidad_editar && $unidad_editar['tipo']=='moto' ? 'selected' : '' ?>>🏍️ Moto Policial</option>
                            <option value="auto" <?= $unidad_editar && $unidad_editar['tipo']=='auto' ? 'selected' : '' ?>>🚗 Auto Patrulla</option>
                            <option value="camioneta" <?= $unidad_editar && $unidad_editar['tipo']=='camioneta' ? 'selected' : '' ?>>🚙 Camioneta</option>
                            <option value="helicoptero" <?= $unidad_editar && $unidad_editar['tipo']=='helicoptero' ? 'selected' : '' ?>>🚁 Helicóptero</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Estado:</label>
                        <select name="estado" required>
                            <option value="disponible" <?= $unidad_editar && $unidad_editar['estado']=='disponible' ? 'selected' : '' ?>>✅ Disponible</option>
                            <option value="ocupado" <?= $unidad_editar && $unidad_editar['estado']=='ocupado' ? 'selected' : '' ?>>🟡 Ocupado</option>
                            <option value="fuera_servicio" <?= $unidad_editar && $unidad_editar['estado']=='fuera_servicio' ? 'selected' : '' ?>>🔴 Fuera de Servicio</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Frecuencia (MHz):</label>
                        <input type="text" name="frecuencia" value="<?= $unidad_editar ? htmlspecialchars($unidad_editar['frecuencia']) : '460.125' ?>" 
                               placeholder="460.125" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Sector de Patrulla:</label>
                    <input type="text" name="sector" value="<?= $unidad_editar ? htmlspecialchars($unidad_editar['sector']) : '' ?>" 
                           placeholder="Ej: Centro Norte" required>
                </div>

                <!-- Selección de policías -->
                <div class="form-group">
                    <label>
                        👮 Policías Asignados 
                        <span class="policias-count" id="policiasCount">0</span>/<span id="minPolicias">2</span> seleccionados
                    </label>
                    <div class="min-policias-warning">* Mínimo 2 policías por unidad</div>
                    <div class="policias-selection">
                        <?php if (count($policias_disponibles) > 0): ?>
                            <?php foreach($policias_disponibles as $p): ?>
                                <div class="policia-option">
                                    <input type="checkbox" name="policias[]" value="<?= $p['Num_Placa'] ?>" 
                                           id="policia_<?= $p['Num_Placa'] ?>"
                                           <?= in_array($p['Num_Placa'], array_column($policias_asignados, 'Num_Placa')) ? 'checked' : '' ?>
                                           onchange="updatePoliciasCount()">
                                    <div class="policia-info">
                                        <label for="policia_<?= $p['Num_Placa'] ?>">
                                            <strong><?= htmlspecialchars($p['Nombre_P'] . ' ' . $p['Apellido_P']) ?></strong> 
                                            (<?= htmlspecialchars($p['Rango']) ?>)
                                            <br>
                                            <small>Placa: <?= htmlspecialchars($p['Num_Placa']) ?> | Cédula: <?= htmlspecialchars($p['Cedula_P']) ?></small>
                                        </label>
                                    </div>
                                    <div class="conductor-checkbox">
                                        <label>
                                            <input type="checkbox" name="conductor[]" value="<?= $p['Num_Placa'] ?>"
                                                   <?= in_array($p['Num_Placa'], array_column(array_filter($policias_asignados, function($pa) { 
                                                       return $pa['es_conductor']; 
                                                   }), 'Num_Placa')) ? 'checked' : '' ?>>
                                            Conductor
                                        </label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>No hay policías disponibles. <a href="policias_crud.php">Crear policías primero</a></p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary" id="submitBtn">
                    <?= $unidad_editar ? '💾 Guardar Cambios' : '➕ Crear Unidad' ?>
                </button>
                <?php if ($unidad_editar): ?>
                    <a href="unidades_crud.php" class="btn btn-warning">❌ Cancelar Edición</a>
                <?php endif; ?>
                <a href="policias_crud.php" class="btn btn-success">👮 Gestionar Policías</a>
            </form>
        </div>

        <!-- Lista de unidades -->
        <h2>📋 Unidades Registradas (<?= count($unidades) ?>)</h2>
        
        <?php if (count($unidades) > 0): ?>
            <div class="units-grid">
                <?php foreach($unidades as $u): ?>
                    <div class="unit-card <?= htmlspecialchars($u['estado']) ?>">
                        <?php
                        $iconos = [
                            'moto' => '🏍️',
                            'auto' => '🚗', 
                            'camioneta' => '🚙',
                            'helicoptero' => '🚁'
                        ];
                        $icono = $iconos[$u['tipo']] ?? '🚔';
                        ?>
                        <h3><?= $icono ?> <?= htmlspecialchars($u['codigo']) ?></h3>
                        <p><strong>Estado:</strong> 
                            <?php
                            $estados = [
                                'disponible' => '✅ Disponible',
                                'ocupado' => '🟡 Ocupado', 
                                'fuera_servicio' => '🔴 Fuera de Servicio'
                            ];
                            echo $estados[$u['estado']] ?? ucfirst($u['estado']);
                            ?>
                        </p>
                        <p><strong>👮 Policías:</strong> <?= $u['policias_asignados'] ? htmlspecialchars($u['policias_asignados']) : 'Sin asignar' ?></p>
                        <p><strong>Total policías:</strong> <?= $u['total_policias'] ?></p>
                        <p><strong>Sector:</strong> <?= htmlspecialchars($u['sector']) ?></p>
                        <p><strong>Frecuencia:</strong> <?= htmlspecialchars($u['frecuencia']) ?> MHz</p>
                        
                        <div class="unit-actions">
                            <a href="?editar=<?= $u['id'] ?>" class="btn btn-primary">✏️ Editar</a>
                            <a href="?eliminar=<?= $u['id'] ?>" class="btn btn-danger" 
                               onclick="return confirm('¿Estás seguro de eliminar la unidad <?= htmlspecialchars($u['codigo']) ?>?')">
                               🗑️ Eliminar
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 40px; background: #f8f9fa; border-radius: 8px;">
                <h3>No hay unidades registradas</h3>
                <p>Comienza creando la primera unidad usando el formulario superior.</p>
            </div>
        <?php endif; ?>

        <div style="margin-top: 30px; text-align: center;">
            <a href="Radio.php" class="btn btn-success">← Volver al Panel de Radio</a>
            <a href="emergencias_crud.php" class="btn btn-primary">🚨 Gestionar Emergencias</a>
            <a href="policias_crud.php" class="btn btn-primary">👮 Gestionar Policías</a>
        </div>
    </div>

    <script>
        function updatePoliciasCount() {
            const checkboxes = document.querySelectorAll('input[name="policias[]"]:checked');
            const count = checkboxes.length;
            const minPolicias = 2;
            
            document.getElementById('policiasCount').textContent = count;
            document.getElementById('policiasCount').style.color = count >= minPolicias ? '#27ae60' : '#e74c3c';
            
            // Habilitar/deshabilitar botón de enviar
            const submitBtn = document.getElementById('submitBtn');
            if (count >= minPolicias) {
                submitBtn.disabled = false;
                submitBtn.style.opacity = '1';
            } else {
                submitBtn.disabled = true;
                submitBtn.style.opacity = '0.6';
            }
        }

        // Inicializar contador al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            updatePoliciasCount();
            
            // Auto-focus en el primer campo del formulario
            const firstInput = document.querySelector('form input, form select');
            if (firstInput) firstInput.focus();
        });
    </script>
</body>
</html>