<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: /login.php");
    exit();
}

require 'C:/xampp/htdocs/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $clientId = $_GET['id'];

    // Obtener datos del cliente
    $sql_cliente = "SELECT * FROM clientes_p WHERE id = ?";
    $stmt_cliente = $conn->prepare($sql_cliente);
    $stmt_cliente->bind_param("i", $clientId);
    $stmt_cliente->execute();
    $result_cliente = $stmt_cliente->get_result();

    if ($result_cliente->num_rows > 0) {
        $cliente = $result_cliente->fetch_assoc();
    } else {
        echo "<script>alert('Cliente no encontrado.'); window.location.href = 'ver_clientes.php';</script>";
        exit();
    }

    // Obtener compradores asociados al cliente
    $sql_compradores = "SELECT id_comprador as 'id', nombre, telefono, correo FROM compradores WHERE id_cliente = ?";
    $stmt_compradores = $conn->prepare($sql_compradores);
    $stmt_compradores->bind_param("i", $clientId);
    $stmt_compradores->execute();
    $result_compradores = $stmt_compradores->get_result();
    $compradores = $result_compradores->fetch_all(MYSQLI_ASSOC);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $clientId = $_POST['id'];
    $nombre_comercial = $_POST['nombre_comercial'];
    $razon_social = $_POST['razon_social'];
    $rfc = $_POST['rfc'];
    $direccion = $_POST['direccion'];
    $activo = isset($_POST['activo']) ? 1 : 0; // Convertir checkbox a 1 o 0

    // Actualizar datos del cliente
    $sql_cliente = "UPDATE clientes_p SET 
                    nombre_comercial = ?, 
                    razon_social = ?, 
                    rfc = ?, 
                    direccion = ?
                WHERE id = ?";
    $stmt_cliente = $conn->prepare($sql_cliente);
    $stmt_cliente->bind_param("ssssi", $nombre_comercial, $razon_social, $rfc, $direccion, $clientId);

    if ($stmt_cliente->execute()) {
        // Actualizar compradores
        if (isset($_POST['compradores'])) {
            foreach ($_POST['compradores'] as $comprador) {
                $comprador_id = $comprador['id'];
                $nombre = $comprador['nombre'];
                $telefono = $comprador['telefono'];
                $correo = $comprador['correo'];

                $sql_comprador = "UPDATE compradores SET 
                                  nombre = ?, 
                                  telefono = ?, 
                                  correo = ? 
                                WHERE id_comprador = ? AND id_cliente = ?";
                $stmt_comprador = $conn->prepare($sql_comprador);
                $stmt_comprador->bind_param("sssii", $nombre, $telefono, $correo, $comprador_id, $clientId);
                $stmt_comprador->execute();
            }
        }

        echo "<script>alert('Cliente y compradores actualizados exitosamente.'); window.location.href = 'ver_clientes.php';</script>";
    } else {
        echo "<script>alert('Error al actualizar el cliente: " . $stmt_cliente->error . "');</script>";
    }
} else {
    echo "<script>alert('ID de cliente no válido.'); window.location.href = 'ver_clientes.php';</script>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Cliente</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="icon" href="/assets/logo.png" type="image/png">
</head>
<body>

<div class="container mt-4">
    <h1>Editar Cliente</h1>
    <a href="ver_clientes.php" class="btn btn-secondary mb-3">Regresar</a>
    <form method="POST" action="edit_client.php">
        <input type="hidden" name="id" value="<?php echo $clientId; ?>">

        <div class="form-group">
            <label for="nombre_comercial">Nombre Comercial:</label>
            <input type="text" class="form-control" id="nombre_comercial" name="nombre_comercial" value="<?php echo htmlspecialchars($cliente['nombre_comercial']); ?>" required>
        </div>
        <div class="form-group">
            <label for="razon_social">Razón Social:</label>
            <input type="text" class="form-control" id="razon_social" name="razon_social" value="<?php echo htmlspecialchars($cliente['razon_social']); ?>">
        </div>
        <div class="form-group">
            <label for="rfc">RFC:</label>
            <input type="text" class="form-control" id="rfc" name="rfc" value="<?php echo htmlspecialchars($cliente['rfc']); ?>">
        </div>
        <div class="form-group">
            <label for="direccion">Dirección:</label>
            <input type="text" class="form-control" id="direccion" name="direccion" value="<?php echo htmlspecialchars($cliente['direccion']); ?>">
        </div>

        <h3>Compradores</h3>
        <div id="compradores">
            <?php foreach ($compradores as $comprador): ?>
                <div class="comprador">
                    <input type="hidden" name="compradores[<?php echo $comprador['id']; ?>][id]" value="<?php echo $comprador['id']; ?>">
                    <div class="form-group">
                        <label>Nombre del Comprador:</label>
                        <input type="text" class="form-control" name="compradores[<?php echo $comprador['id']; ?>][nombre]" value="<?php echo htmlspecialchars($comprador['nombre']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Teléfono del Comprador:</label>
                        <input type="text" class="form-control" name="compradores[<?php echo $comprador['id']; ?>][telefono]" value="<?php echo htmlspecialchars($comprador['telefono']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Correo Electrónico del Comprador:</label>
                        <input type="email" class="form-control" name="compradores[<?php echo $comprador['id']; ?>][correo]" value="<?php echo htmlspecialchars($comprador['correo']); ?>" required>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <button type="submit" class="btn btn-primary">Guardar Cambios</button>
    </form>
</div>

</body>
</html>