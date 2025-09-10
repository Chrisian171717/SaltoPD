// registro.js
document.getElementById('registroForm').addEventListener('submit', function (e) {
  e.preventDefault();

  const nombre = document.getElementById('nombre').value.trim();
  const apellido = document.getElementById('apellido').value.trim();
  const placa = document.getElementById('placa').value.trim();
  const correo = document.getElementById('correo').value.trim();
  const contrasena = document.getElementById('contrasena').value;

  const placaRegex = /^[A-Z]{3}[0-9]{4}$/;

  if (!nombre || !apellido || !placa || !correo || !contrasena) {
    alert('Todos los campos son obligatorios.');
    return;
  }

  if (!placaRegex.test(placa)) {
    alert('La placa debe tener el formato ABC1234.');
    return;
  }

  if (contrasena.length < 8) {
    alert('La contraseña debe tener al menos 8 caracteres.');
    return;
  }

  // Aquí podrías enviar los datos al servidor
  alert('Registro exitoso ✅');
});

// login.js
document.getElementById('loginForm').addEventListener('submit', function (e) {
  e.preventDefault();

  const email = document.getElementById('email').value.trim();
  const placa = document.getElementById('placa').value.trim();
  const password = document.getElementById('password').value;
  const confipassword = document.getElementById('confipassword').value;
  const rol = document.getElementById('rolLogin').value;

  const placaRegex = /^[A-Z]{3}[0-9]{4}$/;

  if (!email || !placa || !password || !confipassword || !rol) {
    alert('Todos los campos son obligatorios.');
    return;
  }

  if (!placaRegex.test(placa)) {
    alert('Formato de placa inválido. Usa ABC1234.');
    return;
  }

  if (password.length < 8 || confipassword.length < 8) {
    alert('Las contraseñas deben tener al menos 8 caracteres.');
    return;
  }

  if (password !== confipassword) {
    alert('Las contraseñas no coinciden.');
    return;
  }

  // Aquí podrías hacer una petición al backend para validar el login
  alert(`Bienvenido, ${rol} 👮‍♂️`);
});
