<?php
include("conexion.php");

// Manejo de errores
if (!$conn) {
    die("❌ Error de conexión a la base de datos: " . mysqli_connect_error());
}

$result = mysqli_query($conn, "SELECT id, nombre, email FROM usuarios");

if (!$result) {
    die("❌ Error en la consulta: " . mysqli_error($conn));
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Lista de Usuarios</title>
    <style>
        table {
            border-collapse: collapse;
            width: 70%;
            margin: 20px auto;
        }
        th, td {
            border: 1px solid #999;
            padding: 8px 12px;
            text-align: center;
        }
        th {
            background: #f2f2f2;
        }
        tr:nth-child(even) {
            background: #fafafa;
        }
        a {
            margin: 0 5px;
            text-decoration: none;
            color: #0077cc;
        }
        a:hover {
            text-decoration: underline;
        }
        h2 {
            text-align: center;
        }
    </style>
</head>
<body>
    <h2>📋 Lista de Usuarios</h2>

    <table>
        <tr>
            <th>ID</th><th>Nombre</th><th>Email</th><th>Acciones</th>
        </tr>
        <?php while ($row = mysqli_fetch_assoc($result)) { ?>
            <tr>
                <td><?= htmlspecialchars($row['id']) ?></td>
                <td><?= htmlspecialchars($row['nombre']) ?></td>
                <td><?= htmlspecialchars($row['email']) ?></td>
                <td>
                    <a href="editar.php?id=<?= urlencode($row['id']) ?>">✏️ Editar</a>
                    <a href="eliminar.php?id=<?= urlencode($row['id']) ?>" onclick="return confirm('¿Seguro que deseas eliminar este usuario?');">🗑️ Eliminar</a>
                </td>
            </tr>
        <?php } ?>
    </table>
</body>
</html>
