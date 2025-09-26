const API_URL = "http://localhost/GitHub/SaltoPD/Back-end/Civiles.php";

// 📌 Función para cargar civiles
async function cargarCiviles() {
    try {
        console.log("Intentando cargar civiles desde:", API_URL); // Debug
        
        const response = await fetch(`${API_URL}?action=read`);
        
        console.log("Response status:", response.status); // Debug
        console.log("Response ok:", response.ok); // Debug
        
        if (!response.ok) {
            throw new Error(`Error HTTP: ${response.status} - ${response.statusText}`);
        }

        const data = await response.json();
        console.log("Datos recibidos:", data); // Debug
        
        const contenedor = document.querySelector(".civiles-list");

        if (!contenedor) {
            console.error("No se encontró el contenedor .civiles-list en el HTML");
            return;
        }

        contenedor.innerHTML = ""; // Limpiar contenido previo

        if (data.success && Array.isArray(data.data) && data.data.length > 0) {
            data.data.forEach(civil => {
                const div = document.createElement("div");
                div.className = "civil-item";
                div.textContent = `${civil.nombre} - DNI: ${civil.dni}`;
                contenedor.appendChild(div);
            });
        } else {
            contenedor.innerHTML = "<p>No hay civiles registrados.</p>";
        }
    } catch (error) {
        console.error("Error cargando civiles:", error);
        
        // Mostrar error más amigable al usuario
        const contenedor = document.querySelector(".civiles-list");
        if (contenedor) {
            contenedor.innerHTML = `<p style="color: red;">Error: No se pueden cargar los datos. ${error.message}</p>`;
        }
    }
}

// 📌 Función para agregar un civil
async function agregarCivil(e) {
    e.preventDefault();

    const nombre = document.getElementById("nombre").value.trim();
    const dni = document.getElementById("dni").value.trim();

    if (!nombre || !dni) {
        alert("Por favor complete todos los campos");
        return;
    }

    try {
        console.log("Enviando datos:", { nombre, dni }); // Debug
        
        const response = await fetch(API_URL, {
            method: "POST",
            headers: { 
                "Content-Type": "application/x-www-form-urlencoded"
            },
            body: `action=create&nombre=${encodeURIComponent(nombre)}&dni=${encodeURIComponent(dni)}`
        });

        console.log("Response status (POST):", response.status); // Debug

        if (!response.ok) {
            throw new Error(`Error HTTP: ${response.status} - ${response.statusText}`);
        }

        const data = await response.json();
        console.log("Respuesta del servidor:", data); // Debug

        if (data.success) {
            alert("Civil agregado correctamente");
            document.getElementById("addCivilForm").reset();
            cargarCiviles(); // Recargar lista
        } else {
            alert("Error: " + (data.message || "No se pudo agregar el civil"));
        }
    } catch (error) {
        console.error("Error al agregar civil:", error);
        alert("Error de conexión: " + error.message);
    }
}

// 📌 Función para verificar si el servidor está funcionando
async function verificarServidor() {
    // Verificar si estamos usando file:// protocol
    if (window.location.protocol === 'file:') {
        console.error("❌ ERROR: Estás abriendo el archivo directamente desde el explorador");
        console.error("📋 SOLUCIÓN: Debes usar un servidor web local");
        console.error("🔧 Pasos para solucionarlo:");
        console.error("   1. Asegúrate que XAMPP esté ejecutándose");
        console.error("   2. Accede via: http://localhost/GitHub/SaltoPD/Front-end/");
        return false;
    }
    
    // Mostrar información de debug
    console.log("🔍 URL actual:", window.location.href);
    console.log("🔍 Intentando conectar a:", API_URL);
    console.log("🔍 URL completa:", new URL(API_URL, window.location.href).href);
    
    // Primero intentemos con un GET simple para ver si el archivo existe
    try {
        console.log("📋 Intentando acceso directo al PHP...");
        const response = await fetch(API_URL);
        
        console.log("📊 Status:", response.status);
        console.log("📊 Status Text:", response.statusText);
        console.log("📊 OK:", response.ok);
        
        if (response.status === 404) {
            console.error("❌ Error 404: Archivo no encontrado");
            console.error("🔍 Verificaciones:");
            console.error("   ✓ URL intentada:", new URL(API_URL, window.location.href).href);
            console.error("   ✓ ¿Existe Civiles.php en la misma carpeta?");
            console.error("   ✓ ¿XAMPP Apache está ejecutándose?");
            console.error("   ✓ ¿El nombre tiene exactamente esa capitalización?");
            return false;
        }
        
        if (response.status === 500) {
            console.error("❌ Error 500: Error interno del servidor PHP");
            console.error("🔍 Posibles causas:");
            console.error("   ✓ Error de sintaxis en Civiles.php");
            console.error("   ✓ Error de conexión a base de datos");
            console.error("   ✓ Revisa los logs de error de Apache");
            return false;
        }
        
        // Intentar leer la respuesta
        const text = await response.text();
        console.log("📄 Respuesta del servidor:", text.substring(0, 200) + (text.length > 200 ? "..." : ""));
        
        if (response.ok) {
            console.log("✅ Archivo PHP encontrado y respondiendo");
            return true;
        } else {
            console.error("❌ Respuesta no exitosa:", response.status, response.statusText);
            return false;
        }
        
    } catch (error) {
        console.error("❌ Error de conexión:", error.message);
        console.error("🔍 Verificaciones:");
        console.error("   ✓ ¿XAMPP está ejecutándose?");
        console.error("   ✓ ¿Apache está iniciado (luz verde)?");
        console.error("   ✓ ¿Puerto 80 está libre?");
        return false;
    }
}

// 📌 Eventos
document.addEventListener("DOMContentLoaded", async () => {
    console.log("DOM cargado, iniciando verificaciones...");
    console.log("🔍 Ruta del script actual:", document.currentScript?.src || "No disponible");
    console.log("🔍 Ubicación de la página:", window.location.href);
    
    // Verificar que existe el contenedor
    const contenedor = document.querySelector(".civiles-list");
    if (!contenedor) {
        console.error("❌ No se encontró el elemento .civiles-list en el HTML");
    } else {
        console.log("✅ Contenedor .civiles-list encontrado");
    }
    
    // Verificar que existe el formulario
    const form = document.getElementById("addCivilForm");
    if (!form) {
        console.error("❌ No se encontró el formulario #addCivilForm");
    } else {
        console.log("✅ Formulario encontrado");
        form.addEventListener("submit", agregarCivil);
    }
    
    // Verificar servidor antes de cargar datos
    const servidorOK = await verificarServidor();
    if (servidorOK) {
        cargarCiviles();
    } else {
        const contenedor = document.querySelector(".civiles-list");
        if (contenedor) {
            if (window.location.protocol === 'file:') {
                contenedor.innerHTML = `
                    <div style="background: #ffebee; border: 1px solid #e57373; padding: 15px; border-radius: 5px; color: #c62828;">
                        <h3>⚠️ ERROR DE CONFIGURACIÓN</h3>
                        <p><strong>Problema:</strong> Estás abriendo el archivo directamente desde el explorador</p>
                        <p><strong>Solución:</strong></p>
                        <ol>
                            <li>Asegúrate que XAMPP esté ejecutándose</li>
                            <li>Ve a tu navegador y escribe: <code>http://localhost/GitHub/SaltoPD/Front-end/</code></li>
                            <li>NO abras el archivo directamente con doble click</li>
                        </ol>
                    </div>
                `;
            } else if (window.location.protocol !== 'file:') {
                contenedor.innerHTML = `
                    <div style="background: #fff3e0; border: 1px solid #ff9800; padding: 15px; border-radius: 5px; color: #e65100;">
                        <h3>📁 ERROR 404: Archivo no encontrado</h3>
                        <p><strong>Problema:</strong> No se encuentra el archivo <code>Civiles.php</code></p>
                        <p><strong>URL intentada:</strong> <code>${new URL(API_URL, window.location.href).href}</code></p>
                        <p><strong>Ubicación esperada:</strong> Debe estar en la misma carpeta que este script JS</p>
                        <p><strong>Soluciones posibles:</strong></p>
                        <ol>
                            <li>Verifica que el archivo <code>Civiles.php</code> existe en la carpeta Back-end</li>
                            <li>Verifica que se llame exactamente <code>Civiles.php</code> (con C mayúscula)</li>
                            <li>Verifica que la carpeta se llame <code>Back-end</code> (con B mayúscula)</li>
                            <li>Verifica la estructura: <br/>
                                <code>SaltoPD/</code><br/>
                                <code>├── Front-end/ (civiles.html)</code><br/>
                                <code>└── Back-end/ (civiles.js + Civiles.php)</code>
                            </li>
                        </ol>
                    </div>
                `;
            } else {
                contenedor.innerHTML = '<p style="color: red;">⚠️ No se puede conectar al servidor. Verifique que esté ejecutándose.</p>';
            }
        }
    }
});