<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: /login.php");
    exit();
}

require 'C:/xampp/htdocs/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre_comercial = $_POST['nombre_comercial']; // Nombre del campo actualizado
    $razon_social = $_POST['razon_social'];
    $rfc = $_POST['rfc'];
    $direccion = $_POST['direccion'];
    $comprador = $_POST['comprador'];
    $telefono = $_POST['telefono'];
    $correo = $_POST['correo'];

    $sql = "INSERT INTO clientes_p (nombre_comercial, razon_social, rfc, direccion, comprador, telefono, correo) 
            VALUES (?,?,?,?,?,?,?)"; // Consulta actualizada
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssss", $nombre_comercial, $razon_social, $rfc, $direccion, $comprador, $telefono, $correo);

    if ($stmt->execute()) {
        echo "<script>alert('Cliente registrado exitosamente.'); window.location.href = 'ver_clientes.php';</script>"; // Redirigir a ver_clientes.php
    } else {
        echo "<script>alert('Error al registrar el cliente: ". $stmt->error. "');</script>";
    }
}?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar Cliente</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>

<div class="container mt-4">
    <h1>Registrar Cliente</h1>
    <form method="POST" action="reg_client.php">
        <div class="form-group">
            <label for="nombre_comercial">Nombre Comercial:</label> 
            <input type="text" class="form-control" id="nombre_comercial" name="nombre_comercial" required>
        </div>
        <div class="form-group">
            <label for="razon_social">Razón Social:</label>
            <input type="text" class="form-control" id="razon_social" name="razon_social">
        </div>
        <div class="form-group">
            <label for="rfc">RFC:</label>
            <input type="text" class="form-control" id="rfc" name="rfc">
        </div>
        <div class="form-group">
            <label for="direccion">Dirección:</label>
            <input type="text" class="form-control" id="direccion" name="direccion">
        </div>
        <div class="form-group">
            <label for="comprador">Comprador:</label>
            <input type="text" class="form-control" id="comprador" name="comprador">
        </div>
        <div class="form-group">
            <label for="telefono">Teléfono:</label>
            <input type="text" class="form-control" id="telefono" name="telefono">
        </div>
        <div class="form-group">
            <label for="correo">Correo Electrónico:</label>
            <input type="email" class="form-control" id="correo" name="correo">
        </div>
        <button type="submit" class="btn btn-primary">Registrar Cliente</button>
    </form>
</div>

</body>
</html>