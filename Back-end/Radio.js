// Función para limpiar formularios después de guardar
function limpiarFormularios() {
    // Limpiar formulario de unidades
    document.getElementById('id_unidad').value = '';
    document.getElementById('codigo_unidad').value = '';
    document.getElementById('tipo_unidad').value = '';
    document.getElementById('estado_unidad').value = '';
    document.getElementById('oficial_nombre_unidad').value = '';
    document.getElementById('oficial_rango_unidad').value = '';
    document.getElementById('sector_unidad').value = '';
    document.getElementById('form-unidad-titulo').textContent = '➕ Agregar Nueva Unidad';
    
    // Limpiar formulario de emergencias
    document.getElementById('id_emergencia').value = '';
    document.getElementById('codigo_emergencia').value = '';
    document.getElementById('descripcion_emergencia').value = '';
    document.getElementById('ubicacion_emergencia').value = '';
    document.getElementById('unidades_asignadas_emergencia').value = '';
    document.getElementById('activa_emergencia').value = '1';
    document.getElementById('form-emergencia-titulo').textContent = '🚨 Nueva Emergencia';
    
    // Limpiar formulario de ubicaciones
    document.getElementById('id_ubicacion').value = '';
    document.getElementById('nombre_ubicacion').value = '';
    document.getElementById('descripcion_ubicacion').value = '';
    document.getElementById('lat_ubicacion').value = '';
    document.getElementById('lng_ubicacion').value = '';
    document.getElementById('tipo_ubicacion').value = '';
    document.getElementById('form-ubicacion-titulo').textContent = '➕ Nueva Ubicación';
}

// Verificar si hay mensaje de éxito y limpiar formularios
document.addEventListener('DOMContentLoaded', function() {
    // Si hay un mensaje de éxito, limpiar los formularios
    const alertSuccess = document.querySelector('.alert-success');
    if (alertSuccess) {
        limpiarFormularios();
    }
    
    // También agregar esta función al sistema de pestañas existente
    const tabButtons = document.querySelectorAll('.tab-button');
    const tabContents = document.querySelectorAll('.tab-content');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            const tabId = button.getAttribute('data-tab');
            
            // Remover clase active de todos los botones y contenidos
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));
            
            // Agregar clase active al botón y contenido actual
            button.classList.add('active');
            document.getElementById(tabId).classList.add('active');
            
            // Si es la pestaña de radio, actualizar logs inmediatamente
            if (tabId === 'radio') {
                actualizarLogsRadio();
            }
        });
    });
});

// Función para actualizar logs de radio
function actualizarLogsRadio() {
    const radioLog = document.getElementById('radioLog');
    const radioTab = document.getElementById('radio');
    
    // Solo actualizar si la pestaña de Radio está activa
    if (radioLog && radioTab && radioTab.classList.contains('active')) {
        fetch('Radio.php?action=log')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error en la respuesta del servidor');
                }
                return response.json();
            })
            .then(data => {
                if (Array.isArray(data)) {
                    radioLog.innerHTML = '';
                    if (data.length > 0) {
                        data.forEach(item => {
                            const div = document.createElement('div');
                            div.className = 'comunicacion-item';
                            div.id = 'comunicacion-' + item.id;
                            div.innerHTML = `
                                <div class="comms-content">
                                    <p><strong>${item.emisor}:</strong> ${item.mensaje}</p>
                                    <small>${item.fecha}</small>
                                </div>
                                <div class="comms-actions">
                                    <button class="btn-editar-small" onclick="editarComunicacion(${item.id}, '${item.emisor.replace(/'/g, "\\'")}', '${item.mensaje.replace(/'/g, "\\'")}')">✏️</button>
                                    <button class="btn-eliminar-small" onclick="eliminarComunicacion(${item.id})">🗑️</button>
                                </div>
                            `;
                            radioLog.appendChild(div);
                        });
                    } else {
                        radioLog.innerHTML = '<div class="no-data"><p>No hay comunicaciones recientes.</p></div>';
                    }
                }
            })
            .catch(err => { 
                console.error('Error al cargar logs:', err); 
            });
    }
}

// Actualizar logs cada 5 segundos
setInterval(actualizarLogsRadio, 5000);