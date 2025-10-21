// ===============================
// 🚀 SISTEMA UNIFICADO DE AUTENTICACIÓN Y PERFIL
// ===============================

// Estado global de la aplicación
let currentUser = null;
let isLoggedIn = false;

// Elementos del DOM
const authButtons = document.getElementById('auth-buttons');
const userProfile = document.getElementById('user-profile');
const dropdownMenu = document.getElementById('dropdown-menu') || document.getElementById('dropdownMenu');
const userAvatar = document.getElementById('user-avatar') || document.getElementById('headerAvatar');
const userName = document.getElementById('user-name') || document.getElementById('headerUsername');
const userRole = document.getElementById('user-role');
const adminPanel = document.getElementById('admin-panel');

// Elementos del perfil
const viewProfile = document.getElementById('viewProfile');
const profileModal = document.getElementById('profileModal');
const closeProfileModal = document.getElementById('closeProfileModal');
const btnCloseProfile = document.getElementById('btnCloseProfile');
const btnLogoutProfile = document.getElementById('btnLogoutProfile');
const logoutLink = document.getElementById('logoutLink');
const userButton = document.getElementById('userButton');

// Elementos de datos del perfil
const profileName = document.getElementById('profileName');
const profileRole = document.getElementById('profileRole');
const profilePlaca = document.getElementById('profilePlaca');
const profileFullName = document.getElementById('profileFullName');
const profileEmail = document.getElementById('profileEmail');
const profilePhone = document.getElementById('profilePhone');
const profileRoleBadge = document.getElementById('profileRoleBadge');
const profileDepartment = document.getElementById('profileDepartment');
const profileJoinDate = document.getElementById('profileJoinDate');

// Elementos del chat
const chatBtn = document.getElementById('chat-btn');
const chatbot = document.getElementById('chatbot');
const cerrarChat = document.getElementById('cerrarChat');

// ===============================
// ⏰ CONFIGURACIÓN DE INACTIVIDAD
// ===============================

const INACTIVITY_LIMIT = 5 * 60 * 1000; // 5 minutos
let inactivityTimer;

function resetInactivityTimer() {
    clearTimeout(inactivityTimer);
    if (isLoggedIn) {
        inactivityTimer = setTimeout(() => {
            alert('Has estado inactivo durante 5 minutos. Tu sesión se cerrará por seguridad.');
            logout();
        }, INACTIVITY_LIMIT);
    }
}

// ===============================
// 🔐 MANEJO DE LOGIN
// ===============================

document.getElementById('loginForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    // Limpiar errores previos
    document.querySelectorAll('.error-message').forEach(el => el.textContent = '');
    
    const formData = new FormData(this);
    
    try {
        const response = await fetch('InicioSesion.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert(data.message || '¡Inicio de sesión exitoso!');
            
            // Guardar datos del usuario
            const userData = {
                name: data.user?.name || 'Usuario',
                email: data.user?.email || '',
                role: data.user?.role || 'user',
                initials: data.user?.initials || 'US',
                placa: data.user?.placa || '',
                telefono: data.user?.telefono || '',
                departamento: data.user?.departamento || '',
                fechaIngreso: data.user?.fechaIngreso || '',
                avatar: data.user?.avatar || '👤'
            };
            
            localStorage.setItem('currentUser', JSON.stringify(userData));
            localStorage.setItem('usuarioActual', JSON.stringify(userData));
            
            login(userData);
            window.location.href = data.redirect;
            
        } else {
            if (data.errors) {
                for (const [field, message] of Object.entries(data.errors)) {
                    const errorElement = document.getElementById(`error-${field}`);
                    if (errorElement) {
                        errorElement.textContent = message;
                    }
                }
            } else {
                alert(data.message || 'Error al iniciar sesión');
            }
        }
        
    } catch (error) {
        console.error('Error:', error);
        alert('Error de conexión. Por favor, intenta nuevamente.');
    }
});

// ===============================
// 🔑 FUNCIONES DE AUTENTICACIÓN
// ===============================

function login(userData) {
    currentUser = userData;
    isLoggedIn = true;
    localStorage.setItem('currentUser', JSON.stringify(userData));
    localStorage.setItem('usuarioActual', JSON.stringify(userData));
    updateUI();
    resetInactivityTimer();
}

function logout() {
    currentUser = null;
    isLoggedIn = false;
    localStorage.removeItem('currentUser');
    localStorage.removeItem('usuarioActual');
    clearTimeout(inactivityTimer);
    updateUI();
    window.location.href = 'InicioDeSesion.html';
}

// ===============================
// 🎨 ACTUALIZACIÓN DE INTERFAZ
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

function updateUserProfile() {
    if (!currentUser) return;
    
    // Actualizar header
    if (userName) userName.textContent = currentUser.name;
    if (userAvatar) userAvatar.textContent = currentUser.avatar || currentUser.initials;
    
    // Actualizar rol y panel admin
    if (userRole) {
        userRole.textContent = currentUser.role === 'admin' ? 'Administrador' : 'Usuario';
    }
    
    if (adminPanel) {
        adminPanel.style.display = currentUser.role === 'admin' ? 'flex' : 'none';
    }
    
    // Actualizar modal de perfil
    updateProfileModal();
}

function updateProfileModal() {
    if (!currentUser || !profileName) return;
    
    profileName.textContent = currentUser.name;
    profileRole.textContent = currentUser.role === 'admin' ? 'Administrador' : 'Usuario';
    profilePlaca.textContent = `Placa: ${currentUser.placa || 'N/A'}`;
    profileFullName.textContent = currentUser.name;
    profileEmail.textContent = currentUser.email || 'N/A';
    profilePhone.textContent = currentUser.telefono || 'N/A';
    profileRoleBadge.textContent = currentUser.role === 'admin' ? 'Administrador' : 'Usuario';
    profileDepartment.textContent = currentUser.departamento || 'N/A';
    profileJoinDate.textContent = currentUser.fechaIngreso || 'N/A';
}

// ===============================
// 🪟 MANEJO DE MODALES Y DROPDOWNS
// ===============================

function abrirModalPerfil() {
    if (profileModal) {
        profileModal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        profileModal.classList.add('fade-in');
    }
}

function cerrarModalPerfil() {
    if (profileModal) {
        profileModal.style.display = 'none';
        document.body.style.overflow = 'auto';
        profileModal.classList.remove('fade-in');
    }
}

function toggleDropdown() {
    if (dropdownMenu) {
        dropdownMenu.classList.toggle('show');
        dropdownMenu.classList.toggle('active');
    }
}

function cerrarDropdown() {
    if (dropdownMenu) {
        dropdownMenu.classList.remove('show');
        dropdownMenu.classList.remove('active');
    }
}

// ===============================
// 💬 MANEJO DEL CHAT
// ===============================

function toggleChat() {
    if (chatbot) {
        chatbot.style.display = chatbot.style.display === 'none' ? 'flex' : 'none';
    }
}

function cerrarChatbot() {
    if (chatbot) {
        chatbot.style.display = 'none';
    }
}

// ===============================
// 🎯 EVENT LISTENERS
// ===============================

function initializeEventListeners() {
    // Detectar interacción del usuario para inactividad
    ['mousemove', 'keydown', 'click', 'scroll', 'touchstart'].forEach(evt => {
        document.addEventListener(evt, resetInactivityTimer);
    });
    
    // Botón de chat
    if (chatBtn) {
        chatBtn.addEventListener('click', toggleChat);
    }
    
    // Cerrar chat
    if (cerrarChat) {
        cerrarChat.addEventListener('click', cerrarChatbot);
    }
    
    // Dropdown del usuario
    if (userButton) {
        userButton.addEventListener('click', toggleDropdown);
    }
    
    // Cerrar dropdown al hacer clic fuera
    document.addEventListener('click', function(e) {
        if (userButton && !userButton.contains(e.target) && 
            dropdownMenu && !dropdownMenu.contains(e.target)) {
            cerrarDropdown();
        }
        
        // Cerrar modal al hacer clic fuera
        if (profileModal && e.target === profileModal) {
            cerrarModalPerfil();
        }
    });
    
    // Perfil y modal
    if (viewProfile) {
        viewProfile.addEventListener('click', function(e) {
            e.preventDefault();
            abrirModalPerfil();
            cerrarDropdown();
        });
    }
    
    if (closeProfileModal) {
        closeProfileModal.addEventListener('click', cerrarModalPerfil);
    }
    
    if (btnCloseProfile) {
        btnCloseProfile.addEventListener('click', cerrarModalPerfil);
    }
    
    // Cerrar sesión
    if (logoutLink) {
        logoutLink.addEventListener('click', function(e) {
            e.preventDefault();
            if (confirm('¿Estás seguro de que deseas cerrar sesión?')) {
                logout();
            }
        });
    }
    
    if (btnLogoutProfile) {
        btnLogoutProfile.addEventListener('click', function() {
            if (confirm('¿Estás seguro de que deseas cerrar sesión?')) {
                logout();
            }
        });
    }
    
    // Tecla Escape para cerrar modal
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && profileModal && profileModal.style.display === 'flex') {
            cerrarModalPerfil();
        }
    });
    
    // Panel de administración
    if (adminPanel) {
        adminPanel.addEventListener('click', function() {
            alert('Redirigiendo al Panel de Administración');
            cerrarDropdown();
        });
    }
}

// ===============================
// 🔍 VERIFICACIÓN DE SESIÓN
// ===============================

function checkSession() {
    const savedUser = localStorage.getItem('currentUser') || localStorage.getItem('usuarioActual');
    if (savedUser) {
        try {
            currentUser = JSON.parse(savedUser);
            isLoggedIn = true;
            updateUI();
            resetInactivityTimer();
        } catch (error) {
            console.error('Error parsing user data:', error);
            localStorage.removeItem('currentUser');
            localStorage.removeItem('usuarioActual');
        }
    }
}

// ===============================
// 🧪 FUNCIONES DE TESTEO (OPCIONAL)
// ===============================

function simulateLogin() {
    login({
        name: 'Usuario Demo',
        email: 'demo@ejemplo.com',
        role: 'user',
        initials: 'UD',
        placa: 'PL-2024',
        telefono: '+598 99 123 456',
        departamento: 'Montevideo',
        fechaIngreso: '2024-01-01',
        avatar: '👤'
    });
}

function simulateAdminLogin() {
    login({
        name: 'Administrador',
        email: 'admin@ejemplo.com',
        role: 'admin',
        initials: 'AD',
        placa: 'AD-001',
        telefono: '+598 99 999 999',
        departamento: 'Sistema',
        fechaIngreso: '2023-01-01',
        avatar: '👨‍💼'
    });
}

// ===============================
// 🚀 INICIALIZACIÓN
// ===============================

document.addEventListener('DOMContentLoaded', function() {
    initializeEventListeners();
    checkSession();
    
    // Limpiar animación del modal
    if (profileModal) {
        profileModal.addEventListener('animationend', function() {
            this.classList.remove('fade-in');
        });
    }
});