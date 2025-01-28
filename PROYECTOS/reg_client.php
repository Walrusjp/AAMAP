<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

include 'C:/xampp/htdocs/PAPELERIA/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'];
    $contacto = $_POST['contacto'];

    $sql = "INSERT INTO clientes_p (nombre, contacto) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $nombre, $contacto);

    if ($stmt->execute()) {
        echo "<script>alert('Cliente registrado exitosamente.'); window.location.href = 'all_projects.php';</script>"; 
    } else {
        echo "<script>alert('Error al registrar el cliente: " . $stmt->error . "');</script>";
    }
}
?>

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
            <label for="nombre">Nombre del Cliente:</label>
            <input type="text" class="form-control" id="nombre" name="nombre" required>
        </div>
        <div class="form-group">
            <label for="contacto">Contacto:</label>
            <input type="text" class="form-control" id="contacto" name="contacto">
        </div>
        <button type="submit" class="btn btn-primary">Registrar Cliente</button>
    </form>
</div>

</body>
</html>