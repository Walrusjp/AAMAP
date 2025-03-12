<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: /login.php");
    exit();
}

require 'C:/xampp/htdocs/db_connect.php';

// Obtener clientes activos para el select
$sql_clientes = "SELECT id, nombre_comercial FROM clientes_p";
$result_clientes = $conn->query($sql_clientes);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_cliente = $_POST['id_cliente'];

    // Eliminar compradores asociados al cliente
    $sql_borrar_compradores = "DELETE FROM compradores WHERE id_cliente = ?";
    $stmt_borrar_compradores = $conn->prepare($sql_borrar_compradores);
    $stmt_borrar_compradores->bind_param("i", $id_cliente);

    // Eliminar el cliente
    $sql_borrar_cliente = "DELETE FROM clientes_p WHERE id = ?";
    $stmt_borrar_cliente = $conn->prepare($sql_borrar_cliente);
    $stmt_borrar_cliente->bind_param("i", $id_cliente);

    // Iniciar transacción para asegurar la atomicidad
    $conn->begin_transaction();

    try {
        // Borrar compradores
        $stmt_borrar_compradores->execute();

        // Borrar cliente
        $stmt_borrar_cliente->execute();

        // Confirmar la transacción
        $conn->commit();

        echo "<script>alert('Cliente y compradores asociados eliminados exitosamente.'); window.location.href = 'ver_clientes.php';</script>";
    } catch (Exception $e) {
        // Revertir la transacción en caso de error
        $conn->rollback();
        echo "<script>alert('Error al eliminar el cliente y sus compradores: " . $e->getMessage() . "');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Borrar Cliente</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="icon" href="/assets/logo.png" type="image/png">
</head>
<body>

<div class="container mt-4">
    <h1>Borrar Cliente</h1>
    <a href="ver_clientes.php" class="btn btn-secondary mb-3">Regresar</a>

    <form method="POST" action="borrar_cliente.php">
        <!-- Select para elegir cliente -->
        <div class="form-group">
            <label for="id_cliente">Seleccionar Cliente:</label>
            <select class="form-control" id="id_cliente" name="id_cliente" required>
                <option value="">Seleccione un cliente</option>
                <?php
                if ($result_clientes->num_rows > 0) {
                    while ($row = $result_clientes->fetch_assoc()) {
                        echo "<option value='" . $row['id'] . "'>" . htmlspecialchars($row['nombre_comercial']) . "</option>";
                    }
                } else {
                    echo "<option value=''>No hay clientes disponibles</option>";
                }
                ?>
            </select>
        </div>

        <!-- Botón para borrar cliente -->
        <button type="submit" class="btn btn-danger">Borrar Cliente</button>
    </form>
</div>

</body>
</html>