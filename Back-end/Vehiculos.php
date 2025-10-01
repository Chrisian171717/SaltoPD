<?php
// Configuración de CORS para permitir requests desde el frontend
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

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

// Manejar método OPTIONS para CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
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
            $sql = "DELETE FROM automovil WHERE Matricula='$matricula'";
            if ($conn->query($sql)) {
                $success = "✅ Vehículo <strong>'$matricula'</strong> eliminado correctamente.";
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
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #2c3e50, #34495e);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }
        
        .header p {
            font-size: 1.1em;
            opacity: 0.9;
        }
        
        .content {
            padding: 30px;
        }
        
        /* ESTILOS PARA IMÁGENES PEQUEÑAS */
        .nav-tabs {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 15px 0;
            margin-bottom: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .nav-tabs__list {
            list-style: none;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 30px;
            flex-wrap: wrap;
        }

        .nav-tabs__link {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-decoration: none;
            color: #2c3e50;
            transition: all 0.3s ease;
            padding: 10px;
            border-radius: 10px;
            width: 80px;
        }

        .nav-tabs__link:hover {
            background: #3498db;
            color: white;
            transform: translateY(-3px);
        }

        .nav-tabs__icon {
            width: 35px;
            height: 35px;
            margin-bottom: 5px;
            object-fit: contain;
            transition: transform 0.3s ease;
        }

        .nav-tabs__link:hover .nav-tabs__icon {
            transform: scale(1.1);
        }

        .header-icon {
            width: 45px;
            height: 45px;
            object-fit: contain;
        }

        .alert {
            padding: 15px;
            margin: 20px 0;
            border-radius: 8px;
            text-align: center;
            font-weight: bold;
        }
        
        .alert-error {
            background: #ffe6e6;
            color: #cc0000;
            border: 1px solid #ffcccc;
        }
        
        .alert-success {
            background: #e6ffe6;
            color: #009900;
            border: 1px solid #ccffcc;
        }
        
        /* BOTONES MEJORADOS */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 25px;
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            transition: all 0.3s ease;
            margin: 5px;
        }
        
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.2);
        }
        
        .btn-add {
            background: linear-gradient(135deg, #27ae60, #219a52);
            padding: 12px 25px;
        }
        
        .btn-add-large {
            background: linear-gradient(135deg, #27ae60, #219a52);
            font-size: 18px;
            padding: 16px 32px;
            margin: 20px auto;
            display: block;
            width: fit-content;
        }
        
        .btn-edit {
            background: linear-gradient(135deg, #f39c12, #e67e22);
            padding: 8px 15px;
            font-size: 14px;
        }
        
        .btn-delete {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            padding: 8px 15px;
            font-size: 14px;
        }

        .btn-back {
            background: linear-gradient(135deg, #95a5a6, #7f8c8d);
            padding: 8px 15px;
            font-size: 14px;
        }

        .btn-admin {
            background: linear-gradient(135deg, #f39c12, #e67e22);
            padding: 12px 25px;
        }

        .btn-view {
            background: linear-gradient(135deg, #9b59b6, #8e44ad);
            padding: 10px 20px;
            font-size: 14px;
        }

        .table-container {
            overflow-x: auto;
            margin: 30px 0;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }
        
        th {
            background: linear-gradient(135deg, #34495e, #2c3e50);
            color: white;
            padding: 18px;
            text-align: left;
            font-weight: 600;
            font-size: 16px;
        }
        
        td {
            padding: 16px 18px;
            border-bottom: 1px solid #ecf0f1;
            font-size: 15px;
        }

        tr:hover {
            background: #f8f9fa;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            padding: 30px;
            border-radius: 15px;
            text-align: center;
            transition: transform 0.3s ease;
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .stat-card:hover {
            transform: translateY(-8px);
        }

        .stat-card.marcas {
            background: linear-gradient(135deg, #27ae60, #219a52);
        }

        .stat-card.tipos {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
        }

        .stat-number {
            font-size: 3em;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .stat-label {
            font-size: 1.1em;
            opacity: 0.9;
        }

        .actions-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 20px;
        }

        .page-title {
            color: #2c3e50;
            font-size: 2.2em;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: bold;
            margin-left: 5px;
        }

        .badge-sedan { background: #3498db; color: white; }
        .badge-suv { background: #27ae60; color: white; }
        .badge-camioneta { background: #e67e22; color: white; }
        .badge-hatchback { background: #9b59b6; color: white; }
        .badge-deportivo { background: #e74c3c; color: white; }
        .badge-motocicleta { background: #34495e; color: white; }
        .badge-camion { background: #7f8c8d; color: white; }
        .badge-otro { background: #95a5a6; color: white; }

        .form-container {
            max-width: 700px;
            margin: 0 auto;
            background: #f8f9fa;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }

        .form-group {
            margin-bottom: 25px;
        }

        label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: #2c3e50;
            font-size: 16px;
        }

        input, select {
            width: 100%;
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 16px;
            transition: border-color 0.3s ease;
            background: white;
        }

        input:focus, select:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        .form-actions {
            text-align: center;
            margin-top: 40px;
        }

        .empty-state {
            text-align: center;
            padding: 60px;
            color: #7f8c8d;
        }

        .empty-state img {
            width: 80px;
            height: 80px;
            margin-bottom: 20px;
            opacity: 0.7;
            object-fit: contain;
        }

        .admin-panel {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin: 30px 0;
            flex-wrap: wrap;
        }

        .actions-container {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
            margin: 20px 0;
        }

        .quick-actions {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 15px;
            margin: 30px 0;
            text-align: center;
        }

        .quick-actions h3 {
            margin-bottom: 20px;
            color: #2c3e50;
        }

        .view-frontend {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            text-align: center;
            margin-top: 30px;
            padding: 18px 30px;
            background: linear-gradient(135deg, #9b59b6, #8e44ad);
            color: white;
            text-decoration: none;
            border-radius: 12px;
            font-weight: bold;
            transition: all 0.3s ease;
            font-size: 18px;
        }

        .view-frontend:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.2);
        }

        .icon-large {
            font-size: 1.3em;
        }

        /* ANIMACIONES */
        @keyframes fadeIn {
            from { 
                opacity: 0; 
                transform: translateY(-20px);
            }
            to { 
                opacity: 1; 
                transform: translateY(0);
            }
        }

        @keyframes slideIn {
            from { 
                transform: translateX(-100%); 
                opacity: 0;
            }
            to { 
                transform: translateX(0); 
                opacity: 1;
            }
        }

        .vehiculo-card {
            animation: slideIn 0.5s ease;
        }

        .vehiculo-card:nth-child(even) {
            animation-delay: 0.1s;
        }

        .vehiculo-card:nth-child(odd) {
            animation-delay: 0.2s;
        }

        /* RESPONSIVE */
        @media (max-width: 768px) {
            .nav-tabs__list {
                gap: 15px;
            }
            
            .nav-tabs__link {
                width: 70px;
                padding: 8px;
            }
            
            .nav-tabs__icon {
                width: 30px;
                height: 30px;
            }
            
            .vehiculos-header {
                flex-direction: column;
                text-align: center;
            }
            
            .vehiculos-header__branding {
                justify-content: center;
            }
            
            .vehiculo-card {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }
            
            .header h1 {
                font-size: 2em;
            }
            
            .btn {
                padding: 10px 18px;
                font-size: 14px;
            }
            
            .stats {
                grid-template-columns: 1fr;
            }
            
            .actions-header {
                flex-direction: column;
                text-align: center;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 10px;
            }
            
            .nav-tabs__list {
                gap: 10px;
            }
            
            .nav-tabs__link {
                width: 60px;
                font-size: 0.8em;
            }
            
            .nav-tabs__icon {
                width: 25px;
                height: 25px;
            }
            
            .vehiculos-section {
                padding: 15px;
            }
            
            .btn {
                padding: 8px 15px;
                font-size: 12px;
            }
            
            .container {
                border-radius: 10px;
            }
            
            .content {
                padding: 20px;
            }
            
            .header-icon {
                width: 35px;
                height: 35px;
            }
            
            .empty-state img {
                width: 60px;
                height: 60px;
            }
        }
    </style>
</head>
<body>
    <!-- Navegación con imágenes pequeñas -->
    <nav class="nav-tabs" aria-label="Navegación principal">
        <ul class="nav-tabs__list">
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
            <li><a href="../Front-end/principal.html" class="nav-tabs__link">
                <img src="../Front-end/Logo.png" alt="Sección principal" class="nav-tabs__icon" />
                <span>Principal</span>
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
                Sistema de Gestión de Vehículos
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

    <script>
        // Mejorar la experiencia del usuario
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-focus en el primer campo del formulario
            const firstInput = document.querySelector('input[type="text"]:not([readonly])');
            if (firstInput) {
                firstInput.focus();
            }
            
            // Confirmación antes de eliminar
            const deleteButtons = document.querySelectorAll('.btn-delete');
            deleteButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    if (!confirm('⚠️ ¿Está completamente seguro de que desea eliminar este automóvil?\n\nEsta acción NO se puede deshacer y los datos se perderán permanentemente.')) {
                        e.preventDefault();
                    }
                });
            });

            // Validación en tiempo real del formulario
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    const inputs = form.querySelectorAll('input[required], select[required]');
                    let valid = true;
                    
                    inputs.forEach(input => {
                        if (!input.value.trim()) {
                            valid = false;
                            input.style.borderColor = '#e74c3c';
                            input.style.backgroundColor = '#ffe6e6';
                        } else {
                            input.style.borderColor = '#e0e0e0';
                            input.style.backgroundColor = 'white';
                        }
                    });
                    
                    if (!valid) {
                        e.preventDefault();
                        alert('⚠️ Por favor, complete todos los campos obligatorios.');
                    }
                });
            });

            // Efectos hover mejorados para botones
            const buttons = document.querySelectorAll('.btn');
            buttons.forEach(button => {
                button.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-3px)';
                });
                button.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
        });
    </script>
</body>
</html>