document.addEventListener('DOMContentLoaded', function() {
    const registroForm = document.getElementById('registroForm');
    
    if (registroForm) {
        registroForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Validaciones
            let isValid = true;
            let errorMessage = '';
            
            // Validar nombre
            const nombre = document.getElementById('nombre').value.trim();
            if (nombre === '') {
                isValid = false;
                errorMessage += 'El nombre es obligatorio.\n';
            }
            
            // Validar apellido
            const apellido = document.getElementById('apellido').value.trim();
            if (apellido === '') {
                isValid = false;
                errorMessage += 'El apellido es obligatorio.\n';
            }
            
            // Validar placa
            const placa = document.getElementById('placa').value.trim();
            const placaPattern = /^[A-Z]{3}[0-9]{4}$/;
            if (!placaPattern.test(placa)) {
                isValid = false;
                errorMessage += 'La placa debe tener el formato ABC1234.\n';
            }
            
            // Validar correo
            const correo = document.getElementById('correo').value.trim();
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailPattern.test(correo)) {
                isValid = false;
                errorMessage += 'Ingrese un correo electrónico válido.\n';
            }
            
            // Validar contraseña
            const contrasena = document.getElementById('contrasena').value;
            if (contrasena.length < 8) {
                isValid = false;
                errorMessage += 'La contraseña debe tener al menos 8 caracteres.\n';
            }
            
            if (!isValid) {
                alert('Por favor, corrija los siguientes errores:\n\n' + errorMessage);
                return false;
            }
            
            // Si todas las validaciones pasan, enviar el formulario
            this.submit();
        });
    }
});