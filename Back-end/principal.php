<?php
// principal.php
session_start();

// Configuración de cabeceras para CORS y tipo de contenido
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Manejo de la solicitud
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Procesar datos del formulario si es necesario
    $response = array(
        'status' => 'success',
        'message' => 'Solicitud procesada correctamente',
        'timestamp' => date('Y-m-d H:i:s')
    );
    
    echo json_encode($response);
    exit;
}

// Si no es POST, mostrar la página HTML
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="Estilo.css">
    <link rel="icon" href="logo.png" type="image/png">
    <title>Pagina Principal</title>
    <script src="../Back-end/principal.js" defer></script>
</head>
<body>
    <header class="header">
        <div class="header__branding">
            <img src="Logo.png" alt="Logo SALTODP" class="header__logo" />
            <h1 class="header__title">Página Principal</h1>
        </div>
        
        <!-- Botones de autenticación -->
        <div class="auth-buttons" id="auth-buttons">
            <a href="InicioDeSesion.html" class="auth-btn login-btn" id="login-btn">Iniciar Sesión</a>
            <a href="Registro.html" class="auth-btn register-btn" id="register-btn">Registro</a>
        </div>
        
        <!-- Perfil de usuario (inicialmente oculto) -->
        <div class="user-profile" id="user-profile" style="display: none;">
            <div class="user-avatar" id="user-avatar">U</div>
            <div class="user-info">
                <div class="user-name" id="user-name">Usuario</div>
                <div class="user-role" id="user-role">Usuario</div>
            </div>
            <div class="dropdown-menu" id="dropdown-menu">
                <div class="dropdown-item" id="profile-option">
                    <span class="dropdown-icon">👤</span> Mi Perfil
                </div>
                <div class="dropdown-item admin-only" id="admin-panel">
                    <span class="dropdown-icon">⚙️</span> Panel Admin
                </div>
                <div class="dropdown-item" id="settings-option">
                    <span class="dropdown-icon">⚙️</span> Configuración
                </div>
                <div class="dropdown-item" id="logout-option">
                    <span class="dropdown-icon">🚪</span> Cerrar Sesión
                </div>
            </div>
        </div>
    </header>

    <div id="chat-btn">💬</div>
    
    <div id="chat-container" style="display:none;">
        <h2 style="padding: 1rem; margin: 0; border-bottom: 1px solid #ddd;">ChatBot</h2>
        <div id="chatbox"></div>
        <div id="input-container">
            <input id="msg" placeholder="Escribe un mensaje">
            <button id="send-btn">Enviar</button>
        </div>
    </div>

    <form action="principal.php" method="post">
        <nav class="nav-tabs" aria-label="Navegación principal">
            <ul class="nav-tabs__list">
                <li><a href="civiles.html" class="nav-tabs__link"><img src="Civil.png" alt="Sección Civil" class="nav-tabs__icon" /></a></li>
                <li><a href="Denuncias.html" class="nav-tabs__link"><img src="Denuncias.png" alt="Sección Denuncias" class="nav-tabs__icon" /></a></li>
                <li><a href="Vehiculo2.0.html" class="nav-tabs__link"><img src="Vehiculo.png" alt="Sección Vehículo" class="nav-tabs__icon" /></a></li>
                <li><a href="Mapa.html" class="nav-tabs__link"><img src="mapa.png" alt="Sección Mapa" class="nav-tabs__icon" /></a></li>
                <li><a href="Radio.html" class="nav-tabs__link"><img src="radio-policia-2507338-2102444.png" alt="Sección Radio" class="nav-tabs__icon" /></a></li>
                <li><a href="Escaner Facial.html" class="nav-tabs__link"><img src="Escaner Facial.png" alt="Sección Escaner" class="nav-tabs__icon" /></a></li>
            </ul>
        </nav>

        <footer class="footer">
            <p class="footer__text">&copy; 2025 SALTODP. Todos los derechos reservados.</p>
        </footer>
    </form>
</body>
</html>