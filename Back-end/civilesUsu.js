const API_URL = "http://localhost/SaltoPD/Back-end/CivilesUsu.php";

// Variable global para almacenar todos los civiles
let todosLosCiviles = [];

// Variable para controlar el debounce de búsqueda
let timeoutBusqueda = null;

// Variable para almacenar el civil seleccionado
let civilSeleccionado = null;

// 📌 Función para actualizar el contador de civiles
function actualizarContador(cantidad) {
    const totalElement = document.getElementById("totalCiviles");
    if (totalElement) {
        totalElement.textContent = `Total: ${cantidad} civil${cantidad !== 1 ? 'es' : ''}`;
    }
}

// 📌 Función para cargar civiles
async function cargarCiviles() {
    try {
        console.log("Intentando cargar civiles desde:", API_URL);
        
        const response = await fetch(`${API_URL}?action=read`);
        
        console.log("Response status:", response.status);
        console.log("Response ok:", response.ok);
        
        if (!response.ok) {
            throw new Error(`Error HTTP: ${response.status} - ${response.statusText}`);
        }

        const data = await response.json();
        console.log("Datos recibidos:", data);
        
        const contenedor = document.querySelector(".civiles-list");

        if (!contenedor) {
            console.error("No se encontró el contenedor .civiles-list en el HTML");
            return;
        }

        contenedor.innerHTML = ""; // Limpiar contenido previo

        if (data.success && Array.isArray(data.data) && data.data.length > 0) {
            // Guardar los civiles en la variable global
            todosLosCiviles = data.data;
            mostrarCiviles(todosLosCiviles);
            actualizarContador(todosLosCiviles.length);
        } else {
            contenedor.innerHTML = "<p>No hay civiles registrados.</p>";
            todosLosCiviles = [];
            actualizarContador(0);
        }
    } catch (error) {
        console.error("Error cargando civiles:", error);
        
        // Mostrar error más amigable al usuario
        const contenedor = document.querySelector(".civiles-list");
        if (contenedor) {
            contenedor.innerHTML = `<p style="color: red;">Error: No se pueden cargar los datos. ${error.message}</p>`;
        }
        actualizarContador(0);
    }
}

// 📌 Función para mostrar civiles en el DOM
function mostrarCiviles(civiles) {
    const contenedor = document.querySelector(".civiles-list");
    
    if (!contenedor) return;
    
    contenedor.innerHTML = ""; // Limpiar contenido previo
    
    if (civiles.length === 0) {
        contenedor.innerHTML = "<p>No se encontraron civiles con ese criterio.</p>";
        return;
    }
    
    civiles.forEach(civil => {
        const div = document.createElement("div");
        div.className = "civil-item";
        div.innerHTML = `
            <div class="civil-info">
                <strong>${civil.nombre}</strong> - DNI: ${civil.dni}
            </div>
            <div class="civil-actions">
                <button class="btn-ver-delitos" onclick="verDelitos(${civil.id}, '${civil.nombre.replace(/'/g, "\\'")}')">
                    📋 Ver Delitos
                </button>
            </div>
        `;
        contenedor.appendChild(div);
    });
}

// 📌 Función para buscar civiles (usando el backend)
async function buscarCiviles() {
    const searchInput = document.getElementById("searchInput");
    
    if (!searchInput) {
        console.error("No se encontró el campo de búsqueda");
        return;
    }
    
    const termino = searchInput.value.trim();
    
    // Si el término está vacío, cargar todos los civiles
    if (termino === "") {
        cargarCiviles();
        return;
    }
    
    // Cancelar búsqueda anterior si existe (debounce)
    if (timeoutBusqueda) {
        clearTimeout(timeoutBusqueda);
    }
    
    // Esperar 300ms antes de hacer la búsqueda
    timeoutBusqueda = setTimeout(async () => {
        try {
            console.log(`🔍 Buscando: "${termino}"`);
            
            const response = await fetch(`${API_URL}?action=search&q=${encodeURIComponent(termino)}`);
            
            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status}`);
            }
            
            const data = await response.json();
            console.log("Resultados de búsqueda:", data);
            
            if (data.success && Array.isArray(data.data)) {
                mostrarCiviles(data.data);
                actualizarContador(data.data.length);
                console.log(`✅ Se encontraron ${data.data.length} resultado(s)`);
            } else {
                mostrarCiviles([]);
                actualizarContador(0);
            }
            
        } catch (error) {
            console.error("Error en la búsqueda:", error);
            const contenedor = document.querySelector(".civiles-list");
            if (contenedor) {
                contenedor.innerHTML = `<p style="color: red;">Error al buscar: ${error.message}</p>`;
            }
        }
    }, 300); // Espera 300ms después de que el usuario deje de escribir
}

// 📌 Función para limpiar búsqueda
function limpiarBusqueda() {
    const searchInput = document.getElementById("searchInput");
    
    if (searchInput) {
        searchInput.value = "";
        cargarCiviles(); // Recargar todos los civiles
    }
}

// 📌 Función para exportar datos (CSV)
function exportarDatos() {
    if (todosLosCiviles.length === 0) {
        alert("No hay datos para exportar");
        return;
    }
    
    // Crear contenido CSV
    let csvContent = "data:text/csv;charset=utf-8,";
    csvContent += "ID,Nombre,DNI\n"; // Encabezados
    
    todosLosCiviles.forEach(civil => {
        csvContent += `${civil.id || ''},${civil.nombre},${civil.dni}\n`;
    });
    
    // Crear enlace de descarga
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", `civiles_${new Date().toISOString().split('T')[0]}.csv`);
    document.body.appendChild(link);
    
    link.click();
    document.body.removeChild(link);
    
    console.log("✅ Datos exportados exitosamente");
    alert("Datos exportados correctamente");
}

// ==================== GESTIÓN DE DELITOS (SOLO LECTURA) ====================

// 📌 Función para ver delitos de un civil
async function verDelitos(civilId, nombreCivil) {
    civilSeleccionado = { id: civilId, nombre: nombreCivil };
    
    try {
        console.log(`📋 Cargando delitos del civil ID: ${civilId}`);
        
        const response = await fetch(`${API_URL}?action=read_delitos&civil_id=${civilId}`);
        
        console.log("Response status (read_delitos):", response.status);
        
        if (!response.ok) {
            throw new Error(`Error HTTP: ${response.status}`);
        }
        
        const data = await response.json();
        console.log("Delitos recibidos:", data);
        
        if (data.success) {
            mostrarModalDelitos(data.data);
        } else {
            alert("Error al cargar delitos: " + data.message);
        }
    } catch (error) {
        console.error("Error al cargar delitos:", error);
        alert("Error al cargar delitos: " + error.message);
    }
}

// 📌 Función para mostrar modal con delitos (SOLO LECTURA)
function mostrarModalDelitos(delitos) {
    // Crear modal si no existe
    let modal = document.getElementById("modalDelitos");
    
    if (!modal) {
        modal = document.createElement("div");
        modal.id = "modalDelitos";
        modal.className = "modal-delitos";
        document.body.appendChild(modal);
    }
    
    let delitosHTML = '';
    
    if (delitos.length === 0) {
        delitosHTML = '<p class="no-delitos">No hay delitos registrados para este civil.</p>';
    } else {
        delitos.forEach(delito => {
            delitosHTML += `
                <div class="delito-card">
                    <div class="delito-header">
                        <strong>${delito.tipo_delito}</strong>
                        <span class="delito-fecha">${formatearFecha(delito.fecha_delito)}</span>
                    </div>
                    <div class="delito-descripcion">
                        ${delito.descripcion || 'Sin descripción'}
                    </div>
                </div>
            `;
        });
    }
    
    modal.innerHTML = `
        <div class="modal-content">
            <div class="modal-header">
                <h2>Delitos de ${civilSeleccionado.nombre}</h2>
                <button class="btn-close" onclick="cerrarModalDelitos()">✕</button>
            </div>
            
            <div class="modal-body">
                <div class="delitos-lista">
                    <h3>Delitos Registrados</h3>
                    ${delitosHTML}
                </div>
            </div>
        </div>
    `;
    
    modal.style.display = "flex";
    
    // Cerrar modal al hacer clic fuera del contenido
    modal.addEventListener("click", (e) => {
        if (e.target === modal) {
            cerrarModalDelitos();
        }
    });
}

// 📌 Función para cerrar modal
function cerrarModalDelitos() {
    const modal = document.getElementById("modalDelitos");
    if (modal) {
        modal.style.display = "none";
        civilSeleccionado = null;
    }
}

// 📌 Función para formatear fecha
function formatearFecha(fecha) {
    const opciones = { year: 'numeric', month: 'long', day: 'numeric' };
    return new Date(fecha + 'T00:00:00').toLocaleDateString('es-ES', opciones);
}

// ==================== FIN GESTIÓN DE DELITOS ====================

// 📌 Función para aplicar estilos a los botones y modal
function aplicarEstilosBotones() {
    const estilos = `
        /* Estilos para botones del header */
        .reload-btn, .export-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
            margin-left: 10px;
        }

        .reload-btn:hover, .export-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.5);
        }

        .reload-btn:active, .export-btn:active {
            transform: translateY(0);
            box-shadow: 0 2px 6px rgba(102, 126, 234, 0.3);
        }

        .export-btn {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            box-shadow: 0 2px 8px rgba(17, 153, 142, 0.3);
        }

        .export-btn:hover {
            box-shadow: 0 4px 12px rgba(17, 153, 142, 0.5);
        }

        .export-btn:active {
            box-shadow: 0 2px 6px rgba(17, 153, 142, 0.3);
        }

        /* Estilos para el input de búsqueda */
        .search-input {
            padding: 10px 15px;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
            width: 300px;
        }

        .search-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        /* Estilos para el botón de limpiar búsqueda */
        .clear-search-btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1.1rem;
            font-weight: bold;
            transition: all 0.3s ease;
            margin-left: 5px;
        }

        .clear-search-btn:hover {
            background: #c82333;
            transform: scale(1.1);
        }

        /* Contenedor de búsqueda */
        .search-container {
            display: flex;
            align-items: center;
        }

        /* ESTILOS DEL MODAL DE DELITOS */
        .modal-delitos {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content {
            background: white;
            border-radius: 12px;
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            border-bottom: 2px solid #f0f0f0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 12px 12px 0 0;
        }

        .modal-header h2 {
            margin: 0;
            font-size: 1.5rem;
        }

        .btn-close {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 5px 12px;
            border-radius: 50%;
            transition: all 0.3s ease;
        }

        .btn-close:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: rotate(90deg);
        }

        .modal-body {
            padding: 20px;
        }

        .delitos-lista h3 {
            color: #333;
            margin-bottom: 15px;
        }

        .no-delitos {
            text-align: center;
            color: #999;
            padding: 20px;
            font-style: italic;
        }

        .delito-card {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }

        .delito-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }

        .delito-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .delito-header strong {
            color: #333;
            font-size: 1.1rem;
        }

        .delito-fecha {
            color: #666;
            font-size: 0.9rem;
            background: #f0f0f0;
            padding: 4px 10px;
            border-radius: 12px;
        }

        .delito-descripcion {
            color: #555;
            line-height: 1.5;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .modal-content {
                width: 95%;
                max-height: 95vh;
            }

            .modal-header h2 {
                font-size: 1.2rem;
            }

            .reload-btn, .export-btn {
                padding: 10px 18px;
                font-size: 0.9rem;
                margin-left: 5px;
            }

            .search-input {
                width: 100%;
            }
        }
    `;

    // Crear elemento style y agregarlo al head
    const styleElement = document.createElement('style');
    styleElement.textContent = estilos;
    document.head.appendChild(styleElement);
    
    console.log("✅ Estilos de botones y modal aplicados");
}

// 📌 Función para verificar si el servidor está funcionando
async function verificarServidor() {
    // Verificar si estamos usando file:// protocol
    if (window.location.protocol === 'file:') {
        console.error("❌ ERROR: Estás abriendo el archivo directamente desde el explorador");
        console.error("📋 SOLUCIÓN: Debes usar un servidor web local");
        console.error("🔧 Pasos para solucionarlo:");
        console.error("   1. Asegúrate que XAMPP esté ejecutándose");
        console.error("   2. Accede via: http://localhost/SaltoPD/Front-end/");
        return false;
    }
    
    console.log("🔍 URL actual:", window.location.href);
    console.log("🔍 Intentando conectar a:", API_URL);
    
    try {
        console.log("📋 Intentando acceso directo al PHP...");
        const response = await fetch(API_URL);
        
        console.log("📊 Status:", response.status);
        console.log("📊 Status Text:", response.statusText);
        console.log("📊 OK:", response.ok);
        
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
    console.log("🔍 Ubicación de la página:", window.location.href);
    
    // ========== APLICAR ESTILOS A LOS BOTONES Y MODAL ==========
    aplicarEstilosBotones();
    
    // Verificar que existe el contenedor
    const contenedor = document.querySelector(".civiles-list");
    if (!contenedor) {
        console.error("❌ No se encontró el elemento .civiles-list en el HTML");
    } else {
        console.log("✅ Contenedor .civiles-list encontrado");
    }
    
    // Agregar evento al campo de búsqueda
    const searchInput = document.getElementById("searchInput");
    if (searchInput) {
        console.log("✅ Campo de búsqueda encontrado");
        searchInput.addEventListener("input", buscarCiviles);
    } else {
        console.error("❌ No se encontró el campo de búsqueda #searchInput");
    }
    
    // Agregar evento al botón de limpiar búsqueda
    const clearSearchBtn = document.getElementById("clearSearch");
    if (clearSearchBtn) {
        console.log("✅ Botón de limpiar búsqueda encontrado");
        clearSearchBtn.addEventListener("click", limpiarBusqueda);
    } else {
        console.error("❌ No se encontró el botón #clearSearch");
    }
    
    // Agregar evento al botón de recargar
    const reloadBtn = document.getElementById("reloadButton");
    if (reloadBtn) {
        console.log("✅ Botón de recargar encontrado");
        reloadBtn.addEventListener("click", () => {
            console.log("🔄 Recargando civiles...");
            limpiarBusqueda();
        });
    } else {
        console.error("❌ No se encontró el botón #reloadButton");
    }
    
    // Agregar evento al botón de exportar
    const exportBtn = document.getElementById("exportButton");
    if (exportBtn) {
        console.log("✅ Botón de exportar encontrado");
        exportBtn.addEventListener("click", exportarDatos);
    } else {
        console.log("ℹ️ Botón de exportar no encontrado");
    }
    
    // Cerrar modal con tecla ESC
    document.addEventListener("keydown", (e) => {
        if (e.key === "Escape") {
            cerrarModalDelitos();
        }
    });
    
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
                            <li>Ve a tu navegador y escribe: <code>http://localhost/SaltoPD/Front-end/</code></li>
                            <li>NO abras el archivo directamente con doble click</li>
                        </ol>
                    </div>
                `;
            } else {
                contenedor.innerHTML = '<p style="color: red;">⚠️ No se puede conectar al servidor. Verifique que esté ejecutándose.</p>';
            }
        }
    }
});