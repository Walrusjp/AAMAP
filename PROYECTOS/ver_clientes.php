<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

include 'C:/xampp/htdocs/PAPELERIA/db_connect.php';

// Obtener clientes de la base de datos
$sql = "SELECT id, nombre, contacto FROM clientes_p";
$result = $conn->query($sql);

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ver Clientes</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>

<div class="container mt-4">
    <h1>Clientes</h1>

    <div>
        <a href="all_projects.php" class="btn btn-secondary mb-3">Regresar</a>
        <a href="reg_client.php" class="btn btn-success mb-3">Crear Cliente</a> 
    </div>

    <table class="table table-bordered">
        <thead class="thead-dark">
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Contacto</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
        <?php
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $row["id"] . "</td>";
                echo "<td>" . htmlspecialchars($row["nombre"]) . "</td>";
                echo "<td>" . htmlspecialchars($row["contacto"]) . "</td>";
                echo "<td>";
                echo "<a href='edit_client.php?id=" . $row["id"] . "' class='btn btn-primary btn-sm mr-2'>Editar</a>";
                echo "<a href='delete_client.php?id=" . $row["id"] . "' class='btn btn-danger btn-sm'>Eliminar</a>";
                echo "</td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='4' class='text-center'>No hay clientes registrados.</td></tr>";
        }
        ?>
        </tbody>
    </table>
</div>

</body>
</html>