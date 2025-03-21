<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: /login.php");
    exit();
}

require 'C:/xampp/htdocs/db_connect.php';

// Obtener órdenes de fabricación (orden_fab) para el select
$sqlOrdenFab = "SELECT of.id_fab, p.nombre 
                FROM orden_fab of
                INNER JOIN proyectos p ON of.id_proyecto = p.cod_fab";
$resultOrdenFab = $conn->query($sqlOrdenFab);
$ordenesFab = [];
if ($resultOrdenFab->num_rows > 0) {
    while ($row = $resultOrdenFab->fetch_assoc()) {
        $ordenesFab[] = $row;
    }
}

// Procesar la eliminación de la orden de fabricación
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_fab'])) {
    $id_fab = $_POST['id_fab'];

    try {
        $conn->begin_transaction();

        // 1. Eliminar registros de registro_estatus relacionados con orden_fab
        $sqlDeleteRegistros = "DELETE FROM registro_estatus WHERE id_fab = ?";
        $stmt = $conn->prepare($sqlDeleteRegistros);
        $stmt->bind_param("i", $id_fab);
        $stmt->execute();

        // 2. Eliminar registros de orden_fab
        $sqlDeleteOrdenFab = "DELETE FROM orden_fab WHERE id_fab = ?";
        $stmt = $conn->prepare($sqlDeleteOrdenFab);
        $stmt->bind_param("i", $id_fab);
        $stmt->execute();

        $conn->commit();
        echo "<script>alert('Registros de orden_fab y logs eliminados exitosamente.'); window.location.href = 'all_projects.php';</script>";

    } catch (Exception $e) {
        $conn->rollback();
        echo "<script>alert('Error al eliminar los registros: " . $e->getMessage() . "');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Eliminar Registros de Orden de Fabricación</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>

<div class="container mt-4">
    <h1>Eliminar Registros de Orden de Fabricación</h1>
    <form method="POST" action="delete_project.php">
        <div class="form-group">
            <label for="id_fab">Seleccionar Orden de Fabricación:</label>
            <select class="form-control" id="id_fab" name="id_fab" required>
                <option value="">Seleccionar orden de fabricación</option>
                <?php foreach ($ordenesFab as $orden): ?>
                    <option value="<?php echo $orden['id_fab']; ?>"><?php echo "OF-" . htmlspecialchars($orden['id_fab']) . " - " . htmlspecialchars($orden['nombre']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <a href="all_projects.php" class="btn btn-secondary">Regresar</a>
        <button type="submit" class="btn btn-danger">Eliminar Registros</button>
    </form>
</div>

</body>
</html>