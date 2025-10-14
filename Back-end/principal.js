// ===============================
// 🔐 SISTEMA DE SESIÓN Y PERFIL
// ===============================

// Estado de la aplicación 
let currentUser = null;
let isLoggedIn = false;

// Elementos del DOM
const authButtons = document.getElementById('auth-buttons');
const userProfile = document.getElementById('user-profile');
const dropdownMenu = document.getElementById('dropdown-menu');
const userAvatar = document.getElementById('user-avatar');
const userName = document.getElementById('user-name');
const userRole = document.getElementById('user-role');
const adminPanel = document.getElementById('admin-panel');

// ===============================
// 🕒 CONFIGURACIÓN DE INACTIVIDAD
// ===============================

// Tiempo máximo sin actividad antes del cierre (en milisegundos)
const INACTIVITY_LIMIT = 5 * 60 * 1000; // 5 minutos
let inactivityTimer;

// Reinicia el temporizador cada vez que el usuario interactúa
function resetInactivityTimer() {
    clearTimeout(inactivityTimer);
    if (isLoggedIn) {
        inactivityTimer = setTimeout(() => {
            alert("Has estado inactivo durante 5 minutos. Tu sesión se cerrará por seguridad.");
            logout();
        }, INACTIVITY_LIMIT);
    }
}

// Detectar interacción del usuario
["mousemove", "keydown", "click", "scroll", "touchstart"].forEach(evt => {
    document.addEventListener(evt, resetInactivityTimer);
});

// ===============================
// 🚀 FUNCIÓN DE INICIO AUTOMÁTICO
// ===============================

// Verificar si hay una sesión activa al cargar la página
function checkSession() {
    const savedUser = localStorage.getItem('currentUser');
    if (savedUser) {
        currentUser = JSON.parse(savedUser);
        isLoggedIn = true;
        updateUI();
        resetInactivityTimer();
    }
}

// ===============================
// ⚙️ EVENT LISTENERS Y FUNCIONES
// ===============================

function initializeEventListeners() {
    // 🗨️ Botón de chat
    const chatBtn = document.getElementById("chat-btn");
    if (chatBtn) {
        chatBtn.onclick = function(){
            const chat = document.getElementById("chat-container");
            if (chat) {
                chat.style.display = (chat.style.display === "none") ? "flex" : "none";
            }
        };
    }
    
    // 👤 Menú desplegable del perfil
    if (userProfile) {
        userProfile.addEventListener('click', function(e) {
            e.stopPropagation();
            if (dropdownMenu) dropdownMenu.classList.toggle('show');
        });
    }

    // 🔒 Cerrar menú al hacer clic fuera
    document.addEventListener('click', function() {
        if (dropdownMenu) dropdownMenu.classList.remove('show');
    });
    
    // ⚙️ Opciones del menú
    const profileOption = document.getElementById('profile-option');
    if (profileOption) {
        profileOption.addEventListener('click', function() {
            alert('Funcionalidad de perfil - En desarrollo');
            if (dropdownMenu) dropdownMenu.classList.remove('show');
        });
    }
    
    if (adminPanel) {
        adminPanel.addEventListener('click', function() {
            alert('Redirigiendo al Panel de Administración');
            if (dropdownMenu) dropdownMenu.classList.remove('show');
        });
    }
    
    const settingsOption = document.getElementById('settings-option');
    if (settingsOption) {
        settingsOption.addEventListener('click', function() {
            alert('Redirigiendo a Configuración');
            if (dropdownMenu) dropdownMenu.classList.remove('show');
        });
    }
    
    const logoutOption = document.getElementById('logout-option');
    if (logoutOption) {
        logoutOption.addEventListener('click', function() {
            if (confirm('¿Estás seguro de que deseas cerrar sesión?')) {
                logout();
                if (dropdownMenu) dropdownMenu.classList.remove('show');
            }
        });
    }
}

// ===============================
// 🎨 ACTUALIZAR INTERFAZ
// ===============================

function updateUI() {
    if (isLoggedIn && currentUser) {
        if (authButtons) authButtons.style.display = 'none';
        if (userProfile) userProfile.style.display = 'flex';
        updateUserProfile();
    } else {
        if (authButtons) authButtons.style.display = 'flex';
        if (userProfile) userProfile.style.display = 'none';
    }
}

// ===============================
// 🔑 LOGIN / LOGOUT
// ===============================

function login(userData) {
    currentUser = userData;
    isLoggedIn = true;
    localStorage.setItem('currentUser', JSON.stringify(userData));
    updateUI();
    resetInactivityTimer();
    alert(`Bienvenido, ${userData.name}`);
}

function logout() {
    currentUser = null;
    isLoggedIn = false;
    localStorage.removeItem('currentUser');
    clearTimeout(inactivityTimer);
    updateUI();
    alert('Sesión cerrada correctamente');
}

// ===============================
// 👤 ACTUALIZAR PERFIL DE USUARIO
// ===============================

function updateUserProfile() {
    if (currentUser && userName && userAvatar && userRole && adminPanel) {
        userName.textContent = currentUser.name;
        userAvatar.textContent = currentUser.initials;
        if (currentUser.role === 'admin') {
            userRole.textContent = 'Administrador';
            adminPanel.style.display = 'flex';
        } else {
            userRole.textContent = 'Usuario';
            adminPanel.style.display = 'none';
        }
    }
}

// ===============================
// 🧪 FUNCIONES DE TESTEO
// ===============================

function simulateLogin() {
    login({
        name: "Usuario Demo",
        email: "demo@ejemplo.com",
        role: "user",
        initials: "UD"
    });
}

function simulateAdminLogin() {
    login({
        name: "Administrador",
        email: "admin@ejemplo.com",
        role: "admin",
        initials: "AD"
    });
}

// ===============================
// 🚀 INICIALIZAR APP
// ===============================

document.addEventListener('DOMContentLoaded', function() {
    initializeEventListeners();
    checkSession();
});
