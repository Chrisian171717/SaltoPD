// Verificar si hay una sesión activa
function verificarSesion() {
    fetch('../Back-end/verificar_sesion.php')
        .then(response => response.json())
        .then(data => {
            if (!data.loggedin) {
                window.location.href = 'inicio de sesion.html';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            window.location.href = 'inicio de sesion.html';
        });
}

// Verificar al cargar la página
document.addEventListener('DOMContentLoaded', verificarSesion);