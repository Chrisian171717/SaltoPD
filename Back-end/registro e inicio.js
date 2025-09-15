const registroForm = document.querySelector('#registro form');
if (registroForm) {
    registroForm.addEventListener('submit', e => {
        const pass = registroForm.contrasena.value;
        if (pass.length < 8) {
            e.preventDefault();
            alert('La contraseña debe tener al menos 8 caracteres.');
        }
    });
}

const loginForm = document.querySelector('#login form');
if (loginForm) {
    loginForm.addEventListener('submit', e => {
        const pass = loginForm.password.value;
        const confirm = loginForm.confipassword.value;
        if (pass !== confirm) {
            e.preventDefault();
            alert('Las contraseñas no coinciden.');
        }
    });
}