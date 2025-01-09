<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

include 'C:/xampp/htdocs/PAPELERIA/db_connect.php';
include 'C:/xampp/htdocs/PAPELERIA/role.php';

// Verificar si la ventana emergente ya se mostró
if (!isset($_SESSION['welcome_shown'])) {
    $_SESSION['welcome_shown'] = true;
    $showModal = true; 
} else {
    $showModal = false;
}

// Verificar si se solicitó el cierre de sesión
if (isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header("Location: index.php");
    exit();
}

// Verificar si se pasó el ID del proyecto a editar
if (isset($_GET['id'])) {
    $id_proyecto = $_GET['id'];

    // Obtener los datos del proyecto desde la base de datos
    $sql = "SELECT * FROM proyectos WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_proyecto);
    $stmt->execute();
    $result = $stmt->get_result();
    $proyecto = $result->fetch_assoc();

    if (!$proyecto) {
        die("Proyecto no encontrado.");
    }

    // Obtener la lista de clientes para el select
    $sql_clientes = "SELECT * FROM clientes_p";
    $clientes_result = $conn->query($sql_clientes);
} else {
    die("ID del proyecto no especificado.");
}

// Procesar el formulario para actualizar el proyecto
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Recoger los datos del formulario
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

    // Actualizar el proyecto en la base de datos
    $sql_update = "UPDATE proyectos 
                   SET cod_fab = ?, nombre = ?, id_cliente = ?, descripcion = ?, id_pedido = ?, estatus = ?, fecha_entrega = ?, pedido = ?, costo = ?, precio_cliente = ? 
                   WHERE id = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("ssisssssdii", $cod_fab, $nombre, $id_cliente, $descripcion, $id_pedido, $estatus, $fecha_entrega, $pedido, $costo, $precio_cliente, $id_proyecto);

    if ($stmt_update->execute()) {
        // Redirigir a la página de proyectos después de actualizar
        header("Location: all_projects.php");
        exit();
    } else {
        echo "Error al actualizar el proyecto: " . $stmt_update->error;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Proyecto</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="stprojects.css">
    <link rel="icon" href="/assets/logo.ico">
</head>
<body>

<?php if ($showModal): ?>
    <div id="welcomeModal" class="modal">
        <div class="modal-content">
            <h2>Bienvenido, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
            <p>Inicio de sesión exitoso.</p>
            <button class="close-btn" onclick="document.getElementById('welcomeModal').style.display = 'none';">Cerrar</button>
        </div>
    </div>
<?php endif; ?>

<div class="sticky-header">
    <div class="header">
        <h1>Bienvenido, <?php echo htmlspecialchars($_SESSION['username']); ?></h1>
    </div>
    <div class="container d-flex justify-content-between chompa">
        <h2 class="text-center">Editar Proyecto</h2>
        <div class="d-flex justify-content-center mb-3">
            <a href="all_projects.php" class="btn btn-success chompa">Volver a Proyectos</a>
            <form method="POST" action="">
                <button type="submit" name="logout" class="btn btn-danger chompa">Cerrar sesión</button>
            </form>
        </div>
    </div>
</div>

<div class="container">
    <form action="" method="POST">
        <div class="form-row">
            <div class="form-group col-md-6">
                <label for="cod_fab">ID de fabricación</label>
                <input type="text" class="form-control" id="cod_fab" name="cod_fab" value="<?php echo htmlspecialchars($proyecto['cod_fab']); ?>" required>
            </div>
            <div class="form-group col-md-6">
                <label for="nombre">Nombre del Proyecto</label>
                <input type="text" class="form-control" id="nombre" name="nombre" value="<?php echo htmlspecialchars($proyecto['nombre']); ?>" required>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group col-md-6">
                <label for="id_cliente">Cliente</label>
                <select class="form-control" id="id_cliente" name="id_cliente" required>
                    <?php while ($cliente = $clientes_result->fetch_assoc()): ?>
                        <option value="<?php echo $cliente['id']; ?>" <?php if ($cliente['id'] == $proyecto['id_cliente']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($cliente['nombre']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group col-md-6">
                <label for="descripcion">Descripción</label>
                <textarea class="form-control" id="descripcion" name="descripcion" rows="3" required><?php echo htmlspecialchars($proyecto['descripcion']); ?></textarea>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group col-md-6">
                <label for="estatus">Estado de Aprobación</label>
                <select class="form-control" id="estatus" name="estatus" required>
                    <option value="en proceso" <?php if ($proyecto['estatus'] == 'en proceso') echo 'selected'; ?>>En proceso</option>
                    <option value="finalizado" <?php if ($proyecto['estatus'] == 'finalizado') echo 'selected'; ?>>Finalizado</option>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group col-md-6">
                <label for="fecha_entrega">Fecha de entrega</label>
                <input type="date" class="form-control" id="fecha_entrega" name="fecha_entrega" value="<?php echo $proyecto['fecha_entrega']; ?>" required>
            </div>
            <div class="form-group col-md-6">
                <label for="pedido">Pedido</label>
                <textarea class="form-control" id="pedido" name="pedido" rows="3" required><?php echo htmlspecialchars($proyecto['pedido']); ?></textarea>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group col-md-6">
                <label for="costo">Costo</label>
                <input type="number" class="form-control" id="costo" name="costo" step="0.01" value="<?php echo $proyecto['costo']; ?>" required>
            </div>
            <div class="form-group col-md-6">
                <label for="precio_cliente">Precio al Cliente</label>
                <input type="number" class="form-control" id="precio_cliente" name="precio_cliente" step="0.01" value="<?php echo $proyecto['precio_cliente']; ?>" required>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">Guardar Cambios</button>
    </form>
</div>

</body>
</html>
