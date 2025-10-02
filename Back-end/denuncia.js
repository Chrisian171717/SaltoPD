// ===== CONFIGURACIÓN INTELIGENTE =====
function getBackendBaseUrl() {
  const currentPath = window.location.pathname;
  
  if (currentPath.includes('GitHub/SaltoPD')) {
    return '/GitHub/SaltoPD/Back-end';
  }
  else if (currentPath === '/' || currentPath.includes('Front-end')) {
    return '../Back-end';
  }
  else {
    return '../Back-end';
  }
}

const BACKEND_BASE_URL = getBackendBaseUrl();
console.log('📍 Ruta Backend detectada:', BACKEND_BASE_URL);

// ===== INICIALIZACIÓN =====
$(document).ready(function () {
    console.log('🚀 Sistema de Denuncias - Iniciando...');
    
    // Probar conexión primero
    probarConexionBackend();
    
    // Configurar eventos
    configurarEventos();
});

function configurarEventos() {
    $("#form-agregar-denuncia").submit(function (e) {
        e.preventDefault();
        agregarDenuncia();
    });

    $("#form-buscar-denuncia").submit(function (e) {
        e.preventDefault();
        buscarDenuncias();
    });

    $("#form-editar-denuncia").submit(function (e) {
        e.preventDefault();
        guardarEdicionDenuncia();
    });

    $(".close").click(function() {
        cerrarModal();
    });

    $(window).click(function(e) {
        if (e.target.id === 'modalEditar') {
            cerrarModal();
        }
    });
    
    $("#btn-mostrar-todas").click(function() {
        cargarTodasDenuncias();
    });
    
    $("#btn-generar-reporte").click(function() {
        generarReporte();
    });
    
    $("#btn-cancelar-edicion").click(function() {
        cerrarModal();
    });
    
    $("#btn-actualizar-lista").click(function() {
        cargarDenuncias();
    });
}

// ===== FUNCIÓN PARA PROBAR CONEXIÓN =====
function probarConexionBackend() {
    const testUrl = `${BACKEND_BASE_URL}/funciones_denuncias.php?accion=listar`;
    console.log('🔍 Probando conexión con:', testUrl);
    
    $.ajax({
        url: testUrl,
        method: 'GET',
        dataType: 'text',
        timeout: 8000,
        success: function(response) {
            console.log('📨 Respuesta CRUDA del servidor:', response);
            
            // Limpiar respuesta de posibles espacios/ruido
            const cleanResponse = response.trim();
            
            // Verificar si es HTML (error PHP)
            if (cleanResponse.startsWith('<') || cleanResponse.includes('<b>') || cleanResponse.includes('<br')) {
                console.error('❌ El servidor devuelve HTML (errores PHP)');
                mostrarErrorPHP(cleanResponse);
                return;
            }
            
            try {
                const jsonData = JSON.parse(cleanResponse);
                console.log('✅ Conexión exitosa con el backend', jsonData);
                
                // Si la conexión es exitosa, cargar datos
                if (jsonData.status === "ok") {
                    cargarDenuncias();
                    actualizarEstadisticas();
                }
            } catch (e) {
                console.error('❌ Error parseando JSON:', e);
                console.log('📨 Respuesta que falló:', cleanResponse);
                mostrarErrorJSON(cleanResponse);
            }
        },
        error: function(xhr, status, error) {
            console.error('❌ Error de conexión:', error);
            mostrarErrorConexion(testUrl, error, xhr.status);
        }
    });
}

// ===== FUNCIONES PRINCIPALES =====

function cargarDenuncias() {
    const url = `${BACKEND_BASE_URL}/funciones_denuncias.php?accion=listar`;
    
    $("#tabla-denuncias").html(`
        <div class="loading">
            <div class="spinner"></div>
            <p>Cargando denuncias...</p>
        </div>
    `);
    
    $.ajax({
        url: url,
        method: 'GET',
        dataType: 'text',
        timeout: 10000,
        success: function(response) {
            const cleanResponse = response.trim();
            
            // Verificar si es HTML (error PHP)
            if (cleanResponse.startsWith('<') || cleanResponse.includes('<b>') || cleanResponse.includes('<br')) {
                mostrarErrorPHP(cleanResponse);
                return;
            }
            
            try {
                const res = JSON.parse(cleanResponse);
                if (res.status === "ok") {
                    renderizarDenuncias(res.data);
                    actualizarEstadisticas(res.data);
                } else {
                    mostrarError("Error al cargar denuncias: " + (res.mensaje || 'Error desconocido'));
                }
            } catch (e) {
                console.error('❌ Error parseando JSON:', e);
                mostrarErrorJSON(cleanResponse);
            }
        },
        error: function(xhr, status, error) {
            console.error('❌ Error cargando denuncias:', error);
            mostrarErrorConexion(url, error, xhr.status);
        }
    });
}

function agregarDenuncia() {
    const url = `${BACKEND_BASE_URL}/funciones_denuncias.php`;
    
    const nombre = $("#nombre_civil").val().trim();
    const codigo = $("#codigo_penal").val().trim();
    const descripcion = $("#descripcion").val().trim();
    
    if (!nombre || !codigo || !descripcion) {
        mostrarMensaje("❌ Todos los campos son obligatorios", "error");
        return;
    }
    
    const formData = {
        accion: "agregar",
        nombre_civil: nombre,
        codigo_penal: codigo,
        descripcion: descripcion
    };
    
    const submitBtn = $("#form-agregar-denuncia button[type='submit']");
    const originalText = submitBtn.html();
    submitBtn.html('⏳ Enviando...').prop('disabled', true);
    
    $.ajax({
        url: url,
        method: 'POST',
        data: formData,
        dataType: 'text',
        timeout: 10000,
        success: function(response) {
            const cleanResponse = response.trim();
            
            // Verificar si es HTML (error PHP)
            if (cleanResponse.startsWith('<') || cleanResponse.includes('<b>') || cleanResponse.includes('<br')) {
                mostrarErrorPHP(cleanResponse);
                return;
            }
            
            try {
                const res = JSON.parse(cleanResponse);
                if (res.status === "ok") {
                    mostrarMensaje("✅ Denuncia registrada correctamente", "success");
                    $("#form-agregar-denuncia")[0].reset();
                    cargarDenuncias();
                } else {
                    mostrarMensaje("❌ Error: " + res.mensaje, "error");
                }
            } catch (e) {
                console.error('❌ Error parseando JSON:', e);
                mostrarErrorJSON(cleanResponse);
            }
        },
        error: function(xhr, status, error) {
            mostrarMensaje("❌ Error de conexión: " + error, "error");
        },
        complete: function() {
            submitBtn.html(originalText).prop('disabled', false);
        }
    });
}

function buscarDenuncias() {
    const url = `${BACKEND_BASE_URL}/funciones_denuncias.php`;
    
    const busqueda = $("#busqueda").val().trim();
    const codigo_busqueda = $("#codigo_busqueda").val().trim();
    
    const formData = {
        accion: "buscar",
        busqueda: busqueda,
        codigo_busqueda: codigo_busqueda
    };
    
    $.ajax({
        url: url,
        method: 'POST',
        data: formData,
        dataType: 'text',
        timeout: 10000,
        success: function(response) {
            const cleanResponse = response.trim();
            
            // Verificar si es HTML (error PHP)
            if (cleanResponse.startsWith('<') || cleanResponse.includes('<b>') || cleanResponse.includes('<br')) {
                mostrarErrorPHP(cleanResponse);
                return;
            }
            
            try {
                const res = JSON.parse(cleanResponse);
                if (res.status === "ok") {
                    renderizarDenuncias(res.data);
                    mostrarMensaje(`🔍 Se encontraron ${res.data.length} denuncias`, "info");
                } else {
                    mostrarError("Error en búsqueda: " + (res.mensaje || 'Error desconocido'));
                }
            } catch (e) {
                console.error('❌ Error parseando JSON:', e);
                mostrarErrorJSON(cleanResponse);
            }
        },
        error: function(xhr, status, error) {
            mostrarMensaje("❌ Error de búsqueda: " + error, "error");
        }
    });
}

function cargarTodasDenuncias() {
    $("#form-buscar-denuncia")[0].reset();
    cargarDenuncias();
    mostrarMensaje("📋 Mostrando todas las denuncias", "info");
}

// ===== FUNCIONES DE EDICIÓN Y ELIMINACIÓN =====

function editarDenuncia(id, nombre, codigo, descripcion) {
    $("#editar_id").val(id);
    $("#editar_nombre_civil").val(nombre);
    $("#editar_codigo_penal").val(codigo);
    $("#editar_descripcion").val(descripcion);
    $("#modalEditar").show();
    $("#mensaje-editar").html('');
}

function guardarEdicionDenuncia() {
    const url = `${BACKEND_BASE_URL}/funciones_denuncias.php`;
    
    const id = $("#editar_id").val();
    const nombre = $("#editar_nombre_civil").val().trim();
    const codigo = $("#editar_codigo_penal").val().trim();
    const descripcion = $("#editar_descripcion").val().trim();
    
    if (!nombre || !codigo || !descripcion) {
        $("#mensaje-editar").html('<div class="error">❌ Todos los campos son obligatorios</div>');
        return;
    }
    
    const formData = {
        accion: "editar",
        id: id,
        nombre_civil: nombre,
        codigo_penal: codigo,
        descripcion: descripcion
    };
    
    const submitBtn = $("#form-editar-denuncia button[type='submit']");
    const originalText = submitBtn.html();
    submitBtn.html('⏳ Guardando...').prop('disabled', true);
    
    $.ajax({
        url: url,
        method: 'POST',
        data: formData,
        dataType: 'text',
        timeout: 10000,
        success: function(response) {
            const cleanResponse = response.trim();
            
            // Verificar si es HTML (error PHP)
            if (cleanResponse.startsWith('<') || cleanResponse.includes('<b>') || cleanResponse.includes('<br')) {
                mostrarErrorPHP(cleanResponse);
                return;
            }
            
            try {
                const res = JSON.parse(cleanResponse);
                if (res.status === "ok") {
                    $("#mensaje-editar").html('<div class="success">✅ Denuncia actualizada correctamente</div>');
                    setTimeout(() => {
                        cerrarModal();
                        cargarDenuncias();
                    }, 1500);
                } else {
                    $("#mensaje-editar").html('<div class="error">❌ Error: ' + res.mensaje + '</div>');
                }
            } catch (e) {
                console.error('❌ Error parseando JSON:', e);
                $("#mensaje-editar").html('<div class="error">❌ Error en el servidor: respuesta no válida</div>');
            }
        },
        error: function(xhr, status, error) {
            $("#mensaje-editar").html('<div class="error">❌ Error de conexión: ' + error + '</div>');
        },
        complete: function() {
            submitBtn.html(originalText).prop('disabled', false);
        }
    });
}

function eliminarDenuncia(id) {
    if (!confirm("⚠️ ¿Está seguro de que desea eliminar esta denuncia?\n\nEsta acción no se puede deshacer.")) {
        return;
    }
    
    const url = `${BACKEND_BASE_URL}/funciones_denuncias.php`;
    
    $.ajax({
        url: url,
        method: 'POST',
        data: {
            accion: "eliminar",
            id: id
        },
        dataType: 'text',
        timeout: 10000,
        success: function(response) {
            const cleanResponse = response.trim();
            
            // Verificar si es HTML (error PHP)
            if (cleanResponse.startsWith('<') || cleanResponse.includes('<b>') || cleanResponse.includes('<br')) {
                mostrarErrorPHP(cleanResponse);
                return;
            }
            
            try {
                const res = JSON.parse(cleanResponse);
                if (res.status === "ok") {
                    mostrarMensaje("✅ Denuncia eliminada correctamente", "success");
                    cargarDenuncias();
                } else {
                    mostrarMensaje("❌ Error: " + res.mensaje, "error");
                }
            } catch (e) {
                console.error('❌ Error parseando JSON:', e);
                mostrarMensaje("❌ Error en el servidor al eliminar", "error");
            }
        },
        error: function(xhr, status, error) {
            mostrarMensaje("❌ Error de conexión: " + error, "error");
        }
    });
}

function cerrarModal() {
    $("#modalEditar").hide();
    $("#form-editar-denuncia")[0].reset();
    $("#mensaje-editar").html('');
}

// ===== FUNCIONES DE RENDERIZADO =====

function renderizarDenuncias(denuncias) {
    if (!denuncias || denuncias.length === 0) {
        $("#tabla-denuncias").html(`
            <div class="info" style="text-align: center; padding: 40px;">
                <h3>📭 No se encontraron denuncias</h3>
                <p>No hay denuncias registradas en el sistema.</p>
                <button onclick="cargarDenuncias()" class="btn btn-info">🔄 Actualizar</button>
            </div>
        `);
        return;
    }

    let html = `
        <div style="margin-bottom: 20px; text-align: center;">
            <span class="stat-label">Total: ${denuncias.length} denuncias</span>
        </div>
        <div class="denuncia-cards">
    `;
    
    denuncias.forEach(denuncia => {
        const fecha = new Date(denuncia.Fecha).toLocaleDateString('es-ES', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
        
        // Información adicional si existe
        const infoAdicional = [];
        if (denuncia.Tipo) infoAdicional.push(`<strong>📋 Tipo:</strong> ${escapeHtml(denuncia.Tipo)}`);
        if (denuncia.Tipo_Informe) infoAdicional.push(`<strong>📄 Tipo Informe:</strong> ${escapeHtml(denuncia.Tipo_Informe)}`);
        if (denuncia.Num_Placa) infoAdicional.push(`<strong>🔢 Placa:</strong> ${escapeHtml(denuncia.Num_Placa)}`);
        if (denuncia.Cedula_C) infoAdicional.push(`<strong>🆔 Cédula:</strong> ${escapeHtml(denuncia.Cedula_C)}`);
        
        html += `
        <article class="denuncia-card fade-in">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px;">
                <strong>Denuncia #${denuncia.id}</strong>
                <span style="background: #667eea; color: white; padding: 4px 8px; border-radius: 12px; font-size: 0.8rem;">
                    ${fecha}
                </span>
            </div>
            
            <p><strong>👤 Civil:</strong> ${escapeHtml(denuncia.nombre_civil)}</p>
            <p><strong>⚖️ Código Penal:</strong> <code>${escapeHtml(denuncia.CodigoPenal)}</code></p>
            <p><strong>📝 Descripción:</strong> ${escapeHtml(denuncia.descripcion)}</p>
            ${infoAdicional.length > 0 ? `<p><strong>📊 Información Adicional:</strong><br>${infoAdicional.join('<br>')}</p>` : ''}
            
            <div class="denuncia-actions">
                <button onclick="editarDenuncia(${denuncia.id}, '${escapeJs(denuncia.nombre_civil)}', '${escapeJs(denuncia.CodigoPenal)}', '${escapeJs(denuncia.descripcion)}')" 
                        class="btn btn-warning btn-sm">
                    ✏️ Editar
                </button>
                <button onclick="eliminarDenuncia(${denuncia.id})" 
                        class="btn btn-danger btn-sm">
                    🗑️ Eliminar
                </button>
            </div>
        </article>`;
    });
    
    html += '</div>';
    $("#tabla-denuncias").html(html);
}

// ===== FUNCIONES ADICIONALES =====

function actualizarEstadisticas(denuncias) {
    if (!denuncias) {
        const url = `${BACKEND_BASE_URL}/funciones_denuncias.php?accion=estadisticas`;
        $.ajax({
            url: url,
            method: 'GET',
            dataType: 'text',
            success: function(response) {
                const cleanResponse = response.trim();
                try {
                    const res = JSON.parse(cleanResponse);
                    if (res.status === "ok" && res.data) {
                        $("#total-denuncias").text(res.data.total);
                        $("#denuncias-hoy").text(res.data.hoy);
                        $("#denuncias-mes").text(res.data.mes);
                        $("#resueltas").text(res.data.resueltas);
                    }
                } catch (e) {
                    usarEstadisticasPorDefecto();
                }
            },
            error: function() {
                usarEstadisticasPorDefecto();
            }
        });
    } else {
        const total = denuncias.length;
        const hoy = denuncias.filter(d => {
            const fechaDenuncia = new Date(d.Fecha);
            const hoy = new Date();
            return fechaDenuncia.toDateString() === hoy.toDateString();
        }).length;
        
        const mes = denuncias.filter(d => {
            const fechaDenuncia = new Date(d.Fecha);
            const hoy = new Date();
            return fechaDenuncia.getMonth() === hoy.getMonth() && 
                   fechaDenuncia.getFullYear() === hoy.getFullYear();
        }).length;
        
        $("#total-denuncias").text(total);
        $("#denuncias-hoy").text(hoy);
        $("#denuncias-mes").text(mes);
        $("#resueltas").text('0');
    }
}

function usarEstadisticasPorDefecto() {
    $("#total-denuncias").text('0');
    $("#denuncias-hoy").text('0');
    $("#denuncias-mes").text('0');
    $("#resueltas").text('0');
}

function generarReporte() {
    alert(`📊 Generando reporte de denuncias...\n\nEsta funcionalidad generará un reporte PDF/Excel con todas las denuncias registradas.`);
}

// ===== MANEJO MEJORADO DE ERRORES =====

function mostrarErrorJSON(respuesta) {
    console.error('❌ ERROR JSON DETECTADO:', respuesta);
    
    const errorDiv = `
        <div class="error" style="max-height: 400px; overflow-y: auto;">
            <h4>❌ Error en la respuesta del servidor</h4>
            <p>El servidor devuelve JSON inválido o corrupto.</p>
            
            <details style="margin-top: 15px;">
                <summary>Ver respuesta del servidor</summary>
                <div style="background: #1e1e1e; color: #d4d4d4; padding: 15px; border-radius: 5px; margin-top: 10px; font-family: monospace; font-size: 12px; white-space: pre-wrap;">
${escapeHtml(respuesta.substring(0, 1000))}
                </div>
            </details>
            
            <div style="margin-top: 20px; background: #fff3cd; padding: 15px; border-radius: 5px; border-left: 4px solid #ffc107;">
                <h5>🔧 Posibles soluciones:</h5>
                <ol style="text-align: left; margin: 10px 0;">
                    <li>Verifica que el archivo PHP no tenga espacios/ruido antes de <?php o después de ?></li>
                    <li>Comprueba que no haya echo/print fuera del JSON</li>
                    <li>Revisa que el encoding del archivo sea UTF-8 sin BOM</li>
                    <li>Verifica que no haya errores de sintaxis en el PHP</li>
                </ol>
            </div>
            
            <div style="margin-top: 15px;">
                <button onclick="probarConexionBackend()" class="btn btn-info">🔄 Reintentar</button>
                <button onclick="usarDatosDemo()" class="btn btn-warning">🎭 Usar Demo</button>
                <button onclick="limpiarYReintentar()" class="btn btn-secondary">🧹 Limpiar Cache</button>
            </div>
        </div>
    `;
    
    $("#tabla-denuncias").html(errorDiv);
}

function mostrarErrorPHP(respuestaHtml) {
    console.error('❌ ERROR PHP DETECTADO:', respuestaHtml);
    
    const errorLines = respuestaHtml.split('\n');
    let errorInfo = 'Error de PHP en el servidor';
    
    errorLines.forEach(line => {
        if (line.includes('Fatal error') || line.includes('Parse error') || line.includes('Warning') || line.includes('Notice')) {
            errorInfo = line.replace(/<[^>]*>/g, '').trim();
        }
    });
    
    const errorDiv = `
        <div class="error" style="max-height: 400px; overflow-y: auto;">
            <h4>❌ Error de PHP en el Servidor</h4>
            <p><strong>${errorInfo}</strong></p>
            
            <details style="margin-top: 15px;">
                <summary>Ver detalles técnicos</summary>
                <div style="background: #1e1e1e; color: #d4d4d4; padding: 15px; border-radius: 5px; margin-top: 10px; font-family: monospace; font-size: 12px; white-space: pre-wrap;">
${escapeHtml(respuestaHtml.substring(0, 2000))}
                </div>
            </details>
            
            <div style="margin-top: 15px;">
                <button onclick="probarConexionBackend()" class="btn btn-info">🔄 Reintentar</button>
                <button onclick="usarDatosDemo()" class="btn btn-warning">🎭 Usar Demo</button>
            </div>
        </div>
    `;
    
    $("#tabla-denuncias").html(errorDiv);
}

function mostrarError(mensaje) {
    $("#tabla-denuncias").html(`
        <div class="error">
            <h4>❌ Error</h4>
            <p>${mensaje}</p>
            <div style="margin-top: 15px;">
                <button onclick="cargarDenuncias()" class="btn btn-info">🔄 Reintentar</button>
                <button onclick="usarDatosDemo()" class="btn btn-warning">🎭 Usar Demo</button>
            </div>
        </div>
    `);
}

function mostrarErrorConexion(url, error, status) {
    const errorDiv = `
        <div class="error">
            <h4>❌ Error de Conexión</h4>
            <p><strong>URL:</strong> ${url}</p>
            <p><strong>Error:</strong> ${error}</p>
            <p><strong>Status:</strong> ${status}</p>
            <div style="margin-top: 15px;">
                <button onclick="cargarDenuncias()" class="btn btn-info">🔄 Reintentar</button>
                <button onclick="usarDatosDemo()" class="btn btn-warning">🎭 Usar Demo</button>
            </div>
        </div>
    `;
    $("#tabla-denuncias").html(errorDiv);
}

function limpiarYReintentar() {
    // Limpiar cache del navegador forzando reload
    window.location.reload(true);
}

// ===== MODO DEMO =====
function usarDatosDemo() {
    const datosDemo = [
        {
            id: 1,
            nombre_civil: "Juan Pérez",
            CodigoPenal: "ART 79 CP",
            descripcion: "Robo en vía pública con arma blanca",
            Fecha: "2024-01-15 14:30:00",
            Tipo: "Denuncia",
            Tipo_Informe: "General"
        },
        {
            id: 2,
            nombre_civil: "María González",
            CodigoPenal: "ART 149 CP",
            descripcion: "Daños a propiedad privada",
            Fecha: "2024-01-14 10:15:00",
            Tipo: "Denuncia",
            Tipo_Informe: "Urgente"
        }
    ];
    
    renderizarDenuncias(datosDemo);
    actualizarEstadisticas(datosDemo);
    mostrarMensaje("🎭 Modo demo activado - Usando datos de ejemplo", "info");
}

function mostrarMensaje(mensaje, tipo) {
    $("#mensaje-form").html(`<div class="mensaje ${tipo}">${mensaje}</div>`);
    setTimeout(() => $("#mensaje-form").html(""), 5000);
}

// ===== FUNCIONES UTILITARIAS =====
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function escapeJs(text) {
    if (!text) return '';
    return text.replace(/'/g, "\\'")
               .replace(/"/g, '\\"')
               .replace(/\n/g, '\\n')
               .replace(/\r/g, '\\r');
}

console.log('✅ Sistema de Denuncias - Listo para usar');