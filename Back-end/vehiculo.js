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
                        <img src="Civil.png" alt="Icono de vehículo" class="vehiculo-card__foto" />
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
                            <div style="display: flex; gap: 10px; margin-top: 10px;">
                                <a href="../Back-end/Vehiculos.php?action=edit&matricula=${vehiculo.Matricula}" 
                                   class="btn" 
                                   style="background: linear-gradient(135deg, #f39c12, #e67e22); padding: 8px 15px; font-size: 14px;"
                                   target="_blank">
                                    ✏️ Editar
                                </a>
                                <a href="../Back-end/Vehiculos.php?action=delete&matricula=${vehiculo.Matricula}" 
                                   class="btn" 
                                   style="background: linear-gradient(135deg, #e74c3c, #c0392b); padding: 8px 15px; font-size: 14px;"
                                   target="_blank"
                                   onclick="return confirm('¿Eliminar vehículo ${vehiculo.Matricula}?')">
                                    🗑️ Eliminar
                                </a>
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
                    <div style="margin-top: 20px;">
                        <a href="../Back-end/Vehiculos.php?action=add" class="btn btn-add" target="_blank">
                            <span class="icon-large">➕</span> Agregar Primer Vehículo
                        </a>
                    </div>
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

// Cargar todos los vehículos al iniciar
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 DOM cargado, iniciando aplicación...');
    cargarResultados();
    
    // Agregar efecto hover a todos los botones
    const buttons = document.querySelectorAll('.btn');
    buttons.forEach(button => {
        button.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-3px)';
        });
        button.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
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
            searchInput.value = '';
            searchInput.focus();
            cargarResultados('');
            console.log('🧹 Búsqueda limpiada');
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
            
            // Clear previous timeout
            clearTimeout(timeoutId);
            
            // Set new timeout
            timeoutId = setTimeout(() => {
                if (busqueda.length === 0 || busqueda.length >= 2) {
                    console.log('🔍 Búsqueda en tiempo real:', busqueda);
                    cargarResultados(busqueda);
                }
            }, 500); // 500ms delay
        });
    }
});

// Función para exportar datos (opcional)
function exportarDatos() {
    console.log('📤 Exportando datos...');
    alert('Función de exportación activada - Los datos se prepararán para descarga');
}

// Función para imprimir lista
function imprimirLista() {
    console.log('🖨️ Imprimiendo lista...');
    window.print();
}

// Efectos de notificación
function mostrarNotificacion(mensaje, tipo = 'info') {
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        background: ${tipo === 'error' ? '#e74c3c' : '#27ae60'};
        color: white;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        z-index: 1000;
        font-weight: bold;
    `;
    notification.textContent = mensaje;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}