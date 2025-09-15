const denunciasBtn = document.querySelector('.denuncia-form__content .btn-primary');
const denunciasList = document.querySelector('.denuncia-cards');

if (denunciasBtn) {
    denunciasBtn.addEventListener('click', async e => {
        e.preventDefault();
        const denunciaText = prompt('Ingrese la denuncia:');
        if (!denunciaText) return;

        const formData = new FormData();
        formData.append('action', 'add');
        formData.append('descripcion', denunciaText);

        try {
            const res = await fetch('../Back-end/Denuncia.php', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) loadDenuncias();
            else alert(data.message || 'Error al agregar denuncia.');
        } catch (err) { console.error(err); }
    });
}

async function loadDenuncias() {
    try {
        const res = await fetch('../Back-end/Denuncia.php?action=list');
        const data = await res.json();
        denunciasList.innerHTML = '';
        data.forEach(d => {
            const card = document.createElement('article');
            card.className = 'denuncia-card';
            card.dataset.id = d.id;
            card.innerHTML = `🔹 <strong>#${d.id}</strong> — ${d.descripcion}
                <button class="edit-denuncia">✏️</button>
                <button class="delete-denuncia">🗑️</button>`;
            denunciasList.appendChild(card);

            card.querySelector('.edit-denuncia').addEventListener('click', () => editDenuncia(d.id));
            card.querySelector('.delete-denuncia').addEventListener('click', () => deleteDenuncia(d.id));
        });
    } catch (err) { console.error(err); }
}

async function editDenuncia(id) {
    const newText = prompt('Editar denuncia:');
    if (!newText) return;
    const formData = new FormData();
    formData.append('action', 'edit');
    formData.append('id', id);
    formData.append('descripcion', newText);

    try {
        const res = await fetch('../Back-end/Denuncia.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) loadDenuncias();
    } catch (err) { console.error(err); }
}

async function deleteDenuncia(id) {
    if (!confirm('¿Eliminar esta denuncia?')) return;
    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('id', id);

    try {
        const res = await fetch('../Back-end/Denuncia.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) loadDenuncias();
    } catch (err) { console.error(err); }
}

loadDenuncias();


// Abrir y cerrar modal
function abrirModal() {
    document.getElementById('modal').style.display = 'block';
    document.getElementById('modalTitle').innerText = 'Agregar Denuncia';
    document.getElementById('formAccion').value = 'agregar';
    document.getElementById('formId').value = '';
    document.getElementById('formNombre').value = '';
    document.getElementById('formCodigo').value = '';
    document.getElementById('formDescripcion').value = '';
}
function cerrarModal() {
    document.getElementById('modal').style.display = 'none';
}

// Editar denuncia
function editarDenuncia(id,nombre,codigo,descripcion){
    abrirModal();
    document.getElementById('modalTitle').innerText = 'Editar Denuncia';
    document.getElementById('formAccion').value = 'editar';
    document.getElementById('formId').value = id;
    document.getElementById('formNombre').value = nombre;
    document.getElementById('formCodigo').value = codigo;
    document.getElementById('formDescripcion').value = descripcion;
}

// Filtrar denuncias en tiempo real
function filtrarDenuncias() {
    const input = document.getElementById('searchInput').value.toLowerCase();
    const cards = document.querySelectorAll('.card');
    cards.forEach(card => {
        const texto = card.textContent.toLowerCase();
        card.style.display = texto.includes(input) ? 'block' : 'none';
    });
}

// Cerrar modal al click fuera
window.onclick = function(event) {
    const modal = document.getElementById('modal');
    if(event.target == modal) modal.style.display = "none";
}


