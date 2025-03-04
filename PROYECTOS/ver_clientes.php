<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

include 'C:/xampp/htdocs/PAPELERIA/db_connect.php';

// Obtener clientes de la base de datos (todas las columnas)
$sql = "SELECT * FROM clientes_p"; 
$result = $conn->query($sql);

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ver Clientes</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="icon" href="/assets/logo.png" type="image/png">
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
            <th>Nombre Comercial</th>
            <th>Razón Social</th>
            <th>RFC</th>
            <th>Dirección</th>
            <th>Comprador</th>
            <th>Teléfono</th>
            <th>Correo</th>
            <th>Acciones</th> 
        </tr>
    </thead>
    <tbody>
    <?php
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row["id"] . "</td>";
            echo "<td>" . htmlspecialchars($row["nombre_comercial"]) . "</td>";
            echo "<td>" . htmlspecialchars($row["razon_social"]) . "</td>";
            echo "<td>" . htmlspecialchars($row["rfc"]) . "</td>";
            echo "<td>" . htmlspecialchars($row["direccion"]) . "</td>";
            echo "<td>" . htmlspecialchars($row["comprador"]) . "</td>";
            echo "<td>" . htmlspecialchars($row["telefono"]) . "</td>";
            echo "<td>" . htmlspecialchars($row["correo"]) . "</td>";
            echo "<td>"; // Inicio de la celda de Acciones
            echo "<a href='edit_client.php?id=" . $row["id"] . "' class='btn btn-primary btn-sm mr-2'>Editar</a>";
            //echo "<a href='delete_client.php?id=" . $row["id"] . "' class='btn btn-danger btn-sm'>Eliminar</a>";
            echo "</td>"; // Cierre de la celda de Acciones
            echo "</tr>";
        }
    } else {
        echo "<tr><td colspan='9' class='text-center'>No hay clientes registrados.</td></tr>"; 
    }
    ?>
    </tbody>
</table>
</div>

</body>
</html>