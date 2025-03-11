<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: /login.php");
    exit();
}

require 'C:/xampp/htdocs/db_connect.php';

echo "<pre>";
var_dump($_GET);
echo "</pre>";
echo "<script>
    console.log('ID del proyecto desde GET:', " . $_GET['id_proyecto'] . ");
</script>";


// Verificar si se pasó el cod_fab del proyecto en la URL
if (isset($_GET['id_proyecto']) && !empty($_GET['id_proyecto'])) {
    $id_proyecto = $_GET['id_proyecto'];

    // Obtener datos del proyecto para mostrar en el formulario de finalizar pedido
    $sqlProyecto = "SELECT * FROM proyectos WHERE cod_fab = ?";
    $stmt = $conn->prepare($sqlProyecto);
    $stmt->bind_param('s', $id_proyecto); // Usamos 's' para 'cod_fab' que es varchar
    $stmt->execute();
    $result = $stmt->get_result();
    $proyecto = $result->fetch_assoc();

    if (!$proyecto) {
        echo "No se ha encontrado el proyecto con código: $id_proyecto.";
        exit();
    }
} else {
    echo "No se ha recibido un ID de proyecto válido.";
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Finalizar Pedido</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>

<div class="container">
    <h1 class="text-center">Finalizar Pedido para Proyecto: <?php echo htmlspecialchars($proyecto['nombre']); ?></h1>
    <form method="POST" action="finalizar_pedido.php?id_proyecto=<?php echo $id_proyecto; ?>">
        <div class="form-group">
            <label for="estatus">Estatus</label>
            <input type="text" class="form-control" id="estatus" name="estatus" required>
        </div>
        <div class="form-group">
            <label for="fecha_entrega">Fecha de Entrega</label>
            <input type="date" class="form-control" id="fecha_entrega" name="fecha_entrega" required>
        </div>
        <div class="form-group">
            <label for="pedido">Pedido</label>
            <textarea class="form-control" id="pedido" name="pedido" rows="3" required></textarea>
        </div>
        <div class="form-group">
            <label for="costo">Costo</label>
            <input type="number" class="form-control" id="costo" name="costo" required>
        </div>
        <div class="form-group">
            <label for="precio_cliente">Precio Cliente</label>
            <input type="number" class="form-control" id="precio_cliente" name="precio_cliente" required>
        </div>

        <button type="submit" class="btn btn-success btn-block">Finalizar Pedido</button>
    </form>
</div>

</body>
</html>
