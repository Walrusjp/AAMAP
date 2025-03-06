<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

include 'C:/xampp/htdocs/PAPELERIA/db_connect.php';

// Obtener proyectos para el select
$sqlProyectos = "SELECT cod_fab, nombre FROM proyectos";
$resultProyectos = $conn->query($sqlProyectos);
$proyectos = [];
if ($resultProyectos->num_rows > 0) {
    while ($row = $resultProyectos->fetch_assoc()) {
        $proyectos[] = $row;
    }
}

// Procesar la eliminación del proyecto
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['proyecto_id'])) {
    $proyectoId = $_POST['proyecto_id'];

    try {
        $conn->begin_transaction();

        // 1. Eliminar de registro_estatus (usando INNER JOIN para mayor seguridad)
        $sqlDeleteRegistros = "DELETE FROM registro_estatus WHERE id_partida IN (SELECT id FROM partidas WHERE cod_fab = ?)";
        $stmt = $conn->prepare($sqlDeleteRegistros);
        $stmt->bind_param("s", $proyectoId);
        $stmt->execute();

        // 2. Eliminar de partidas (antes de eliminar proyectos)
        $sqlDeletePartidas = "DELETE FROM partidas WHERE cod_fab = ?";
        $stmt = $conn->prepare($sqlDeletePartidas);
        $stmt->bind_param("s", $proyectoId);
        $stmt->execute();

        // 3. Eliminar de datos_vigencia (antes de eliminar proyectos)
        $sqlDeleteVigencia = "DELETE FROM datos_vigencia WHERE cod_fab = ?";
        $stmt = $conn->prepare($sqlDeleteVigencia);
        $stmt->bind_param("s", $proyectoId);
        $stmt->execute();

        // 4. Eliminar de proyectos (después de eliminar partidas y registro_estatus)
        $sqlDeleteProyecto = "DELETE FROM proyectos WHERE cod_fab = ?";
        $stmt = $conn->prepare($sqlDeleteProyecto);
        $stmt->bind_param("s", $proyectoId);
        $stmt->execute();

        $conn->commit();
        echo "<script>alert('Proyecto eliminado exitosamente.'); window.location.href = 'all_projects.php';</script>";

    } catch (Exception $e) {
        $conn->rollback();
        echo "<script>alert('Error al eliminar el proyecto: " . $e->getMessage() . "');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Eliminar Proyecto</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>

<div class="container mt-4">
    <h1>Eliminar Proyecto</h1>
    <form method="POST" action="delete_project.php">
        <div class="form-group">
            <label for="proyecto_id">Seleccionar Proyecto:</label>
            <select class="form-control" id="proyecto_id" name="proyecto_id" required>
                <option value="">Seleccionar proyecto</option>
                <?php foreach ($proyectos as $proyecto): ?>
                    <option value="<?php echo $proyecto['cod_fab']; ?>"><?php echo htmlspecialchars($proyecto['cod_fab']) . " - " . htmlspecialchars($proyecto['nombre']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <a href="all_projects.php" class="btn btn-secondary">Regresar</a>
        <button type="submit" class="btn btn-danger">Eliminar Proyecto</button>
    </form>
</div>

</body>
</html>