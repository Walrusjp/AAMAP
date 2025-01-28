<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

include 'C:/xampp/htdocs/PAPELERIA/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $clientId = $_GET['id'];

    // Obtener datos del cliente
    $sql = "SELECT nombre, contacto FROM clientes_p WHERE id = ?";
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
    $nombre = $_POST['nombre'];
    $contacto = $_POST['contacto'];

    // Actualizar datos del cliente
    $sql = "UPDATE clientes_p SET nombre = ?, contacto = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $nombre, $contacto, $clientId);

    if ($stmt->execute()) {
        echo "<script>alert('Cliente actualizado exitosamente.'); window.location.href = 'ver_clientes.php';</script>"; 
    } else {
        echo "<script>alert('Error al actualizar el cliente: " . $stmt->error . "');</script>";
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
</head>
<body>

<div class="container mt-4">
    <h1>Editar Cliente</h1>
    <form method="POST" action="edit_client.php">
        <input type="hidden" name="id" value="<?php echo $clientId; ?>">
        <div class="form-group">
            <label for="nombre">Nombre del Cliente:</label>
            <input type="text" class="form-control" id="nombre" name="nombre" value="<?php echo htmlspecialchars($cliente['nombre']); ?>" required>
        </div>
        <div class="form-group">
            <label for="contacto">Contacto:</label>
            <input type="text" class="form-control" id="contacto" name="contacto" value="<?php echo htmlspecialchars($cliente['contacto']); ?>">
        </div>
        <button type="submit" class="btn btn-primary">Guardar Cambios</button>
    </form>
</div>

</body>
</html>