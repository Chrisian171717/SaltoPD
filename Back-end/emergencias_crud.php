<?php
// Back-end/emergencias_crud.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include("conexion.php");

session_start();
if (!isset($_SESSION['usuario'])) {
    $_SESSION['usuario'] = 'Operador Radio';
}

$mensaje = '';

// Crear emergencia
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_emergencia'])) {
    $codigo = $conn->real_escape_string($_POST['codigo']);
    $descripcion = $conn->real_escape_string($_POST['descripcion']);
    $ubicacion = $conn->real_escape_string($_POST['ubicacion']);
    $unidades_asignadas = $conn->real_escape_string($_POST['unidades_asignadas']);
    
    $sql = "INSERT INTO emergencias (codigo, descripcion, ubicacion, unidades_asignadas, activa) 
            VALUES ('$codigo', '$descripcion', '$ubicacion', '$unidades_asignadas', 1)";
    
    if ($conn->query($sql)) {
        $emergencia_id = $conn->insert_id;
        registrarActividad($conn, $_SESSION['usuario'], "Creó emergencia: $codigo", "emergencias", $emergencia_id);
        $mensaje = "✅ Emergencia creada exitosamente";
    } else {
        $mensaje = "❌ Error al crear emergencia: " . $conn->error;
    }
}

// Archivar emergencia
if (isset($_GET['archivar'])) {
    $id = intval($_GET['archivar']);
    $sql = "UPDATE emergencias SET activa = 0 WHERE id = $id";
    
    if ($conn->query($sql)) {
        registrarActividad($conn, $_SESSION['usuario'], "Archivó emergencia ID: $id", "emergencias", $id);
        $mensaje = "✅ Emergencia archivada";
    } else {
        $mensaje = "❌ Error al archivar emergencia";
    }
}

// Reactivar emergencia
if (isset($_GET['reactivar'])) {
    $id = intval($_GET['reactivar']);
    $sql = "UPDATE emergencias SET activa = 1 WHERE id = $id";
    
    if ($conn->query($sql)) {
        registrarActividad($conn, $_SESSION['usuario'], "Reactivó emergencia ID: $id", "emergencias", $id);
        $mensaje = "✅ Emergencia reactivada";
    } else {
        $mensaje = "❌ Error al reactivar emergencia";
    }
}

// Eliminar emergencia
if (isset($_GET['eliminar'])) {
    $id = intval($_GET['eliminar']);
    $sql = "DELETE FROM emergencias WHERE id = $id";
    
    if ($conn->query($sql)) {
        registrarActividad($conn, $_SESSION['usuario'], "Eliminó emergencia ID: $id", "emergencias", $id);
        $mensaje = "✅ Emergencia eliminada";
    } else {
        $mensaje = "❌ Error al eliminar emergencia";
    }
}

// Obtener todas las emergencias
$emergencias = array();
$resultEmerg = $conn->query("SELECT * FROM emergencias ORDER BY activa DESC, fecha DESC");
if ($resultEmerg && $resultEmerg->num_rows > 0) {
    while ($row = $resultEmerg->fetch_assoc()) {
        $emergencias[] = $row;
    }
}

// Obtener unidades disponibles para asignar
$unidades_disponibles = array();
$resultUnidades = $conn->query("SELECT codigo FROM unidades WHERE estado = 'disponible'");
if ($resultUnidades && $resultUnidades->num_rows > 0) {
    while ($row = $resultUnidades->fetch_assoc()) {
        $unidades_disponibles[] = $row['codigo'];
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Emergencias</title>
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
        .emergency-management {
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
            border-bottom: 3px solid #e74c3c;
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
        .form-group input, .form-group textarea, .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ced4da;
            border-radius: 6px;
            font-size: 16px;
        }
        .form-group input:focus, .form-group textarea:focus, .form-group select:focus {
            outline: none;
            border-color: #e74c3c;
            box-shadow: 0 0 5px rgba(231, 76, 60, 0.3);
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
        .btn-primary {
            background: #3498db;
            color: white;
        }
        .btn-primary:hover {
            background: #2980b9;
        }
        .btn-danger {
            background: #e74c3c;
            color: white;
        }
        .btn-danger:hover {
            background: #c0392b;
        }
        .btn-warning {
            background: #f39c12;
            color: white;
        }
        .btn-warning:hover {
            background: #d35400;
        }
        .btn-success {
            background: #27ae60;
            color: white;
        }
        .btn-success:hover {
            background: #219a52;
        }
        .btn-secondary {
            background: #95a5a6;
            color: white;
        }
        .btn-secondary:hover {
            background: #7f8c8d;
        }
        .emergency-list {
            margin-top: 30px;
        }
        .emergency-card {
            background: white;
            padding: 20px;
            margin: 15px 0;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-left: 5px solid #e74c3c;
            transition: transform 0.3s ease;
        }
        .emergency-card:hover {
            transform: translateY(-2px);
        }
        .emergency-card.archived {
            border-left-color: #95a5a6;
            opacity: 0.8;
            background: #f8f9fa;
        }
        .emergency-card h3 {
            color: #2c3e50;
            margin-bottom: 15px;
            font-size: 1.3em;
        }
        .emergency-card p {
            margin: 8px 0;
            color: #555;
        }
        .emergency-actions {
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
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            margin-left: 10px;
        }
        .status-active {
            background: #e74c3c;
            color: white;
        }
        .status-archived {
            background: #95a5a6;
            color: white;
        }
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="emergency-management">
        <h1>🚨 Gestión de Emergencias</h1>
        
        <?php if ($mensaje): ?>
            <div class="alert <?= strpos($mensaje, '✅') !== false ? 'alert-success' : 'alert-danger' ?>">
                <?= $mensaje ?>
            </div>
        <?php endif; ?>

        <!-- Formulario para crear emergencia -->
        <div class="form-container">
            <h2>➕ Crear Nueva Emergencia</h2>
            <form method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label>Código de Emergencia:</label>
                        <select name="codigo" required>
                            <option value="">Seleccionar código...</option>
                            <option value="10-13">10-13 - Oficial necesita ayuda</option>
                            <option value="10-54">10-54 - Accidente de tráfico</option>
                            <option value="10-56">10-56 - Persona intoxicada</option>
                            <option value="10-80">10-80 - Persecución en curso</option>
                            <option value="10-99">10-99 - Oficial en peligro</option>
                            <option value="Code 3">Code 3 - Máxima prioridad</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Ubicación:</label>
                        <input type="text" name="ubicacion" placeholder="Ej: Av. Principal con Calle 8" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Descripción:</label>
                    <textarea name="descripcion" rows="3" placeholder="Descripción detallada de la emergencia..." required></textarea>
                </div>

                <div class="form-group">
                    <label>Unidades Asignadas:</label>
                    <input type="text" name="unidades_asignadas" 
                           placeholder="Ej: Zeta-01, Zeta-02" 
                           value="<?= implode(', ', $unidades_disponibles) ?>"
                           required>
                    <small style="color: #666;">Unidades disponibles: <?= implode(', ', $unidades_disponibles) ?></small>
                </div>
                
                <button type="submit" name="crear_emergencia" class="btn btn-danger">🚨 Crear Emergencia</button>
            </form>
        </div>

        <!-- Lista de emergencias -->
        <div class="emergency-list">
            <h2>📋 Lista de Emergencias (<?= count($emergencias) ?>)</h2>
            
            <?php if (count($emergencias) > 0): ?>
                <?php foreach($emergencias as $e): ?>
                    <div class="emergency-card <?= $e['activa'] ? '' : 'archived' ?>">
                        <h3>
                            ⚠️ <?= htmlspecialchars($e['codigo']) ?> 
                            <span class="status-badge <?= $e['activa'] ? 'status-active' : 'status-archived' ?>">
                                <?= $e['activa'] ? 'ACTIVA' : 'ARCHIVADA' ?>
                            </span>
                        </h3>
                        <p><strong>📝 Descripción:</strong> <?= htmlspecialchars($e['descripcion']) ?></p>
                        <p><strong>📍 Ubicación:</strong> <?= htmlspecialchars($e['ubicacion']) ?></p>
                        <p><strong>🚔 Unidades:</strong> <?= htmlspecialchars($e['unidades_asignadas']) ?></p>
                        <p><strong>🕐 Fecha:</strong> <?= $e['fecha'] ?></p>
                        
                        <div class="emergency-actions">
                            <?php if ($e['activa']): ?>
                                <a href="?archivar=<?= $e['id'] ?>" class="btn btn-warning">📁 Archivar</a>
                            <?php else: ?>
                                <a href="?reactivar=<?= $e['id'] ?>" class="btn btn-success">↻ Reactivar</a>
                            <?php endif; ?>
                            <a href="emergencias_editar.php?id=<?= $e['id'] ?>" class="btn btn-primary">✏️ Editar</a>
                            <a href="?eliminar=<?= $e['id'] ?>" class="btn btn-danger" 
                               onclick="return confirm('¿Estás seguro de eliminar esta emergencia?')">
                               🗑️ Eliminar
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="text-align: center; padding: 40px; background: #f8f9fa; border-radius: 8px;">
                    <h3>No hay emergencias registradas</h3>
                    <p>Usa el formulario superior para crear la primera emergencia.</p>
                </div>
            <?php endif; ?>
        </div>

        <div style="margin-top: 30px; text-align: center;">
            <a href="Radio.php" class="btn btn-success">← Volver al Panel de Radio</a>
            <a href="unidades_crud.php" class="btn btn-primary">🚔 Gestionar Unidades</a>
            <a href="registro_actividades.php" class="btn btn-secondary">📊 Ver Registros</a>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-focus en el primer campo
            const firstInput = document.querySelector('form input, form select, form textarea');
            if (firstInput) firstInput.focus();
            
            // Ejemplo de actualización automática cada 30 segundos
            setInterval(() => {
                window.location.reload();
            }, 30000); // 30 segundos
        });
    </script>
</body>
</html>