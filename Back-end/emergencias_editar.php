<?php
// Back-end/emergencias_editar.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include("conexion.php");

session_start();
if (!isset($_SESSION['usuario'])) {
    $_SESSION['usuario'] = 'Operador Radio';
}

$mensaje = '';
$emergencia = null;

// Obtener emergencia a editar
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $result = $conn->query("SELECT * FROM emergencias WHERE id = $id");
    if ($result && $result->num_rows > 0) {
        $emergencia = $result->fetch_assoc();
    }
}

if (!$emergencia) {
    die("<script>alert('Emergencia no encontrada'); window.location.href='emergencias_crud.php';</script>");
}

// Actualizar emergencia
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_emergencia'])) {
    $codigo = $conn->real_escape_string($_POST['codigo']);
    $descripcion = $conn->real_escape_string($_POST['descripcion']);
    $ubicacion = $conn->real_escape_string($_POST['ubicacion']);
    $unidades_asignadas = $conn->real_escape_string($_POST['unidades_asignadas']);
    $activa = isset($_POST['activa']) ? 1 : 0;
    
    $sql = "UPDATE emergencias SET 
            codigo = '$codigo', 
            descripcion = '$descripcion', 
            ubicacion = '$ubicacion', 
            unidades_asignadas = '$unidades_asignadas',
            activa = $activa 
            WHERE id = $id";
    
    if ($conn->query($sql)) {
        registrarActividad($conn, $_SESSION['usuario'], "Editó emergencia: $codigo", "emergencias", $id);
        $mensaje = "✅ Emergencia actualizada exitosamente";
        // Recargar datos
        $result = $conn->query("SELECT * FROM emergencias WHERE id = $id");
        $emergencia = $result->fetch_assoc();
    } else {
        $mensaje = "❌ Error al actualizar emergencia: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Emergencia</title>
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
        .edit-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2c3e50;
            margin-bottom: 20px;
            border-bottom: 3px solid #f39c12;
            padding-bottom: 10px;
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
            border-color: #f39c12;
            box-shadow: 0 0 5px rgba(243, 156, 18, 0.3);
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
        .btn-secondary {
            background: #95a5a6;
            color: white;
        }
        .btn-secondary:hover {
            background: #7f8c8d;
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
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .checkbox-group input[type="checkbox"] {
            width: auto;
            transform: scale(1.2);
        }
    </style>
</head>
<body>
    <div class="edit-container">
        <h1>✏️ Editar Emergencia</h1>
        
        <?php if ($mensaje): ?>
            <div class="alert alert-success"><?= $mensaje ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Código de Emergencia:</label>
                <select name="codigo" required>
                    <option value="10-13" <?= $emergencia['codigo']=='10-13' ? 'selected' : '' ?>>10-13 - Oficial necesita ayuda</option>
                    <option value="10-54" <?= $emergencia['codigo']=='10-54' ? 'selected' : '' ?>>10-54 - Accidente de tráfico</option>
                    <option value="10-56" <?= $emergencia['codigo']=='10-56' ? 'selected' : '' ?>>10-56 - Persona intoxicada</option>
                    <option value="10-80" <?= $emergencia['codigo']=='10-80' ? 'selected' : '' ?>>10-80 - Persecución en curso</option>
                    <option value="10-99" <?= $emergencia['codigo']=='10-99' ? 'selected' : '' ?>>10-99 - Oficial en peligro</option>
                    <option value="Code 3" <?= $emergencia['codigo']=='Code 3' ? 'selected' : '' ?>>Code 3 - Máxima prioridad</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Descripción:</label>
                <textarea name="descripcion" rows="3" required><?= htmlspecialchars($emergencia['descripcion']) ?></textarea>
            </div>
            
            <div class="form-group">
                <label>Ubicación:</label>
                <input type="text" name="ubicacion" value="<?= htmlspecialchars($emergencia['ubicacion']) ?>" required>
            </div>
            
            <div class="form-group">
                <label>Unidades Asignadas:</label>
                <input type="text" name="unidades_asignadas" value="<?= htmlspecialchars($emergencia['unidades_asignadas']) ?>" required>
            </div>
            
            <div class="form-group">
                <div class="checkbox-group">
                    <input type="checkbox" name="activa" id="activa" <?= $emergencia['activa'] ? 'checked' : '' ?>>
                    <label for="activa">Emergencia Activa</label>
                </div>
            </div>
            
            <button type="submit" name="actualizar_emergencia" class="btn btn-primary">💾 Guardar Cambios</button>
            <a href="emergencias_crud.php" class="btn btn-secondary">← Cancelar</a>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-focus en el primer campo
            const firstInput = document.querySelector('form input, form select, form textarea');
            if (firstInput) firstInput.focus();
        });
    </script>
</body>
</html>