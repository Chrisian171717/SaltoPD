// Función para cargar resultados de búsqueda
        function cargarResultados(busqueda = '') {
            const formData = new FormData();
            formData.append('busqueda', busqueda);
            formData.append('action', 'buscar');

            fetch('../Back-end/Vehiculos.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                const contenedor = document.getElementById('resultados-vehiculos');
                contenedor.innerHTML = '';

                if (data.length > 0) {
                    data.forEach(vehiculo => {
                        const articulo = document.createElement('article');
                        articulo.className = 'vehiculo-card';
                        articulo.innerHTML = `
                            <img src="Civil.png" alt="Foto del conductor" class="vehiculo-card__foto" />
                            <div class="vehiculo-card__info">
                                <span class="vehiculo-card__marca">${vehiculo.marca}</span>
                                <span class="vehiculo-card__modelo">${vehiculo.modelo}</span>
                                <span class="vehiculo-card__matricula">${vehiculo.matricula}</span>
                                <span class="vehiculo-card__civil">Conductor: ${vehiculo.nombre} (DNI: ${vehiculo.dni})</span>
                                <input type="text" placeholder="Observación..." class="vehiculo-card__observacion" required />
                            </div>
                        `;
                        contenedor.appendChild(articulo);
                    });
                } else {
                    contenedor.innerHTML = '<p>No se encontraron vehículos con esos datos.</p>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('resultados-vehiculos').innerHTML = '<p>Error al cargar los datos.</p>';
            });
        }

        // Cargar todos los vehículos al iniciar
        document.addEventListener('DOMContentLoaded', function() {
            cargarResultados();
        });

        // Manejar envío del formulario de búsqueda
        document.querySelector('form').addEventListener('submit', function(e) {
            e.preventDefault();
            const busqueda = document.querySelector('input[name="busqueda"]').value;
            cargarResultados(busqueda);
        });