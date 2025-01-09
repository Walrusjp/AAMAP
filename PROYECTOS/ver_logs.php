<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

include 'C:/xampp/htdocs/PAPELERIA/db_connect.php';

// Obtener el ID del proyecto desde la URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "Proyecto no especificado.";
    exit();
}

$proyecto_id = intval($_GET['id']);

// Consultar todos los registros de estatus del proyecto
$sql = "SELECT 
            id, 
            estatus_log, 
            fecha_log 
        FROM registro_estatus 
        WHERE id_proyecto = ? 
        ORDER BY fecha_log DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $proyecto_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "No se encontraron logs para este proyecto.";
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Logs del Proyecto</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="stprojects.css">
</head>
<body>

<div class="container mt-4">
    <h1>Logs del Proyecto</h1>
    <table class="table table-bordered">
        <thead class="thead-dark">
            <tr>
                <th>ID</th>
                <th>Estatus</th>
                <th>Fecha de Registro</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($log = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($log['id']); ?></td>
                    <td><?php echo htmlspecialchars($log['estatus_log']); ?></td>
                    <td><?php echo htmlspecialchars($log['fecha_log']); ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <div class="mt-4">
        <a href="ver_proyecto.php?id=<?php echo urlencode($proyecto_id); ?>" class="btn btn-secondary">Regresar</a>
    </div>
</div>

</body>
</html>
