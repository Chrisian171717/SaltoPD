<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] != 'policia') {
    echo '<script>window.location.href = "../Front-end/principal.html";</script>';
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Policía</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 0;
            padding: 0;
            background: #f4f4f4;
        }
        .header { 
            background: #27ae60; 
            color: white; 
            padding: 20px; 
            text-align: center;
        }
        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
        }
        .user-info { 
            background: white; 
            padding: 20px; 
            border-radius: 5px; 
            margin: 20px 0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .menu { 
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 30px 0;
        }
        .menu a { 
            display: block;
            background: #2ecc71; 
            color: white; 
            padding: 15px; 
            text-decoration: none; 
            border-radius: 5px;
            text-align: center;
            transition: background 0.3s;
        }
        .menu a:hover { 
            background: #27ae60; 
        }
        .logout-btn {
            background: #e74c3c !important;
        }
        .logout-btn:hover {
            background: #c0392b !important;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>👮 Dashboard Policía - SaltoPD</h1>
    </div>
    
    <div class="container">
        <div class="user-info">
            <h2>👤 Bienvenido, <?php echo htmlspecialchars($_SESSION['user_nombre'] . ' ' . $_SESSION['user_apellido']); ?></h2>
            <p><strong>📧 Email:</strong> <?php echo htmlspecialchars($_SESSION['user_email']); ?></p>
            <p><strong>🎯 Rol:</strong> Policía</p>
            <p><strong>🔢 Placa:</strong> <?php echo htmlspecialchars($_SESSION['user_placa']); ?></p>
        </div>
        
        <div class="menu">
            <a href="nuevo_reporte.php">📋 Nuevo Reporte</a>
            <a href="mis_reportes.php">📁 Mis Reportes</a>
            <a href="perfil.php">👤 Mi Perfil</a>
            <a href="logout.php" class="logout-btn">🚪 Cerrar Sesión</a>
        </div>
    </div>
</body>
</html>