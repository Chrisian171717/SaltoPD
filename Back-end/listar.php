<?php
include("conexion.php");

$result = mysqli_query($conn,"SELECT * FROM usuarios");
?>

<h2> lista de usuario</h2>
<table border="1">
    <tr>
        <th>ID</th><th>Nombre</th><th>Email</th><th>Acciones</th>
    </tr>
    <?php while ($row = mysqli_fetch_array($result)) { ?>
        <tr>
            <td><?php echo $row['id'];?></td>
            <td><?php echo $row['nombre'];?></td>
            <td><?php echo $row['email'];?></td>

            <td>
                <a href="editar.php?id=<?php echo $row['id']; ?>">Editar</a>
                <a href="eliminar.php?id=<?php echo $row['id']; ?>">Eliminar</a>
            </td>
        </tr>
    <?php }?>

</table>