function cargarDenuncias() {
    if (cargando) return;
    
    cargando = true;
    
    var busqueda = $('input[name="busqueda"]').val();
    var codigo_busqueda = $('input[name="codigo_busqueda"]').val();
    
    $('#tabla-denuncias').html('<div class="loader"></div><p style="text-align: center;">Buscando denuncias...</p>');
    
    // Usar ruta absoluta para evitar problemas CORS
    var rutaBase = window.location.href.includes('localhost') ? 
        'http://localhost/GitHub/SaltoPD/Back-end/' : 
        '../Back-end/';
    
    $.ajax({
        url: rutaBase + 'mostrar_denuncias.php',
        type: 'GET',
        data: {
            busqueda: busqueda,
            codigo_busqueda: codigo_busqueda
        },
        success: function(response) {
            $('#tabla-denuncias').html(response);
            cargando = false;
        },
        error: function(xhr, status, error) {
            $('#tabla-denuncias').html(
                '<p class="error">Error: Debes ejecutar desde servidor web (http://)</p>' +
                '<p class="info">Usa XAMPP o abre: http://localhost/GitHub/SaltoPD/Front-end/denuncias.html</p>' +
                '<button onclick="location.reload()">Reintentar</button>'
            );
            cargando = false;
            console.error("Error:", error);
        },
        timeout: 15000
    });
}