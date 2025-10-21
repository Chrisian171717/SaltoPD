// Elementos del DOM
const userButton = document.getElementById('userButton');
const dropdownMenu = document.getElementById('dropdownMenu');
const viewProfileLink = document.getElementById('viewProfile');
const headerAvatar = document.getElementById('headerAvatar');
const headerUsername = document.getElementById('headerUsername');
const logoutLink = document.getElementById('logoutLink');

// Toggle del menú desplegable
userButton.addEventListener('click', (e) => {
  e.stopPropagation();
  dropdownMenu.classList.toggle('show');
});

// Cerrar el menú al hacer clic fuera
document.addEventListener('click', (e) => {
  if (!userButton.contains(e.target) && !dropdownMenu.contains(e.target)) {
    dropdownMenu.classList.remove('show');
  }
});

// Ver perfil
viewProfileLink.addEventListener('click', (e) => {
  e.preventDefault();
  // Aquí puedes redirigir a la página de perfil o mostrar un modal
  console.log('Ver perfil clickeado');
  // window.location.href = 'perfil.html';
  dropdownMenu.classList.remove('show');
});

// Cerrar sesión
logoutLink.addEventListener('click', (e) => {
  e.preventDefault();
  
  // Confirmar cierre de sesión
  if (confirm('¿Estás seguro de que deseas cerrar sesión?')) {
    // Limpiar datos de sesión (si usas variables en memoria)
    console.log('Cerrando sesión...');
    
    // Redirigir a la página de inicio de sesión
    window.location.href = 'InicioDeSesion.html';
  } else {
    dropdownMenu.classList.remove('show');
  }
});

// Cargar datos del usuario al iniciar (simulado)
function loadUserData() {
  // Aquí normalmente cargarías datos de tu sistema de estado
  // Por ahora usamos datos de ejemplo
  const userData = {
    username: 'Usuario',
    avatar: '👮'
  };
  
  headerUsername.textContent = userData.username;
  headerAvatar.textContent = userData.avatar;
}

// Inicializar
loadUserData();