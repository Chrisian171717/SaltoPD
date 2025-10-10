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
        
        // Verificar si hay una sesión activa al cargar la página
        function checkSession() {
            // En una aplicación real, aquí verificarías con el backend
            // si hay una sesión activa
            const savedUser = localStorage.getItem('currentUser');
            if (savedUser) {
                currentUser = JSON.parse(savedUser);
                isLoggedIn = true;
                updateUI();
            }
        }

        // Verificar que los elementos existen antes de agregar event listeners
        function initializeEventListeners() {
            // Funcionalidad del chat
            const chatBtn = document.getElementById("chat-btn");
            if (chatBtn) {
                chatBtn.onclick = function(){
                    const chat = document.getElementById("chat-container");
                    if (chat) {
                        chat.style.display = (chat.style.display === "none") ? "flex" : "none";
                    }
                };
            }
            
            // Mostrar/ocultar menú desplegable
            if (userProfile) {
                userProfile.addEventListener('click', function(e) {
                    e.stopPropagation();
                    if (dropdownMenu) {
                        dropdownMenu.classList.toggle('show');
                    }
                });
            }
            
            // Cerrar menú al hacer clic fuera
            document.addEventListener('click', function() {
                if (dropdownMenu) {
                    dropdownMenu.classList.remove('show');
                }
            });
            
            // Funcionalidad de las opciones del menú
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

        // Actualizar la interfaz según el estado de autenticación
        function updateUI() {
            if (isLoggedIn && currentUser) {
                // Usuario logeado - mostrar perfil
                if (authButtons) authButtons.style.display = 'none';
                if (userProfile) userProfile.style.display = 'flex';
                updateUserProfile();
            } else {
                // Usuario no logeado - mostrar botones de autenticación
                if (authButtons) authButtons.style.display = 'flex';
                if (userProfile) userProfile.style.display = 'none';
            }
        }

        // Inicializar event listeners cuando el DOM esté cargado
        document.addEventListener('DOMContentLoaded', function() {
            initializeEventListeners();
            checkSession();
        });
        
        // Funciones auxiliares
        function login(userData) {
            currentUser = userData;
            isLoggedIn = true;
            
            // Guardar en localStorage (en una app real usarías session del backend)
            localStorage.setItem('currentUser', JSON.stringify(userData));
            
            // Actualizar interfaz
            updateUI();
            
            alert(`Bienvenido, ${userData.name}`);
        }
        
        function logout() {
            currentUser = null;
            isLoggedIn = false;
            
            // Limpiar localStorage
            localStorage.removeItem('currentUser');
            
            // Actualizar interfaz
            updateUI();
            
            alert('Sesión cerrada correctamente');
        }
        
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

        // Función para simular login (para testing)
        // En una aplicación real, esto se haría desde InicioDeSesion.html
        function simulateLogin() {
            login({
                name: "Usuario Demo",
                email: "demo@ejemplo.com",
                role: "user",
                initials: "UD"
            });
        }

        // Función para simular login de admin (para testing)
        function simulateAdminLogin() {
            login({
                name: "Administrador",
                email: "admin@ejemplo.com",
                role: "admin",
                initials: "AD"
            });
        }