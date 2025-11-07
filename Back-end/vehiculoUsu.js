// Función para cargar resultados de búsqueda
function cargarResultados(busqueda = '') {
    console.log('🔍 Buscando vehículos...', busqueda);
    
    const contenedor = document.getElementById('resultados-vehiculos');
    if (!contenedor) {
        console.error('❌ No se encontró el contenedor resultados-vehiculos');
        return;
    }

    // Mostrar loading
    contenedor.innerHTML = '<div class="loading">🔄 Buscando vehículos...</div>';
    
    // Ocultar sección de denuncias mientras se carga
    const denunciasContainer = document.getElementById('denuncias-container');
    if (denunciasContainer) {
        denunciasContainer.style.display = 'none';
    }
    
    // Ruta CORRECTA al PHP en Back-end
    fetch('../Back-end/Vehiculos.php?action=search')
    .then(response => {
        console.log('📡 Respuesta del servidor:', response.status);
        if (!response.ok) {
            throw new Error(`Error HTTP: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('✅ Datos recibidos:', data);
        
        contenedor.innerHTML = '';

        if (data && data.length > 0) {
            // Filtrar resultados si hay búsqueda
            const resultadosFiltrados = busqueda ? 
                data.filter(vehiculo => {
                    const searchTerm = busqueda.toLowerCase();
                    return (
                        (vehiculo.Matricula && vehiculo.Matricula.toLowerCase().includes(searchTerm)) ||
                        (vehiculo.Marca && vehiculo.Marca.toLowerCase().includes(searchTerm)) ||
                        (vehiculo.Modelo && vehiculo.Modelo.toLowerCase().includes(searchTerm)) ||
                        (vehiculo.Tipo_Vehiculo && vehiculo.Tipo_Vehiculo.toLowerCase().includes(searchTerm))
                    );
                }) : data;

            if (resultadosFiltrados.length > 0) {
                resultadosFiltrados.forEach(vehiculo => {
                    const articulo = document.createElement('article');
                    articulo.className = 'vehiculo-card';
                    articulo.innerHTML = `
                        <img src="Vehiculo.png" alt="Icono de vehículo" class="vehiculo-card__foto" />
                        <div class="vehiculo-card__info">
                            <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 10px;">
                                <span class="vehiculo-card__marca">${vehiculo.Marca || 'N/A'}</span>
                                <span class="vehiculo-card__modelo">${vehiculo.Modelo || 'N/A'}</span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                                <span class="vehiculo-card__matricula">${vehiculo.Matricula || 'N/A'}</span>
                                <span class="vehiculo-card__tipo">${vehiculo.Tipo_Vehiculo || 'N/A'}</span>
                            </div>
                            <input type="text" placeholder="Agregar observación sobre este vehículo..." class="vehiculo-card__observacion" />
                            <div class="vehiculo-acciones">
                                <button class="btn btn-primary" onclick="mostrarDenuncias('${vehiculo.Matricula.replace(/'/g, "\\'")}')" type="button">
                                    📋 Ver Denuncias
                                </button>
                            </div>
                        </div>
                    `;
                    contenedor.appendChild(articulo);
                });
                
                // Mostrar contador de resultados
                const resultadoInfo = document.createElement('div');
                resultadoInfo.className = 'results-info';
                resultadoInfo.innerHTML = `📊 Mostrando <strong>${resultadosFiltrados.length}</strong> de <strong>${data.length}</strong> vehículos encontrados`;
                contenedor.appendChild(resultadoInfo);
                
            } else {
                contenedor.innerHTML = `
                    <div class="no-results">
                        <div style="font-size: 4em; margin-bottom: 20px;">🔍</div>
                        <h3>No se encontraron vehículos</h3>
                        <p>No hay vehículos que coincidan con "<strong>${busqueda}</strong>"</p>
                        <div style="margin-top: 20px;">
                            <button onclick="cargarResultados('')" class="btn btn-clear">
                                <span class="icon-large">🔄</span> Ver Todos los Vehículos
                            </button>
                        </div>
                    </div>
                `;
            }
        } else {
            contenedor.innerHTML = `
                <div class="no-results">
                    <div style="font-size: 4em; margin-bottom: 20px;">🚗</div>
                    <h3>No hay vehículos registrados</h3>
                    <p>El sistema no tiene vehículos registrados actualmente.</p>
                </div>
            `;
        }
    })
    .catch(error => {
        console.error('❌ Error completo:', error);
        const contenedor = document.getElementById('resultados-vehiculos');
        if (contenedor) {
            contenedor.innerHTML = `
                <div class="error">
                    <div style="font-size: 4em; margin-bottom: 20px;">⚠️</div>
                    <h3>Error al cargar los datos</h3>
                    <p>${error.message}</p>
                    <p><small>Verifica que el servidor esté funcionando correctamente</small></p>
                    <div style="margin-top: 20px;">
                        <button onclick="cargarResultados()" class="btn">
                            <span class="icon-large">🔄</span> Reintentar
                        </button>
                    </div>
                </div>
            `;
        }
    });
}

// =============================================
// GESTIÓN DE DENUNCIAS - SOLO VISUALIZACIÓN
// =============================================

// Variables globales para denuncias
let vehiculoActual = null;

// Función para mostrar denuncias
function mostrarDenuncias(matricula) {
    console.log('📋 Mostrando denuncias para:', matricula);
    vehiculoActual = matricula;
    
    const denunciasContainer = document.getElementById('denuncias-container');
    if (!denunciasContainer) {
        console.error('❌ No se encontró el contenedor denuncias-container');
        return;
    }
    
    denunciasContainer.style.display = 'block';
    
    // Actualizar título con la matrícula
    const vehiculoInfo = document.getElementById('vehiculo-info');
    if (vehiculoInfo) {
        vehiculoInfo.textContent = `(Matrícula: ${matricula})`;
    }
    
    const vehiculoIdInput = document.getElementById('vehiculo-id');
    if (vehiculoIdInput) {
        vehiculoIdInput.value = matricula;
    }
    
    // Scroll a la sección de denuncias
    denunciasContainer.scrollIntoView({ behavior: 'smooth' });
    
    // Cargar denuncias desde el servidor
    cargarDenunciasVehiculo(matricula);
}

// Cargar denuncias desde el servidor PHP
function cargarDenunciasVehiculo(matricula) {
    console.log('🔄 Cargando denuncias para:', matricula);
    
    const lista = document.getElementById('lista-denuncias');
    if (!lista) {
        console.error('❌ No se encontró el elemento lista-denuncias');
        return;
    }
    
    lista.innerHTML = '<div class="sin-denuncias">🔄 Cargando denuncias...</div>';
    
    fetch(`../Back-end/Vehiculos.php?action=get_denuncias&matricula=${encodeURIComponent(matricula)}`)
        .then(response => {
            console.log('📡 Respuesta del servidor:', response.status);
            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status}`);
            }
            return response.json();
        })
        .then(denuncias => {
            console.log('✅ Denuncias recibidas:', denuncias);
            mostrarListaDenuncias(denuncias);
        })
        .catch(error => {
            console.error('❌ Error al cargar denuncias:', error);
            lista.innerHTML = '<div class="sin-denuncias">❌ Error al cargar denuncias. Verifique la conexión.</div>';
            mostrarNotificacion('Error al cargar denuncias', 'error');
        });
}

// Mostrar lista de denuncias (SOLO LECTURA)
function mostrarListaDenuncias(denuncias) {
    const lista = document.getElementById('lista-denuncias');
    if (!lista) return;
    
    if (!denuncias || denuncias.length === 0 || denuncias.error) {
        lista.innerHTML = '<div class="sin-denuncias">No hay denuncias registradas para este vehículo.</div>';
        return;
    }
    
    lista.innerHTML = denuncias.map(denuncia => `
        <div class="denuncia-item">
            <div class="denuncia-header">
                <div class="denuncia-fecha">${formatearFecha(denuncia.fecha_denuncia)} - ${denuncia.tipo_denuncia}</div>
            </div>
            <div class="denuncia-descripcion">
                <strong>Descripción:</strong> ${denuncia.descripcion}<br>
                <strong>Estado:</strong> 
                <span class="denuncia-estado estado-${denuncia.estado.toLowerCase().replace(' ', '-')}">
                    ${denuncia.estado}
                </span>
            </div>
        </div>
    `).join('');
}

// =============================================
// FUNCIONES AUXILIARES
// =============================================

function formatearFecha(fechaStr) {
    const opciones = { year: 'numeric', month: 'long', day: 'numeric' };
    return new Date(fechaStr + 'T00:00:00').toLocaleDateString('es-ES', opciones);
}

// =============================================
// EVENT LISTENERS Y INICIALIZACIÓN
// =============================================

// Cargar todos los vehículos al iniciar
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 DOM cargado, iniciando aplicación...');
    
    // Inicializar datos
    cargarResultados();
});

// Manejar envío del formulario de búsqueda
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('searchForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const busqueda = document.querySelector('input[name="busqueda"]').value;
            console.log('🔍 Buscando:', busqueda);
            cargarResultados(busqueda);
        });
    }
});

// Botón limpiar búsqueda
document.addEventListener('DOMContentLoaded', function() {
    const clearButton = document.getElementById('clearSearch');
    if (clearButton) {
        clearButton.addEventListener('click', function() {
            const searchInput = document.querySelector('input[name="busqueda"]');
            if (searchInput) {
                searchInput.value = '';
                searchInput.focus();
                cargarResultados('');
                console.log('🧹 Búsqueda limpiada');
            }
        });
    }
});

// Búsqueda en tiempo real con debounce
document.addEventListener('DOMContentLoaded', function() {
    const inputBusqueda = document.querySelector('input[name="busqueda"]');
    if (inputBusqueda) {
        let timeoutId;
        inputBusqueda.addEventListener('input', function(e) {
            const busqueda = e.target.value;
            
            clearTimeout(timeoutId);
            
            timeoutId = setTimeout(() => {
                if (busqueda.length === 0 || busqueda.length >= 2) {
                    console.log('🔍 Búsqueda en tiempo real:', busqueda);
                    cargarResultados(busqueda);
                }
            }, 500);
        });
    }
});

// Efectos de notificación
function mostrarNotificacion(mensaje, tipo = 'info') {
    const notification = document.createElement('div');
    notification.className = 'notification';
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 25px;
        background: ${tipo === 'error' ? '#e74c3c' : tipo === 'success' ? '#27ae60' : '#3498db'};
        color: white;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        z-index: 10000;
        font-weight: bold;
        animation: slideInRight 0.3s ease-out;
    `;
    notification.textContent = mensaje;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOutRight 0.3s ease-out';
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 3000);
}