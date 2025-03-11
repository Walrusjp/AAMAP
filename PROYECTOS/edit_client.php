<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: /login.php");
    exit();
}

require 'C:/xampp/htdocs/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $clientId = $_GET['id'];

    // Obtener datos del cliente (todas las columnas)
    $sql = "SELECT * FROM clientes_p WHERE id =?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $clientId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $cliente = $result->fetch_assoc();
    } else {
        echo "<script>alert('Cliente no encontrado.'); window.location.href = 'ver_clientes.php';</script>";
        exit();
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $clientId = $_POST['id'];
    $nombre_comercial = $_POST['nombre_comercial'];
    $razon_social = $_POST['razon_social'];
    $rfc = $_POST['rfc'];
    $direccion = $_POST['direccion'];
    $comprador = $_POST['comprador'];
    $telefono = $_POST['telefono'];
    $correo = $_POST['correo'];
    $activo = isset($_POST['activo'])? 1: 0; // Convertir checkbox a 1 o 0

    // Actualizar datos del cliente
    $sql = "UPDATE clientes_p SET 
                nombre_comercial =?, 
                razon_social =?, 
                rfc =?, 
                direccion =?, 
                comprador =?, 
                telefono =?, 
                correo =?, 
                activo =? 
            WHERE id =?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssssii", $nombre_comercial, $razon_social, $rfc, $direccion, $comprador, $telefono, $correo, $activo, $clientId);

    if ($stmt->execute()) {
        echo "<script>alert('Cliente actualizado exitosamente.'); window.location.href = 'ver_clientes.php';</script>"; 
    } else {
        echo "<script>alert('Error al actualizar el cliente: ". $stmt->error. "');</script>";
    }
} else {
    echo "<script>alert('ID de cliente no válido.'); window.location.href = 'ver_clientes.php';</script>";
    exit();
}?>

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
        <input type="hidden" name="id" value="<?php echo $clientId;?>">

        <div class="form-group">
            <label for="nombre_comercial">Nombre Comercial:</label>
            <input type="text" class="form-control" id="nombre_comercial" name="nombre_comercial" value="<?php echo htmlspecialchars($cliente['nombre_comercial']);?>" required>
        </div>
        <div class="form-group">
            <label for="razon_social">Razón Social:</label>
            <input type="text" class="form-control" id="razon_social" name="razon_social" value="<?php echo htmlspecialchars($cliente['razon_social']);?>">
        </div>
        <div class="form-group">
            <label for="rfc">RFC:</label>
            <input type="text" class="form-control" id="rfc" name="rfc" value="<?php echo htmlspecialchars($cliente['rfc']);?>">
        </div>
        <div class="form-group">
            <label for="direccion">Dirección:</label>
            <input type="text" class="form-control" id="direccion" name="direccion" value="<?php echo htmlspecialchars($cliente['direccion']);?>">
        </div>
        <div class="form-group">
            <label for="comprador">Comprador:</label>
            <input type="text" class="form-control" id="comprador" name="comprador" value="<?php echo htmlspecialchars($cliente['comprador']);?>">
        </div>
        <div class="form-group">
            <label for="telefono">Teléfono:</label>
            <input type="text" class="form-control" id="telefono" name="telefono" value="<?php echo htmlspecialchars($cliente['telefono']);?>">
        </div>
        <div class="form-group">
            <label for="correo">Correo Electrónico:</label>
            <input type="email" class="form-control" id="correo" name="correo" value="<?php echo htmlspecialchars($cliente['correo']);?>">
        </div>

        <button type="submit" class="btn btn-primary">Guardar Cambios</button>
    </form>
</div>

</body>
</html>