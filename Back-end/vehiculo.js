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
                            <div class="vehiculo-acciones">
                                <a href="../Back-end/Vehiculos.php?action=edit&matricula=${vehiculo.Matricula}" 
                                   class="btn" 
                                   style="background: linear-gradient(135deg, #f39c12, #e67e22); padding: 8px 15px; font-size: 14px;"
                                   target="_blank">
                                    ✏️ Editar
                                </a>
                                <a href="javascript:void(0);" 
                                   class="btn" 
                                   style="background: linear-gradient(135deg, #e74c3c, #c0392b); padding: 8px 15px; font-size: 14px;"
                                   onclick="eliminarVehiculo('${vehiculo.Matricula}')">
                                    🗑️ Eliminar
                                </a>
                                <button class="btn btn-info" onclick="mostrarDenuncias('${vehiculo.Matricula}')">
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

// Función para eliminar un vehículo
function eliminarVehiculo(matricula) {
    const confirmar = confirm(`¿Estás seguro de que deseas eliminar el vehículo con matrícula ${matricula}?`);
    if (confirmar) {
        fetch(`../Back-end/Vehiculos.php?action=delete&matricula=${matricula}`, {
            method: 'DELETE'
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('No se pudo eliminar el vehículo');
            }
            alert(`Vehículo con matrícula ${matricula} eliminado exitosamente.`);
            cargarResultados(); // Recargar los resultados después de eliminar el vehículo
        })
        .catch(error => {
            console.error('❌ Error al eliminar el vehículo:', error);
            mostrarNotificacion('Error al eliminar el vehículo', 'error');
        });
    }
}

// =============================================
// GESTIÓN DE DENUNCIAS
// =============================================

// Variables globales para denuncias
let denuncias = [];
let denunciaAEliminar = null;
let vehiculoActual = null;

// Funciones para gestionar denuncias
function mostrarDenuncias(matricula) {
    vehiculoActual = matricula;
    const denunciasContainer = document.getElementById('denuncias-container');
    if (denunciasContainer) {
        denunciasContainer.style.display = 'block';
    }
    
    const vehiculoIdInput = document.getElementById('vehiculo-id');
    if (vehiculoIdInput) {
        vehiculoIdInput.value = matricula;
    }
    
    // Cargar denuncias desde el servidor (en un caso real)
    cargarDenunciasVehiculo(matricula);
}

function cargarDenunciasVehiculo(matricula) {
    // En un caso real, esto sería una llamada a la API
    // Por ahora, simulamos datos o cargamos desde localStorage
    let denunciasVehiculo = [];
    
    try {
        // Intentar cargar denuncias desde localStorage
        const denunciasGuardadas = localStorage.getItem('denuncias_vehiculos');
        if (denunciasGuardadas) {
            denuncias = JSON.parse(denunciasGuardadas);
            denunciasVehiculo = denuncias.filter(d => d.vehiculoId === matricula);
        }
    } catch (error) {
        console.error('Error al cargar denuncias:', error);
        // Si hay error, usar datos de ejemplo
        denunciasVehiculo = obtenerDenunciasEjemplo(matricula);
    }
    
    mostrarListaDenuncias(denunciasVehiculo);
}

function obtenerDenunciasEjemplo(matricula) {
    // Datos de ejemplo para demostración
    const denunciasEjemplo = [
        {
            id: 1,
            vehiculoId: 'ABC123',
            fecha: '2023-05-15',
            tipo: 'Robo',
            descripcion: 'Vehículo reportado como robado en la zona norte de la ciudad.',
            estado: 'En investigación'
        },
        {
            id: 2,
            vehiculoId: 'ABC123',
            fecha: '2023-08-22',
            tipo: 'Infracción',
            descripcion: 'Exceso de velocidad registrado por cámara de tráfico.',
            estado: 'Activa'
        },
        {
            id: 3,
            vehiculoId: 'XYZ789',
            fecha: '2023-10-10',
            tipo: 'Accidente',
            descripcion: 'Colisión lateral en intersección de Av. Principal y Calle Secundaria.',
            estado: 'Resuelta'
        }
    ];
    
    return denunciasEjemplo.filter(d => d.vehiculoId === matricula);
}

function mostrarListaDenuncias(denunciasLista) {
    const lista = document.getElementById('lista-denuncias');
    if (!lista) return;
    
    if (denunciasLista.length === 0) {
        lista.innerHTML = '<div class="sin-denuncias">No hay denuncias registradas para este vehículo.</div>';
        return;
    }
    
    lista.innerHTML = denunciasLista.map(denuncia => `
        <div class="denuncia-item">
            <div class="denuncia-header">
                <div class="denuncia-fecha">${formatearFecha(denuncia.fecha)} - ${denuncia.tipo}</div>
                <div class="denuncia-acciones">
                    <button class="btn btn-primary" onclick="editarDenuncia(${denuncia.id})">
                        <span class="icon-small">✏️</span> Editar
                    </button>
                    <button class="btn btn-danger" onclick="solicitarEliminarDenuncia(${denuncia.id})">
                        <span class="icon-small">🗑️</span> Eliminar
                    </button>
                </div>
            </div>
            <div class="denuncia-descripcion">
                <strong>Descripción:</strong> ${denuncia.descripcion}<br>
                <strong>Estado:</strong> <span class="estado-${denuncia.estado.toLowerCase().replace(' ', '-')}">${denuncia.estado}</span>
            </div>
        </div>
    `).join('');
}

function mostrarModalAgregar() {
    document.getElementById('modal-titulo').textContent = 'Agregar Denuncia';
    document.getElementById('form-denuncia').reset();
    document.getElementById('denuncia-id').value = '';
    document.getElementById('fecha-denuncia').valueAsDate = new Date();
    document.getElementById('modal-denuncia').style.display = 'flex';
}

function editarDenuncia(id) {
    const denuncia = denuncias.find(d => d.id === id);
    if (!denuncia) return;
    
    document.getElementById('modal-titulo').textContent = 'Editar Denuncia';
    document.getElementById('denuncia-id').value = denuncia.id;
    document.getElementById('fecha-denuncia').value = denuncia.fecha;
    document.getElementById('tipo-denuncia').value = denuncia.tipo;
    document.getElementById('descripcion-denuncia').value = denuncia.descripcion;
    document.getElementById('estado-denuncia').value = denuncia.estado;
    
    document.getElementById('modal-denuncia').style.display = 'flex';
}

function cerrarModal() {
    document.getElementById('modal-denuncia').style.display = 'none';
}

function guardarDenuncia(e) {
    e.preventDefault();
    
    const id = document.getElementById('denuncia-id').value;
    const fecha = document.getElementById('fecha-denuncia').value;
    const tipo = document.getElementById('tipo-denuncia').value;
    const descripcion = document.getElementById('descripcion-denuncia').value;
    const estado = document.getElementById('estado-denuncia').value;
    const vehiculoId = document.getElementById('vehiculo-id').value;
    
    if (!fecha || !tipo || !descripcion || !estado) {
        mostrarNotificacion('Por favor, complete todos los campos requeridos', 'error');
        return;
    }
    
    if (id) {
        // Editar denuncia existente
        const index = denuncias.findIndex(d => d.id == id);
        if (index !== -1) {
            denuncias[index] = {
                ...denuncias[index],
                fecha,
                tipo,
                descripcion,
                estado
            };
        }
    } else {
        // Agregar nueva denuncia
        const nuevaId = denuncias.length > 0 ? Math.max(...denuncias.map(d => d.id)) + 1 : 1;
        denuncias.push({
            id: nuevaId,
            vehiculoId: vehiculoId,
            fecha,
            tipo,
            descripcion,
            estado
        });
    }
    
    // Guardar en localStorage (en un caso real, sería una llamada a la API)
    guardarDenunciasEnStorage();
    
    // Actualizar la lista
    mostrarDenuncias(vehiculoId);
    cerrarModal();
    
    console.log('Denuncia guardada:', id ? 'editada' : 'agregada');
    mostrarNotificacion(`Denuncia ${id ? 'editada' : 'agregada'} correctamente`, 'success');
}

function guardarDenunciasEnStorage() {
    try {
        localStorage.setItem('denuncias_vehiculos', JSON.stringify(denuncias));
    } catch (error) {
        console.error('Error al guardar denuncias en localStorage:', error);
        mostrarNotificacion('Error al guardar los datos localmente', 'error');
    }
}

function solicitarEliminarDenuncia(id) {
    denunciaAEliminar = id;
    document.getElementById('modal-confirmacion').style.display = 'flex';
}

function cerrarConfirmacion() {
    document.getElementById('modal-confirmacion').style.display = 'none';
    denunciaAEliminar = null;
}

function eliminarDenuncia() {
    if (denunciaAEliminar) {
        const index = denuncias.findIndex(d => d.id === denunciaAEliminar);
        if (index !== -1) {
            denuncias.splice(index, 1);
            
            // Guardar cambios en localStorage
            guardarDenunciasEnStorage();
            
            // Actualizar la lista
            mostrarDenuncias(vehiculoActual);
            
            console.log('Denuncia eliminada:', denunciaAEliminar);
            mostrarNotificacion('Denuncia eliminada correctamente', 'success');
        }
    }
    
    cerrarConfirmacion();
}

// =============================================
// FUNCIONES AUXILIARES
// =============================================

function formatearFecha(fechaStr) {
    const opciones = { year: 'numeric', month: 'long', day: 'numeric' };
    return new Date(fechaStr).toLocaleDateString('es-ES', opciones);
}

function inicializarDenuncias() {
    // Cargar denuncias existentes desde localStorage
    try {
        const denunciasGuardadas = localStorage.getItem('denuncias_vehiculos');
        if (denunciasGuardadas) {
            denuncias = JSON.parse(denunciasGuardadas);
        } else {
            // Si no hay datos, inicializar con datos de ejemplo
            denuncias = [
                {
                    id: 1,
                    vehiculoId: 'ABC123',
                    fecha: '2023-05-15',
                    tipo: 'Robo',
                    descripcion: 'Vehículo reportado como robado en la zona norte de la ciudad.',
                    estado: 'En investigación'
                },
                {
                    id: 2,
                    vehiculoId: 'ABC123',
                    fecha: '2023-08-22',
                    tipo: 'Infracción',
                    descripcion: 'Exceso de velocidad registrado por cámara de tráfico.',
                    estado: 'Activa'
                },
                {
                    id: 3,
                    vehiculoId: 'XYZ789',
                    fecha: '2023-10-10',
                    tipo: 'Accidente',
                    descripcion: 'Colisión lateral en intersección de Av. Principal y Calle Secundaria.',
                    estado: 'Resuelta'
                }
            ];
            guardarDenunciasEnStorage();
        }
    } catch (error) {
        console.error('Error al inicializar denuncias:', error);
        denuncias = [];
    }
}

// =============================================
// EVENT LISTENERS Y INICIALIZACIÓN
// =============================================

// Cargar todos los vehículos al iniciar
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 DOM cargado, iniciando aplicación...');
    
    // Inicializar datos
    cargarResultados();
    inicializarDenuncias();
    
    // Configurar eventos de denuncias
    const agregarDenunciaBtn = document.getElementById('agregar-denuncia');
    if (agregarDenunciaBtn) {
        agregarDenunciaBtn.addEventListener('click', mostrarModalAgregar);
    }
    
    const cerrarModalBtn = document.getElementById('cerrar-modal');
    if (cerrarModalBtn) {
        cerrarModalBtn.addEventListener('click', cerrarModal);
    }
    
    const cancelarDenunciaBtn = document.getElementById('cancelar-denuncia');
    if (cancelarDenunciaBtn) {
        cancelarDenunciaBtn.addEventListener('click', cerrarModal);
    }
    
    const formDenuncia = document.getElementById('form-denuncia');
    if (formDenuncia) {
        formDenuncia.addEventListener('submit', guardarDenuncia);
    }
    
    const cerrarConfirmacionBtn = document.getElementById('cerrar-confirmacion');
    if (cerrarConfirmacionBtn) {
        cerrarConfirmacionBtn.addEventListener('click', cerrarConfirmacion);
    }
    
    const cancelarEliminarBtn = document.getElementById('cancelar-eliminar');
    if (cancelarEliminarBtn) {
        cancelarEliminarBtn.addEventListener('click', cerrarConfirmacion);
    }
    
    const confirmarEliminarBtn = document.getElementById('confirmar-eliminar');
    if (confirmarEliminarBtn) {
        confirmarEliminarBtn.addEventListener('click', eliminarDenuncia);
    }
    
    // Cerrar modal al hacer clic fuera del contenido
    const modalDenuncia = document.getElementById('modal-denuncia');
    if (modalDenuncia) {
        modalDenuncia.addEventListener('click', function(e) {
            if (e.target === this) cerrarModal();
        });
    }
    
    const modalConfirmacion = document.getElementById('modal-confirmacion');
    if (modalConfirmacion) {
        modalConfirmacion.addEventListener('click', function(e) {
            if (e.target === this) cerrarConfirmacion();
        });
    }
    
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
        background: ${tipo === 'error' ? '#e74c3c' : tipo === 'success' ? '#27ae60' : '#3498db'};
        color: white;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        z-index: 1000;
        font-weight: bold;
        transition: transform 0.3s ease;
        transform: translateX(100%);
    `;
    notification.textContent = mensaje;
    document.body.appendChild(notification);
    
    // Animación de entrada
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
    }, 10);
    
    setTimeout(() => {
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 3000);
}
