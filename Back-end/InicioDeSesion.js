console.log('✅ InicioDeSesion.js cargado desde Front-end');

document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Sistema de login iniciado');
    
    const loginForm = document.getElementById('loginForm');
    if (!loginForm) {
        console.error('❌ No se encontró el formulario');
        return;
    }

    // Configurar toggles de contraseña
    const togglePasswordBtn = document.getElementById('togglePassword');
    const toggleConfPasswordBtn = document.getElementById('toggleConfPassword');
    const passwordInput = document.getElementById('password');
    const confPasswordInput = document.getElementById('confipassword');

    if (togglePasswordBtn && passwordInput) {
        togglePasswordBtn.addEventListener('click', function() {
            const isPassword = passwordInput.type === 'password';
            passwordInput.type = isPassword ? 'text' : 'password';
            this.textContent = isPassword ? '🔒' : '👁️';
        });
    }

    if (toggleConfPasswordBtn && confPasswordInput) {
        toggleConfPasswordBtn.addEventListener('click', function() {
            const isPassword = confPasswordInput.type === 'password';
            confPasswordInput.type = isPassword ? 'text' : 'password';
            this.textContent = isPassword ? '🔒' : '👁️';
        });
    }

    // Configurar input de placa
    const placaInput = document.getElementById('placa');
    if (placaInput) {
        placaInput.addEventListener('input', function() {
            this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
        });
    }

    // Configurar envío del formulario
    loginForm.addEventListener('submit', function(event) {
        event.preventDefault();
        clearMessages();
        
        if (validateForm()) {
            sendLoginRequest();
        } else {
            showError('Por favor corrige los errores en el formulario');
        }
    });
});

function validateForm() {
    let isValid = true;
    
    // Limpiar errores previos
    document.querySelectorAll('.error-message').forEach(el => el.style.display = 'none');
    document.querySelectorAll('.error').forEach(el => el.classList.remove('error'));
    
    // Validar campos requeridos
    const requiredFields = document.querySelectorAll('#loginForm [required]');
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            showFieldError(field, 'Este campo es obligatorio');
            isValid = false;
        }
    });
    
    // Validaciones específicas
    const email = document.getElementById('email');
    const placa = document.getElementById('placa');
    const password = document.getElementById('password');
    const confPassword = document.getElementById('confipassword');
    
    if (email.value && !isValidEmail(email.value)) {
        showFieldError(email, 'Formato de correo electrónico no válido');
        isValid = false;
    }
    
    if (placa.value && !isValidPlaca(placa.value)) {
        showFieldError(placa, 'Formato de placa no válido. Debe ser ABC1234');
        isValid = false;
    }
    
    if (password.value && password.value.length < 8) {
        showFieldError(password, 'La contraseña debe tener al menos 8 caracteres');
        isValid = false;
    }
    
    if (password.value && confPassword.value && password.value !== confPassword.value) {
        showFieldError(confPassword, 'Las contraseñas no coinciden');
        isValid = false;
    }
    
    return isValid;
}

function isValidEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

function isValidPlaca(placa) {
    const re = /^[A-Z]{3}[0-9]{4}$/;
    return re.test(placa);
}

function showFieldError(field, message) {
    const errorId = field.id + 'Error';
    const errorElement = document.getElementById(errorId);
    
    field.classList.add('error');
    if (errorElement) {
        errorElement.textContent = message;
        errorElement.style.display = 'block';
    }
}

function clearMessages() {
    const serverError = document.getElementById('serverError');
    const successMessage = document.getElementById('successMessage');
    
    if (serverError) serverError.style.display = 'none';
    if (successMessage) successMessage.style.display = 'none';
}

function sendLoginRequest() {
    const form = document.getElementById('loginForm');
    const formData = new FormData(form);
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    
    submitBtn.textContent = 'Iniciando sesión...';
    submitBtn.disabled = true;
    
    console.log('📤 Enviando datos del formulario:');
    for (let [key, value] of formData.entries()) {
        console.log(`  ${key}: ${value}`);
    }
    
    const url = 'http://localhost/GitHub/SaltoPD/Back-end/InicioSesion.php';
    
    fetch(url, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        console.log('📋 Respuesta completa:', data);
        
        if (data.success) {
            showSuccess(data.message);
            setTimeout(() => {
                window.location.href = data.redirect;
            }, 1500);
        } else {
            let errorMessage = data.message;
            
            if (data.errors) {
                for (const fieldName in data.errors) {
                    const field = document.getElementsByName(fieldName)[0];
                    if (field) showFieldError(field, data.errors[fieldName]);
                }
                errorMessage = 'Por favor corrige los errores en el formulario';
            }
            
            // Mostrar información de debug detallada
            if (data.debug_info) {
                console.log('🔍 Información de debug:', data.debug_info);
                
                // Construir mensaje de ayuda
                let helpMessage = '\n\n🔍 Información para diagnóstico:';
                
                if (data.debug_info.usuarios_con_email && data.debug_info.usuarios_con_email.length > 0) {
                    helpMessage += '\n📧 Usuarios con ese email: ' + 
                        data.debug_info.usuarios_con_email.map(u => `${u.correo} (${u.Num_Placa}, ${u.rol})`).join(', ');
                }
                
                if (data.debug_info.usuarios_con_placa && data.debug_info.usuarios_con_placa.length > 0) {
                    helpMessage += '\n🚔 Usuarios con esa placa: ' + 
                        data.debug_info.usuarios_con_placa.map(u => `${u.correo} (${u.Num_Placa}, ${u.rol})`).join(', ');
                }
                
                if (data.debug_info.usuarios_con_rol && data.debug_info.usuarios_con_rol.length > 0) {
                    helpMessage += '\n👮 Usuarios con ese rol: ' + 
                        data.debug_info.usuarios_con_rol.map(u => `${u.correo} (${u.Num_Placa}, ${u.rol})`).join(', ');
                }
                
                errorMessage += helpMessage;
            }
            
            showError(errorMessage);
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }
    })
    .catch(error => {
        console.error('❌ Error:', error);
        showError('Error de conexión con el servidor: ' + error.message);
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
    });
}

function showError(message) {
    const serverError = document.getElementById('serverError');
    if (serverError) {
        serverError.textContent = message;
        serverError.style.display = 'block';
        serverError.style.color = '#e74c3c';
        serverError.style.whiteSpace = 'pre-line';
        serverError.style.textAlign = 'left';
    }
}

function showSuccess(message) {
    const successMessage = document.getElementById('successMessage');
    if (successMessage) {
        successMessage.textContent = message;
        successMessage.style.display = 'block';
        successMessage.style.color = '#27ae60';
    }
}