<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: /login.php");
    exit();
}

require 'C:/xampp/htdocs/db_connect.php';

// Obtener ID del cliente desde la URL si está presente
$id_cliente = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_cliente = $_POST['id_cliente'];
    $nombre_comprador = $_POST['nombre_comprador'];
    $telefono_comprador = $_POST['telefono_comprador'];
    $correo_comprador = $_POST['correo_comprador'];

    // Insertar el comprador en la tabla compradores
    $sql_comprador = "INSERT INTO compradores (id_cliente, nombre, telefono, correo) 
                      VALUES (?, ?, ?, ?)";
    $stmt_comprador = $conn->prepare($sql_comprador);
    $stmt_comprador->bind_param("isss", $id_cliente, $nombre_comprador, $telefono_comprador, $correo_comprador);

    if ($stmt_comprador->execute()) {
        echo "<script>alert('Comprador registrado exitosamente.'); window.location.href = 'ver_clientes.php';</script>";
    } else {
        echo "<script>alert('Error al registrar el comprador: " . $stmt_comprador->error . "');</script>";
    }
}

// Obtener nombre del cliente si se pasa el ID por URL
$nombre_cliente = '';
if ($id_cliente > 0) {
    $sql = "SELECT nombre_comercial FROM clientes_p WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_cliente);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $nombre_cliente = $row['nombre_comercial'];
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar Comprador</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="icon" href="/assets/logo.png" type="image/png">
</head>
<body>

<div class="container mt-4">
    <h1>Registrar Comprador <?php echo $id_cliente > 0 ? 'para Cliente: ' . htmlspecialchars($nombre_cliente) : ''; ?></h1>
    <a href="ver_clientes.php" class="btn btn-secondary mb-3">Regresar</a>

    <form method="POST" action="reg_comprador.php">
        <!-- Campo oculto para el ID del cliente -->
        <input type="hidden" name="id_cliente" value="<?php echo $id_cliente; ?>">

        <!-- Campos para el comprador -->
        <div class="form-group">
            <label for="nombre_comprador">Nombre del Comprador:</label>
            <input type="text" class="form-control" id="nombre_comprador" name="nombre_comprador" required>
        </div>
        <div class="form-group">
            <label for="telefono_comprador">Teléfono del Comprador:</label>
            <input type="text" class="form-control" id="telefono_comprador" name="telefono_comprador" required>
        </div>
        <div class="form-group">
            <label for="correo_comprador">Correo Electrónico del Comprador:</label>
            <input type="email" class="form-control" id="correo_comprador" name="correo_comprador" required>
        </div>

        <button type="submit" class="btn btn-primary">Registrar Comprador</button>
    </form>
</div>

</body>
</html>