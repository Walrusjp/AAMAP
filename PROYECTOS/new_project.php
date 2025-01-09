<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

include 'C:/xampp/htdocs/PAPELERIA/db_connect.php';

// Obtener clientes para la lista desplegable
$sqlClientes = "SELECT id, nombre FROM clientes_p";
$resultClientes = $conn->query($sqlClientes);
$clientes = [];
if ($resultClientes->num_rows > 0) {
    while ($row = $resultClientes->fetch_assoc()) {
        $clientes[] = $row;
    }
}

// Verificar si se envió el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cod_fab = $_POST['cod_fab'];
    $nombre = $_POST['nombre'];
    $id_cliente = $_POST['id_cliente'];
    $descripcion = $_POST['descripcion'];
    $id_pedido = $_POST['id_pedido'];
    $estatus = $_POST['estatus'];
    $fecha_entrega = $_POST['fecha_entrega'];
    $pedido = $_POST['pedido'];
    $costo = $_POST['costo'];
    $precio_cliente = $_POST['precio_cliente'];

    // Insertar en la base de datos
    $sql = "INSERT INTO proyectos (cod_fab, nombre, id_cliente, descripcion, id_pedido, estatus, fecha_entrega, pedido, costo, precio_cliente)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        'ssisissdds',
        $cod_fab,
        $nombre,
        $id_cliente,
        $descripcion,
        $id_pedido,
        $estatus,
        $fecha_entrega,
        $pedido,
        $costo,
        $precio_cliente
    );

    if ($stmt->execute()) {
        echo "<script>alert('Proyecto registrado exitosamente.'); window.location.href = 'all_projects.php';</script>";
    } else {
        echo "<script>alert('Error al registrar el proyecto: " . $stmt->error . "');</script>";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nuevo Proyecto</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="icon" href="/assets/logo.ico">
    <link rel="stylesheet" type="text/css" href="allp.css">
</head>
<body>

<div class="container">
    <h1 class="text-center">Nuevo Proyecto</h1>
    <form method="POST" action="new_project.php">
        <div class="form-group">
            <label for="cod_fab">Código de Fabricación</label>
            <input type="text" class="form-control" id="cod_fab" name="cod_fab" required>
        </div>
        <div class="form-group">
            <label for="nombre">Nombre del Proyecto</label>
            <input type="text" class="form-control" id="nombre" name="nombre" required>
        </div>
        <div class="form-group">
            <label for="id_cliente">Cliente</label>
            <select class="form-control" id="id_cliente" name="id_cliente" required>
                <option value="">Seleccionar cliente</option>
                <?php foreach ($clientes as $cliente): ?>
                    <option value="<?php echo $cliente['id']; ?>"><?php echo htmlspecialchars($cliente['nombre']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="descripcion">Descripción</label>
            <textarea class="form-control" id="descripcion" name="descripcion" rows="3"></textarea>
        </div>
        <!--<div class="form-group">
            <label for="id_pedido">ID del Pedido</label>
            <input type="number" class="form-control" id="id_pedido" name="id_pedido" required>
        </div>-->
        <div class="form-group">
            <label for="estatus">Estado</label><br>
            <label><input type="radio" name="estatus" value="en proceso" checked> En proceso</label>
            <label><input type="radio" name="estatus" value="finalizado"> Finalizado</label>
        </div>
        <div class="form-group">
            <label for="fecha_entrega">Fecha de Entrega</label>
            <input type="date" class="form-control" id="fecha_entrega" name="fecha_entrega" required>
        </div>
        <div class="form-group">
            <label for="pedido">Pedido</label>
            <textarea class="form-control" id="pedido" name="pedido" rows="3"></textarea>
        </div>
        <div class="form-group">
            <label for="costo">Costo</label>
            <input type="number" step="0.01" class="form-control" id="costo" name="costo" required>
        </div>
        <div class="form-group">
            <label for="precio_cliente">Precio al Cliente</label>
            <input type="number" step="0.01" class="form-control" id="precio_cliente" name="precio_cliente" required>
        </div>
        <button type="submit" class="btn btn-primary btn-block">Registrar Proyecto</button>
    </form>
</div>

</body>
</html>
