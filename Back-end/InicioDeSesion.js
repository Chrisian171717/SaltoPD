document.addEventListener('DOMContentLoaded', function() {
            const loginForm = document.getElementById('loginForm');
            const togglePasswordBtn = document.getElementById('togglePassword');
            const toggleConfPasswordBtn = document.getElementById('toggleConfPassword');
            const passwordInput = document.getElementById('password');
            const confPasswordInput = document.getElementById('confipassword');
            const successMessage = document.getElementById('successMessage');
            const serverError = document.getElementById('serverError');
            
            // Función para mostrar/ocultar contraseña
            function setupPasswordToggle(button, input) {
                button.addEventListener('click', function() {
                    if (input.type === 'password') {
                        input.type = 'text';
                        button.textContent = '🔒';
                    } else {
                        input.type = 'password';
                        button.textContent = '👁️';
                    }
                });
            }
            
            setupPasswordToggle(togglePasswordBtn, passwordInput);
            setupPasswordToggle(toggleConfPasswordBtn, confPasswordInput);
            
            // Convertir placa a mayúsculas automáticamente
            const placaInput = document.getElementById('placa');
            placaInput.addEventListener('input', function() {
                this.value = this.value.toUpperCase();
            });
            
            if (loginForm) {
                loginForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    serverError.style.display = 'none';
                    
                    // Validación del lado del cliente
                    if (validateForm()) {
                        // Obtener datos del formulario
                        const formData = new FormData(loginForm);
                        
                        // Simular envío al servidor (aquí iría tu código fetch real)
                        simulateLoginRequest(formData);
                    }
                });
                
                // Validación en tiempo real
                const inputs = loginForm.querySelectorAll('input, select');
                inputs.forEach(input => {
                    input.addEventListener('blur', function() {
                        validateField(this);
                    });
                    
                    input.addEventListener('input', function() {
                        clearError(this);
                    });
                });
            }
            
            function validateForm() {
                let isValid = true;
                const inputs = document.querySelectorAll('#loginForm input, #loginForm select');
                
                inputs.forEach(input => {
                    if (!validateField(input)) {
                        isValid = false;
                    }
                });
                
                // Validar que las contraseñas coincidan
                if (passwordInput.value !== confPasswordInput.value) {
                    showError(confPasswordInput, 'Las contraseñas no coinciden');
                    isValid = false;
                }
                
                return isValid;
            }
            
            function validateField(field) {
                let isValid = true;
                let errorMessage = '';
                
                // Limpiar errores previos
                clearError(field);
                
                // Validar campo requerido
                if (field.hasAttribute('required') && !field.value.trim()) {
                    errorMessage = 'Este campo es obligatorio';
                    isValid = false;
                }
                
                // Validar formato de email
                if (field.type === 'email' && field.value && !isValidEmail(field.value)) {
                    errorMessage = 'Formato de correo electrónico no válido';
                    isValid = false;
                }
                
                // Validar formato de placa
                if (field.id === 'placa' && field.value && !isValidPlaca(field.value)) {
                    errorMessage = 'Formato de placa no válido. Debe ser ABC1234';
                    isValid = false;
                }
                
                // Validar longitud mínima de contraseña
                if ((field.id === 'password' || field.id === 'confipassword') && field.value.length < 8) {
                    errorMessage = 'La contraseña debe tener al menos 8 caracteres';
                    isValid = false;
                }
                
                // Mostrar error si existe
                if (!isValid) {
                    showError(field, errorMessage);
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
            
            function showError(field, message) {
                // Crear elemento de error si no existe
                const errorId = field.id + 'Error';
                const errorElement = document.getElementById(errorId);
                
                // Estilizar campo con error
                field.classList.add('error');
                
                // Mostrar mensaje de error
                if (errorElement) {
                    errorElement.textContent = message;
                    errorElement.style.display = 'block';
                }
            }
            
            function clearError(field) {
                // Quitar clase de error
                field.classList.remove('error');
                
                // Ocultar mensaje de error
                const errorId = field.id + 'Error';
                const errorElement = document.getElementById(errorId);
                if (errorElement) {
                    errorElement.style.display = 'none';
                }
            }
            
            function displayErrors(errors) {
                // Limpiar todos los errores primero
                const allFields = document.querySelectorAll('#loginForm input, #loginForm select');
                allFields.forEach(field => clearError(field));
                
                // Mostrar nuevos errores
                for (const fieldName in errors) {
                    const field = document.getElementsByName(fieldName)[0];
                    if (field) {
                        showError(field, errors[fieldName]);
                    }
                }
            }
            
            // Función para simular la solicitud de inicio de sesión
            function simulateLoginRequest(formData) {
                // Aquí normalmente harías el fetch a tu backend
                // Por ahora simulamos una respuesta después de 1.5 segundos
                
                // Mostrar estado de carga
                const submitBtn = loginForm.querySelector('button[type="submit"]');
                const originalText = submitBtn.textContent;
                submitBtn.textContent = 'Iniciando sesión...';
                submitBtn.disabled = true;
                
                // Simular retraso de red
                setTimeout(() => {
                    // Simular una respuesta exitosa (en un caso real, esto vendría del servidor)
                    const email = formData.get('email');
                    
                    // En un caso real, verificarías la respuesta del servidor
                    if (email && email.includes('@')) {
                        // Éxito
                        successMessage.style.display = 'block';
                        
                        // Simular redirección después de 2 segundos
                        setTimeout(() => {
                            successMessage.textContent = '¡Redirigiendo al dashboard!';
                            // En un caso real: window.location.href = data.redirect;
                        }, 2000);
                    } else {
                        // Error
                        serverError.style.display = 'block';
                        serverError.textContent = 'Error: Credenciales incorrectas';
                        submitBtn.disabled = false;
                        submitBtn.textContent = originalText;
                    }
                }, 1500);
            }
            
            // Para propósitos de demostración - simular un inicio de sesión exitoso con datos específicos
            window.demoLogin = function() {
                document.getElementById('email').value = 'policia@ejemplo.com';
                document.getElementById('placa').value = 'ABC1234';
                document.getElementById('password').value = 'password123';
                document.getElementById('confipassword').value = 'password123';
                document.getElementById('rolLogin').value = 'policia';
                
                loginForm.dispatchEvent(new Event('submit'));
            };
        });