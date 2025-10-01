$(document).ready(function () {
    // Agregar denuncia
    $("#form-agregar-denuncia").submit(function (e) {
        e.preventDefault();
        $.post("denuncias_api.php", $(this).serialize() + "&accion=agregar", function (res) {
            if (res.status === "ok") {
                $("#mensaje-form").html("<p style='color:green'>Denuncia agregada con éxito</p>");
                cargarDenuncias();
                $("#form-agregar-denuncia")[0].reset();
            } else {
                $("#mensaje-form").html("<p style='color:red'>" + res.mensaje + "</p>");
            }
        }, "json");
    });

    // Buscar denuncias
    $("#form-buscar-denuncia").submit(function (e) {
        e.preventDefault();
        $.post("denuncias_api.php", $(this).serialize() + "&accion=buscar", function (res) {
            if (res.status === "ok") {
                renderTabla(res.data);
            }
        }, "json");
    });

    // Cargar denuncias al inicio
    cargarDenuncias();
});

function cargarDenuncias() {
    $.get("denuncias_api.php", {accion: "listar"}, function (res) {
        if (res.status === "ok") {
            renderTabla(res.data);
        }
    }, "json");
}

function renderTabla(data) {
    if (!data || data.length === 0) {
        $("#tabla-denuncias").html("<p>No se encontraron denuncias</p>");
        return;
    }

    let html = "<table border='1' cellpadding='6'><tr><th>ID</th><th>Nombre</th><th>Código Penal</th><th>Descripción</th><th>Fecha</th><th>Acciones</th></tr>";
    data.forEach(d => {
        html += `<tr>
            <td>${d.id}</td>
            <td>${d.nombre_civil}</td>
            <td>${d.CodigoPenal}</td>
            <td>${d.descripcion}</td>
            <td>${d.Fecha}</td>
            <td>
                <button onclick="editarDenuncia(${d.id}, '${d.nombre_civil}', '${d.CodigoPenal}', '${d.descripcion}')">Editar</button>
                <button onclick="eliminarDenuncia(${d.id})">Eliminar</button>
            </td>
        </tr>`;
    });
    html += "</table>";

    $("#tabla-denuncias").html(html);
}

function eliminarDenuncia(id) {
    if (!confirm("¿Seguro que querés eliminar esta denuncia?")) return;
    $.post("denuncias_api.php", {accion: "eliminar", id: id}, function (res) {
        if (res.status === "ok") {
            cargarDenuncias();
        } else {
            alert("Error: " + res.mensaje);
        }
    }, "json");
}

function editarDenuncia(id, nombre, codigo, descripcion) {
    const nuevoNombre = prompt("Editar nombre:", nombre);
    const nuevoCodigo = prompt("Editar código penal:", codigo);
    const nuevaDesc = prompt("Editar descripción:", descripcion);

    if (nuevoNombre && nuevoCodigo && nuevaDesc) {
        $.post("denuncias_api.php", {
            accion: "editar",
            id: id,
            nombre_civil: nuevoNombre,
            codigo_penal: nuevoCodigo,
            descripcion: nuevaDesc
        }, function (res) {
            if (res.status === "ok") {
                cargarDenuncias();
            } else {
                alert("Error: " + res.mensaje);
            }
        }, "json");
    }
}
