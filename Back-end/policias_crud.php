<?php
// Back-end/policias_crud.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include("conexion.php");

session_start();
if (!isset($_SESSION['usuario'])) {
    $_SESSION['usuario'] = 'Operador Radio';
}

$mensaje = '';

// Crear/Editar policía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $conn->real_escape_string($_POST['Nombre_P']);
    $apellido = $conn->real_escape_string($_POST['Apellido_P']);
    $cedula = $conn->real_escape_string($_POST['Cedula_P']);
    $num_placa = $conn->real_escape_string($_POST['Num_Placa']);
    $rango = $conn->real_escape_string($_POST['Rango']);
    $estado_civil = $conn->real_escape_string($_POST['EstadoCivil']);
    
    if (isset($_POST['edit_mode']) && $_POST['edit_mode'] == 'true') {
        // Editar policía existente
        $placa_original = $conn->real_escape_string($_POST['placa_original']);
        $sql = "UPDATE policias SET Nombre_P='$nombre', Apellido_P='$apellido', Cedula_P='$cedula', 
                Num_Placa='$num_placa', Rango='$rango', EstadoCivil='$estado_civil' 
                WHERE Num_Placa='$placa_original'";
        $accion = "editó";
    } else {
        // Crear nuevo policía
        $sql = "INSERT INTO policias (Nombre_P, Apellido_P, Cedula_P, Num_Placa, Rango, EstadoCivil) 
                VALUES ('$nombre', '$apellido', '$cedula', '$num_placa', '$rango', '$estado_civil')";
        $accion = "creó";
    }
    
    if ($conn->query($sql)) {
        registrarActividad($conn, $_SESSION['usuario'], "$accion policía: $nombre $apellido", "policias", $num_placa);
        $mensaje = "✅ Policía guardado exitosamente";
    } else {
        $mensaje = "❌ Error: " . $conn->error;
    }
}

// Eliminar policía
if (isset($_GET['eliminar'])) {
    $num_placa = intval($_GET['eliminar']);
    
    // Verificar si el policía está asignado a alguna unidad
    $check = $conn->query("SELECT COUNT(*) as total FROM unidad_policias WHERE num_placa = $num_placa");
    $row = $check->fetch_assoc();
    
    if ($row['total'] > 0) {
        $mensaje = "❌ No se puede eliminar: El policía está asignado a una unidad";
    } else {
        $sql = "DELETE FROM policias WHERE Num_Placa = $num_placa";
        if ($conn->query($sql)) {
            registrarActividad($conn, $_SESSION['usuario'], "eliminó policía placa: $num_placa", "policias", $num_placa);
            $mensaje = "✅ Policía eliminado";
        } else {
            $mensaje = "❌ Error al eliminar policía: " . $conn->error;
        }
    }
}

// Obtener todos los policías
$policias = array();
$result = $conn->query("SELECT * FROM policias ORDER BY Rango, Nombre_P, Apellido_P");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $policias[] = $row;
    }
}

// Obtener policía para editar
$policia_editar = null;
if (isset($_GET['editar'])) {
    $placa_editar = intval($_GET['editar']);
    $result = $conn->query("SELECT * FROM policias WHERE Num_Placa = $placa_editar");
    if ($result && $result->num_rows > 0) {
        $policia_editar = $result->fetch_assoc();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Policías</title>
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
        .policias-management {
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
        .policias-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }
        .policia-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-left: 5px solid #3498db;
        }
        .policia-card h3 {
            color: #2c3e50;
            margin-bottom: 15px;
        }
        .policia-actions {
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
        .rango-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            margin-left: 10px;
        }
        .rango-oficial { background: #3498db; color: white; }
        .rango-sargento { background: #9b59b6; color: white; }
        .rango-cabo { background: #e67e22; color: white; }
        .rango-teniente { background: #e74c3c; color: white; }
        .rango-capitan { background: #27ae60; color: white; }
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="policias-management">
        <h1>👮 Gestión de Policías</h1>
        
        <?php if ($mensaje): ?>
            <div class="alert <?= strpos($mensaje, '✅') !== false ? 'alert-success' : 'alert-danger' ?>">
                <?= $mensaje ?>
            </div>
        <?php endif; ?>

        <!-- Formulario para crear/editar policía -->
        <div class="form-container">
            <h2><?= $policia_editar ? '✏️ Editar Policía' : '➕ Crear Nuevo Policía' ?></h2>
            <form method="POST">
                <?php if ($policia_editar): ?>
                    <input type="hidden" name="edit_mode" value="true">
                    <input type="hidden" name="placa_original" value="<?= $policia_editar['Num_Placa'] ?>">
                <?php endif; ?>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Nombre:</label>
                        <input type="text" name="Nombre_P" value="<?= $policia_editar ? htmlspecialchars($policia_editar['Nombre_P']) : '' ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Apellido:</label>
                        <input type="text" name="Apellido_P" value="<?= $policia_editar ? htmlspecialchars($policia_editar['Apellido_P']) : '' ?>" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Cédula:</label>
                        <input type="number" name="Cedula_P" value="<?= $policia_editar ? htmlspecialchars($policia_editar['Cedula_P']) : '' ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Número de Placa:</label>
                        <input type="number" name="Num_Placa" value="<?= $policia_editar ? htmlspecialchars($policia_editar['Num_Placa']) : '' ?>" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Rango:</label>
                        <select name="Rango" required>
                            <option value="Oficial" <?= $policia_editar && $policia_editar['Rango']=='Oficial' ? 'selected' : '' ?>>👮 Oficial</option>
                            <option value="Cabo" <?= $policia_editar && $policia_editar['Rango']=='Cabo' ? 'selected' : '' ?>>🔰 Cabo</option>
                            <option value="Sargento" <?= $policia_editar && $policia_editar['Rango']=='Sargento' ? 'selected' : '' ?>>⭐ Sargento</option>
                            <option value="Teniente" <?= $policia_editar && $policia_editar['Rango']=='Teniente' ? 'selected' : '' ?>>🎖️ Teniente</option>
                            <option value="Capitán" <?= $policia_editar && $policia_editar['Rango']=='Capitán' ? 'selected' : '' ?>>👑 Capitán</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Estado Civil:</label>
                        <select name="EstadoCivil" required>
                            <option value="Soltero" <?= $policia_editar && $policia_editar['EstadoCivil']=='Soltero' ? 'selected' : '' ?>>💍 Soltero</option>
                            <option value="Casado" <?= $policia_editar && $policia_editar['EstadoCivil']=='Casado' ? 'selected' : '' ?>>💑 Casado</option>
                            <option value="Divorciado" <?= $policia_editar && $policia_editar['EstadoCivil']=='Divorciado' ? 'selected' : '' ?>>💔 Divorciado</option>
                            <option value="Viudo" <?= $policia_editar && $policia_editar['EstadoCivil']=='Viudo' ? 'selected' : '' ?>>⚰️ Viudo</option>
                        </select>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <?= $policia_editar ? '💾 Guardar Cambios' : '➕ Crear Policía' ?>
                </button>
                <?php if ($policia_editar): ?>
                    <a href="policias_crud.php" class="btn btn-warning">❌ Cancelar Edición</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Lista de policías -->
        <h2>📋 Policías Registrados (<?= count($policias) ?>)</h2>
        
        <?php if (count($policias) > 0): ?>
            <div class="policias-grid">
                <?php foreach($policias as $p): ?>
                    <div class="policia-card">
                        <h3>
                            👤 <?= htmlspecialchars($p['Nombre_P']) ?> <?= htmlspecialchars($p['Apellido_P']) ?>
                            <span class="rango-badge rango-<?= strtolower($p['Rango']) ?>">
                                <?= $p['Rango'] ?>
                            </span>
                        </h3>
                        <p><strong>🆔 Cédula:</strong> <?= htmlspecialchars($p['Cedula_P']) ?></p>
                        <p><strong>🔢 Placa:</strong> <?= htmlspecialchars($p['Num_Placa']) ?></p>
                        <p><strong>💑 Estado Civil:</strong> <?= htmlspecialchars($p['EstadoCivil']) ?></p>
                        
                        <div class="policia-actions">
                            <a href="?editar=<?= $p['Num_Placa'] ?>" class="btn btn-primary">✏️ Editar</a>
                            <a href="?eliminar=<?= $p['Num_Placa'] ?>" class="btn btn-danger" 
                               onclick="return confirm('¿Estás seguro de eliminar a <?= htmlspecialchars($p['Nombre_P'] . ' ' . $p['Apellido_P']) ?>?')">
                               🗑️ Eliminar
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 40px; background: #f8f9fa; border-radius: 8px;">
                <h3>No hay policías registrados</h3>
                <p>Comienza creando el primer policía usando el formulario superior.</p>
            </div>
        <?php endif; ?>

        <div style="margin-top: 30px; text-align: center;">
            <a href="Radio.php" class="btn btn-success">← Volver al Panel de Radio</a>
            <a href="unidades_crud.php" class="btn btn-primary">🚔 Gestionar Unidades</a>
            <a href="emergencias_crud.php" class="btn btn-primary">🚨 Gestionar Emergencias</a>
        </div>
    </div>
</body>
</html>