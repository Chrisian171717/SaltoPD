// script.js - Funcionalidad para el mapa interactivo de Salto

document.addEventListener('DOMContentLoaded', function() {
    // Inicialización de componentes
    inicializarMapaInteractivo();
    inicializarNavegacion();
    inicializarFormulario();
});

// Función para inicializar el mapa interactivo
function inicializarMapaInteractivo() {
    const mapaContenedor = document.querySelector('.mapa-contenedor');
    const imagenMapa = document.querySelector('.imagen-mapa');
    
    if (!imagenMapa) return;
    
    // Hacer el mapa interactivo con zoom
    imagenMapa.style.cursor = 'zoom-in';
    let zoomLevel = 1;
    
    imagenMapa.addEventListener('click', function() {
        if (zoomLevel === 1) {
            // Zoom in
            this.style.transform = 'scale(1.5)';
            this.style.transition = 'transform 0.3s ease';
            this.style.cursor = 'zoom-out';
            zoomLevel = 1.5;
        } else {
            // Zoom out
            this.style.transform = 'scale(1)';
            this.style.cursor = 'zoom-in';
            zoomLevel = 1;
        }
    });
    
    // Permitir arrastrar el mapa cuando está zoomado
    let isDragging = false;
    let startX, startY, scrollLeft, scrollTop;
    
    mapaContenedor.addEventListener('mousedown', (e) => {
        if (zoomLevel > 1) {
            isDragging = true;
            startX = e.pageX - mapaContenedor.offsetLeft;
            startY = e.pageY - mapaContenedor.offsetTop;
            scrollLeft = mapaContenedor.scrollLeft;
            scrollTop = mapaContenedor.scrollTop;
            mapaContenedor.style.cursor = 'grabbing';
        }
    });
    
    mapaContenedor.addEventListener('mouseleave', () => {
        isDragging = false;
    });
    
    mapaContenedor.addEventListener('mouseup', () => {
        isDragging = false;
        if (zoomLevel > 1) {
            mapaContenedor.style.cursor = 'grab';
        }
    });
    
    mapaContenedor.addEventListener('mousemove', (e) => {
        if (!isDragging) return;
        e.preventDefault();
        const x = e.pageX - mapaContenedor.offsetLeft;
        const y = e.pageY - mapaContenedor.offsetTop;
        const walkX = (x - startX) * 2;
        const walkY = (y - startY) * 2;
        mapaContenedor.scrollLeft = scrollLeft - walkX;
        mapaContenedor.scrollTop = scrollTop - walkY;
    });
}

// Función para inicializar la navegación
function inicializarNavegacion() {
    const navLinks = document.querySelectorAll('.nav-tabs__link');
    
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            // Añadir efecto visual al hacer clic
            this.style.transform = 'scale(0.95)';
            setTimeout(() => {
                this.style.transform = 'scale(1)';
            }, 150);
            
            // Aquí puedes añadir lógica adicional antes de la navegación
            console.log('Navegando a:', this.href);
        });
        
        // Efecto hover
        link.addEventListener('mouseenter', function() {
            this.style.transition = 'all 0.2s ease';
        });
    });
}

// Función para inicializar el formulario
function inicializarFormulario() {
    const form = document.querySelector('form');
    
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Validación básica antes de enviar
            if (validarFormulario()) {
                // Mostrar mensaje de carga
                mostrarMensaje('Enviando datos...', 'info');
                
                // Simular envío (reemplazar con tu lógica real)
                setTimeout(() => {
                    this.submit();
                }, 1000);
            }
        });
    }
}

// Función de validación del formulario
function validarFormulario() {
    // Aquí puedes añadir validaciones específicas según tus campos
    console.log('Validando formulario...');
    return true; // Cambiar según tu lógica de validación
}

// Función para mostrar mensajes al usuario
function mostrarMensaje(mensaje, tipo = 'info') {
    // Crear elemento de mensaje
    const mensajeDiv = document.createElement('div');
    mensajeDiv.textContent = mensaje;
    mensajeDiv.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        border-radius: 5px;
        color: white;
        font-weight: bold;
        z-index: 1000;
        transition: all 0.3s ease;
    `;
    
    // Colores según el tipo de mensaje
    const colores = {
        'info': '#2196F3',
        'success': '#4CAF50',
        'warning': '#FF9800',
        'error': '#F44336'
    };
    
    mensajeDiv.style.backgroundColor = colores[tipo] || colores.info;
    
    // Añadir al documento
    document.body.appendChild(mensajeDiv);
    
    // Remover después de 3 segundos
    setTimeout(() => {
        mensajeDiv.style.opacity = '0';
        setTimeout(() => {
            if (mensajeDiv.parentNode) {
                mensajeDiv.parentNode.removeChild(mensajeDiv);
            }
        }, 300);
    }, 3000);
}

// Funciones adicionales para el mapa
function agregarMarcadorMapa(x, y, titulo, descripcion) {
    // Función para agregar marcadores interactivos al mapa
    const marcador = document.createElement('div');
    marcador.className = 'mapa-marcador';
    marcador.innerHTML = `
        <div class="marcador-punto"></div>
        <div class="marcador-tooltip">
            <h3>${titulo}</h3>
            <p>${descripcion}</p>
        </div>
    `;
    
    marcador.style.cssText = `
        position: absolute;
        left: ${x}%;
        top: ${y}%;
        cursor: pointer;
        z-index: 10;
    `;
    
    document.querySelector('.mapa-contenedor').appendChild(marcador);
    
    // Añadir interactividad al marcador
    marcador.addEventListener('click', function(e) {
        e.stopPropagation();
        const tooltip = this.querySelector('.marcador-tooltip');
        tooltip.style.display = tooltip.style.display === 'block' ? 'none' : 'block';
    });
}

// Ejemplo de uso de marcadores (puedes personalizar las coordenadas y datos)
function inicializarMarcadores() {
    // Ejemplo de marcadores - ajusta las coordenadas según tu imagen
    const marcadores = [
        { x: 30, y: 40, titulo: 'Comisaría Central', descripcion: 'Comisaría principal del distrito' },
        { x: 60, y: 25, titulo: 'Plaza Principal', descripcion: 'Área cívica central' },
        { x: 45, y: 65, titulo: 'Estación de Servicio', descripcion: 'Punto de abastecimiento' }
    ];
    
    marcadores.forEach(marcador => {
        agregarMarcadorMapa(marcador.x, marcador.y, marcador.titulo, marcador.descripcion);
    });
}

// Inicializar marcadores cuando el documento esté listo
document.addEventListener('DOMContentLoaded', inicializarMarcadores);
