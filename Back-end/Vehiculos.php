<?php
include("conexion.php");

$action = $_GET['action'] ?? 'list';

$error = "";
$success = "";

if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $matricula = mysqli_real_escape_string($conn, $_POST['matricula']);
    $marca     = mysqli_real_escape_string($conn, $_POST['marca']);
    $modelo    = mysqli_real_escape_string($conn, $_POST['modelo']);
    $civil_id  = (int) $_POST['civil_id'];

    $sql = "INSERT INTO vehiculos (matricula, marca, modelo, civil_id) 
            VALUES ('$matricula', '$marca', '$modelo', $civil_id)";
    if (mysqli_query($conn, $sql)) {
        $success = "Vehículo agregado correctamente.";
        $action = 'list';
    } else {
        $error = "Error al agregar: " . mysqli_error($conn);
    }
}

if ($action === 'edit') {
    $id = (int) ($_GET['id'] ?? 0);
    if ($id <= 0) die("ID inválido.");

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $matricula = mysqli_real_escape_string($conn, $_POST['matricula']);
        $marca     = mysqli_real_escape_string($conn, $_POST['marca']);
        $modelo    = mysqli_real_escape_string($conn, $_POST['modelo']);
        $civil_id  = (int) $_POST['civil_id'];

        $sql = "UPDATE vehiculos SET matricula='$matricula', marca='$marca', modelo='$modelo', civil_id=$civil_id 
                WHERE id=$id";
        if (mysqli_query($conn, $sql)) {
            $success = "Vehículo actualizado correctamente.";
            $action = 'list';
        } else {
            $error = "Error al actualizar: " . mysqli_error($conn);
        }
    } else {
        $res = mysqli_query($conn, "SELECT * FROM vehiculos WHERE id=$id");
        $vehiculo = mysqli_fetch_assoc($res);
        if (!$vehiculo) die("Vehículo no encontrado.");
    }
}

if ($action === 'delete') {
    $id = (int) ($_GET['id'] ?? 0);
    if ($id > 0) {
        $sql = "DELETE FROM vehiculos WHERE id=$id";
        if (mysqli_query($conn, $sql)) {
            $success = "Vehículo eliminado correctamente.";
        } else {
            $error = "Error al eliminar: " . mysqli_error($conn);
        }
    }
    $action = 'list';
}

if ($action === 'list') {
    $res = mysqli_query($conn, "SELECT v.id, v.matricula, v.marca, v.modelo, c.nombre AS civil 
                                FROM vehiculos v
                                LEFT JOIN civiles c ON v.civil_id=c.id
                                ORDER BY v.id DESC");
    $vehiculos = mysqli_fetch_all($res, MYSQLI_ASSOC);
}

$civiles = mysqli_query($conn, "SELECT id, nombre FROM civiles ORDER BY nombre");

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>CRUD Vehículos</title>
    <style>
        table {border-collapse: collapse; width: 80%; margin: 20px auto;}
        th, td {border: 1px solid #999; padding: 8px; text-align: center;}
        th {background: #f2f2f2;}
        tr:nth-child(even) {background: #fafafa;}
        a {margin: 0 5px; text-decoration: none; color: #0077cc;}
        a:hover {text-decoration: underline;}
        form {width: 50%; margin: 20px auto;}
        label {display: block; margin-top: 10px;}
    </style>
</head>
<body>
    <h2 style="text-align:center;">CRUD Vehículos</h2>

    <?php if($error) echo "<p style='color:red;text-align:center;'>$error</p>"; ?>
    <?php if($success) echo "<p style='color:green;text-align:center;'>$success</p>"; ?>

    <?php if($action==='list'): ?>
        <p style="text-align:center;"><a href="?action=add">➕ Agregar nuevo vehículo</a></p>
        <table>
            <tr>
                <th>ID</th><th>Matrícula</th><th>Marca</th><th>Modelo</th><th>Civil</th><th>Acciones</th>
            </tr>
            <?php foreach($vehiculos as $v): ?>
                <tr>
                    <td><?= $v['id'] ?></td>
                    <td><?= htmlspecialchars($v['matricula']) ?></td>
                    <td><?= htmlspecialchars($v['marca']) ?></td>
                    <td><?= htmlspecialchars($v['modelo']) ?></td>
                    <td><?= htmlspecialchars($v['civil']) ?></td>
                    <td>
                        <a href="?action=edit&id=<?= $v['id'] ?>">Editar</a>
                        <a href="?action=delete&id=<?= $v['id'] ?>" onclick="return confirm('¿Seguro?');">Eliminar</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>

    <?php elseif($action==='add' || $action==='edit'): 
        $vehiculo = $vehiculo ?? ['matricula'=>'','marca'=>'','modelo'=>'','civil_id'=>0];
    ?>
        <form method="POST">
            <label>Matrícula:</label>
            <input type="text" name="matricula" value="<?= htmlspecialchars($vehiculo['matricula']) ?>" required>

            <label>Marca:</label>
            <input type="text" name="marca" value="<?= htmlspecialchars($vehiculo['marca']) ?>" required>

            <label>Modelo:</label>
            <input type="text" name="modelo" value="<?= htmlspecialchars($vehiculo['modelo']) ?>" required>

            <label>Civil:</label>
            <select name="civil_id" required>
                <option value="">-- Selecciona --</option>
                <?php while($c = mysqli_fetch_assoc($civiles)): ?>
                    <option value="<?= $c['id'] ?>" <?= ($c['id']==$vehiculo['civil_id'])?'selected':'' ?>>
                        <?= htmlspecialchars($c['nombre']) ?>
                    </option>
                <?php endwhile; ?>
            </select><br><br>

            <button type="submit"><?= $action==='add'?'Agregar':'Actualizar' ?></button>
            <a href="?action=list">Cancelar</a>
        </form>
    <?php endif; ?>
</body>
</html>
