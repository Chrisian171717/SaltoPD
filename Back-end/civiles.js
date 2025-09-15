const civilesForm = document.querySelector('form[action="../Back-end/Civiles.php"]');
const civilesList = document.querySelector('.civiles-list');

if (civilesForm) {
    civilesForm.addEventListener('submit', async e => {
        e.preventDefault();
        const nombre = civilesForm.nombre.value.trim();
        const dni = civilesForm.dni.value.trim();

        if (!nombre || !dni) return alert("Todos los campos son obligatorios.");

        const formData = new FormData(civilesForm);
        formData.append('action', 'add');

        try {
            const res = await fetch('../Back-end/Civiles.php', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();
            if (data.success) {
                loadCiviles(); // recarga la lista
                civilesForm.reset();
            } else {
                alert(data.message || 'Error al agregar civil.');
            }
        } catch (err) {
            console.error(err);
        }
    });
}

async function loadCiviles() {
    try {
        const res = await fetch('../Back-end/Civiles.php?action=list');
        const data = await res.json();
        civilesList.innerHTML = '';
        data.forEach(civil => {
            const card = document.createElement('article');
            card.className = 'civil-card';
            card.dataset.id = civil.id;
            card.innerHTML = `
                <img src="Persona.png" alt="Foto de ${civil.nombre}" class="civil-card__photo" />
                <div class="civil-card__info">
                    <span class="civil-card__name">${civil.nombre}</span>
                    <span class="civil-card__dni">DNI: ${civil.dni}</span>
                    <input type="text" placeholder="Dato adicional" class="civil-card__extra" />
                    <div class="civil-actions">
                        <button class="edit-btn">✏️ Editar</button>
                        <button class="delete-btn">🗑️ Eliminar</button>
                    </div>
                </div>`;
            civilesList.appendChild(card);

            card.querySelector('.edit-btn').addEventListener('click', () => editCivil(civil.id));
            card.querySelector('.delete-btn').addEventListener('click', () => deleteCivil(civil.id));
        });
    } catch (err) { console.error(err); }
}

async function editCivil(id) {
    const newName = prompt('Nuevo nombre:');
    const newDNI = prompt('Nuevo DNI:');
    if (!newName || !newDNI) return;

    const formData = new FormData();
    formData.append('action', 'edit');
    formData.append('id', id);
    formData.append('nombre', newName);
    formData.append('dni', newDNI);

    try {
        const res = await fetch('../Back-end/Civiles.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) loadCiviles();
    } catch (err) { console.error(err); }
}

async function deleteCivil(id) {
    if (!confirm('¿Eliminar este civil?')) return;
    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('id', id);

    try {
        const res = await fetch('../Back-end/Civiles.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) loadCiviles();
    } catch (err) { console.error(err); }
}

loadCiviles();