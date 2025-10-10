<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] != 'administrador') {
    echo '<script>window.location.href = "../Front-end/principal.html";</script>';
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Administrador</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 0;
            padding: 0;
            background: #f4f4f4;
        }
        .header { 
            background: #2c3e50; 
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
            background: #3498db; 
            color: white; 
            padding: 15px; 
            text-decoration: none; 
            border-radius: 5px;
            text-align: center;
            transition: background 0.3s;
        }
        .menu a:hover { 
            background: #2980b9; 
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
        <h1>🚓 Panel de Administración - SaltoPD</h1>
    </div>
    
    <div class="container">
        <div class="user-info">
            <h2>👤 Bienvenido, <?php echo htmlspecialchars($_SESSION['user_nombre'] . ' ' . $_SESSION['user_apellido']); ?></h2>
            <p><strong>📧 Email:</strong> <?php echo htmlspecialchars($_SESSION['user_email']); ?></p>
            <p><strong>🎯 Rol:</strong> Administrador</p>
            <p><strong>🔢 Placa:</strong> <?php echo htmlspecialchars($_SESSION['user_placa']); ?></p>
        </div>
        
        <div class="menu">
            <a href="gestion_usuarios.php">👥 Gestión de Usuarios</a>
            <a href="reportes.php">📊 Reportes</a>
            <a href="configuracion.php">⚙️ Configuración</a>
            <a href="logout.php" class="logout-btn">🚪 Cerrar Sesión</a>
        </div>
    </div>
</body>
</html>