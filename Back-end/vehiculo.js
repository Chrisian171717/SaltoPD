const vehiculosSearch = document.querySelector('.vehiculos-search');
const vehiculosList = document.querySelector('.vehiculos-lista');

async function loadVehiculos() {
    try {
        const res = await fetch('../Back-end/Vehiculos.php?action=list');
        const data = await res.json();
        vehiculosList.innerHTML = '';
        data.forEach(v => {
            const card = document.createElement('article');
            card.className = 'vehiculo-card';
            card.innerHTML = `
                <img src="Civil.png" alt="Foto" class="vehiculo-card__foto" />
                <div class="vehiculo-card__info">
                    <span class="vehiculo-card__marca">${v.marca}</span>
                    <span class="vehiculo-card__modelo">${v.modelo}</span>
                    <span class="vehiculo-card__matricula">${v.matricula}</span>
                </div>`;
            vehiculosList.appendChild(card);
        });
    } catch (err) { console.error(err); }
}

if (vehiculosSearch) {
    vehiculosSearch.addEventListener('input', async () => {
        const term = vehiculosSearch.value.toLowerCase();
        const cards = vehiculosList.querySelectorAll('.vehiculo-card');
        cards.forEach(card => {
            const marca = card.querySelector('.vehiculo-card__marca').textContent.toLowerCase();
            const matricula = card.querySelector('.vehiculo-card__matricula').textContent.toLowerCase();
            card.style.display = (marca.includes(term) || matricula.includes(term)) ? '' : 'none';
        });
    });
}

loadVehiculos();