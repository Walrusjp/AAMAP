<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: /login.php");
    exit();
}

require 'C:/xampp/htdocs/db_connect.php';
require 'C:/xampp/htdocs/role.php';

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
    <link rel="icon" href="/assets/logo.ico" type="image/png">
</head>
<body>

<div class="container mt-4">
    <h1>Clientes</h1>

    <div>
        <a href="all_projects.php" class="btn btn-secondary mb-3">Regresar</a>
        <a href="reg_client.php" class="btn btn-success mb-3">Registrar Cliente</a>
        <a href="reg_comprador.php" class="btn btn-success mb-3">Registrar Comprador</a>
        <?php if($username === 'admin'): ?>
            <a href="borrar_cliente.php" class="btn btn-success mb-3">Eliminar cliente</a>
        <?php endif; ?>
    </div>

    <table class="table table-bordered">
    <thead class="thead-dark">
        <tr>
            <th>ID</th>
            <th>N Comercial</th>
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
            // Obtener todos los compradores asociados al cliente
            $comprador_sql = "SELECT nombre, telefono, correo FROM compradores WHERE id_cliente = " . $row["id"];
            $comprador_result = $conn->query($comprador_sql);

            if ($comprador_result->num_rows > 0) {
                $first = true;
                while($comprador = $comprador_result->fetch_assoc()) {
                    echo "<tr>";
                    if ($first) {
                        // Mostrar datos del cliente solo en la primera fila
                        echo "<td rowspan='" . $comprador_result->num_rows . "'>" . $row["id"] . "</td>";
                        echo "<td rowspan='" . $comprador_result->num_rows . "'>" . htmlspecialchars($row["nombre_comercial"]) . "</td>";
                        echo "<td rowspan='" . $comprador_result->num_rows . "'>" . htmlspecialchars($row["razon_social"]) . "</td>";
                        echo "<td rowspan='" . $comprador_result->num_rows . "'>" . htmlspecialchars($row["rfc"]) . "</td>";
                        echo "<td rowspan='" . $comprador_result->num_rows . "'>" . htmlspecialchars($row["direccion"]) . "</td>";
                        $first = false;
                    }
                    // Mostrar datos del comprador
                    echo "<td>" . htmlspecialchars($comprador["nombre"]) . "</td>";
                    echo "<td>" . htmlspecialchars($comprador["telefono"]) . "</td>";
                    echo "<td>" . htmlspecialchars($comprador["correo"]) . "</td>";
                    // Mostrar el botón de "Editar" en cada fila de compradores
                    echo "<td>";
                    echo "<a href='edit_client.php?id=" . $row["id"] . "' class='btn btn-primary btn-sm mr-2'>Editar</a>";
                    //echo "<a href='delete_client.php?id=" . $row["id"] . "' class='btn btn-danger btn-sm'>Eliminar</a>";
                    echo "</td>";
                    echo "</tr>";
                }
            } else {
                // Si no hay compradores, mostrar solo los datos del cliente
                echo "<tr>";
                echo "<td>" . $row["id"] . "</td>";
                echo "<td>" . htmlspecialchars($row["nombre_comercial"]) . "</td>";
                echo "<td>" . htmlspecialchars($row["razon_social"]) . "</td>";
                echo "<td>" . htmlspecialchars($row["rfc"]) . "</td>";
                echo "<td>" . htmlspecialchars($row["direccion"]) . "</td>";
                echo "<td>Sin comprador</td>";
                echo "<td></td>"; // Celda vacía para teléfono
                echo "<td></td>"; // Celda vacía para correo
                echo "<td>"; // Inicio de la celda de Acciones
                echo "<a href='edit_client.php?id=" . $row["id"] . "' class='btn btn-primary btn-sm mr-2'>Editar</a>";
                //echo "<a href='delete_client.php?id=" . $row["id"] . "' class='btn btn-danger btn-sm'>Eliminar</a>";
                echo "</td>"; // Cierre de la celda de Acciones
                echo "</tr>";
            }
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