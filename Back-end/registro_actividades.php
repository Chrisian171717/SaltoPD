<?php
// Back-end/registro_actividades.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include("conexion.php");

// Obtener registros de actividades
$registros = array();
$result = $conn->query("SELECT * FROM registro_actividades ORDER BY fecha DESC LIMIT 100");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $registros[] = $row;
    }
}

// Estadísticas
$total_registros = count($registros);
$hoy = date('Y-m-d');
$registros_hoy = 0;
foreach ($registros as $r) {
    if (date('Y-m-d', strtotime($r['fecha'])) === $hoy) {
        $registros_hoy++;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Actividades</title>
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
        .registro-container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2c3e50;
            margin-bottom: 20px;
            border-bottom: 3px solid #9b59b6;
            padding-bottom: 10px;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            border-left: 4px solid #9b59b6;
        }
        .stat-number {
            font-size: 2em;
            font-weight: bold;
            color: #2c3e50;
        }
        .stat-label {
            color: #7f8c8d;
            font-size: 0.9em;
        }
        .registro-item {
            background: white;
            padding: 20px;
            margin: 15px 0;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-left: 4px solid #3498db;
            transition: transform 0.3s ease;
        }
        .registro-item:hover {
            transform: translateX(5px);
        }
        .registro-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 10px;
        }
        .registro-usuario {
            font-weight: bold;
            color: #2c3e50;
            font-size: 1.1em;
        }
        .registro-fecha {
            color: #7f8c8d;
            font-size: 0.9em;
        }
        .registro-accion {
            color: #555;
            margin: 10px 0;
            line-height: 1.5;
        }
        .registro-meta {
            font-size: 0.8em;
            color: #95a5a6;
            border-top: 1px solid #ecf0f1;
            padding-top: 10px;
            margin-top: 10px;
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
        .btn-success {
            background: #27ae60;
            color: white;
        }
        .btn-success:hover {
            background: #219a52;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #7f8c8d;
        }
        .empty-state h3 {
            margin-bottom: 10px;
            color: #95a5a6;
        }
        .filters {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .filter-group {
            margin-bottom: 15px;
        }
        .filter-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #495057;
        }
        .filter-group select, .filter-group input {
            padding: 8px 12px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            width: 200px;
        }
    </style>
</head>
<body>
    <div class="registro-container">
        <h1>📝 Registro de Actividades del Sistema</h1>
        
        <!-- Estadísticas -->
        <div class="stats">
            <div class="stat-card">
                <div class="stat-number"><?= $total_registros ?></div>
                <div class="stat-label">Total de Actividades</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $registros_hoy ?></div>
                <div class="stat-label">Actividades Hoy</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= count($registros) ?></div>
                <div class="stat-label">Mostrando</div>
            </div>
        </div>

        <!-- Lista de registros -->
        <?php if (count($registros) > 0): ?>
            <?php foreach($registros as $r): ?>
                <div class="registro-item">
                    <div class="registro-header">
                        <span class="registro-usuario">👤 <?= htmlspecialchars($r['usuario']) ?></span>
                        <span class="registro-fecha">🕐 <?= $r['fecha'] ?></span>
                    </div>
                    <div class="registro-accion">
                        <?= htmlspecialchars($r['accion']) ?>
                    </div>
                    <?php if ($r['tabla_afectada'] || $r['registro_id']): ?>
                        <div class="registro-meta">
                            <?php if ($r['tabla_afectada']): ?>
                                📊 Tabla: <?= htmlspecialchars($r['tabla_afectada']) ?>
                            <?php endif; ?>
                            <?php if ($r['registro_id']): ?>
                                | #️⃣ ID: <?= $r['registro_id'] ?>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <h3>📊 No hay actividades registradas</h3>
                <p>Las actividades del sistema aparecerán aquí automáticamente.</p>
                <p>Realiza algunas acciones en el sistema para ver los registros.</p>
            </div>
        <?php endif; ?>

        <div style="margin-top: 30px; text-align: center;">
            <a href="Radio.php" class="btn btn-success">← Volver al Panel de Radio</a>
            <a href="unidades_crud.php" class="btn btn-primary">🚔 Gestionar Unidades</a>
            <a href="emergencias_crud.php" class="btn btn-primary">🚨 Gestionar Emergencias</a>
        </div>
    </div>

    <script>
        // Auto-refresh cada 60 segundos para ver nuevas actividades
        setInterval(() => {
            window.location.reload();
        }, 60000);

        // Scroll suave al hacer clic en los registros
        document.addEventListener('DOMContentLoaded', function() {
            const registros = document.querySelectorAll('.registro-item');
            registros.forEach(registro => {
                registro.style.cursor = 'pointer';
                registro.addEventListener('click', function() {
                    this.style.background = '#f8f9fa';
                    setTimeout(() => {
                        this.style.background = 'white';
                    }, 200);
                });
            });
        });
    </script>
</body>
</html>