<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: /login.php");
    exit();
}

require 'C:/xampp/htdocs/db_connect.php';

// Obtener el id_fab desde la URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "ID de OF no especificado.";
    exit();
}

$id_fab = $_GET['id'];

// Obtener los datos actuales de la OF
$sql = "SELECT * FROM orden_fab WHERE id_fab = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_fab);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "Orden de fabricación no encontrada.";
    exit();
}

$of_data = $result->fetch_assoc();

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $plano_ref = $_POST['plano_ref'];
    $nota = $_POST['nota'];
    $observaciones = $_POST['observaciones'];

    // Actualizar los campos faltantes en orden_fab
    $sqlUpdate = "UPDATE orden_fab SET plano_ref = ?, nota = ?, observaciones = ?, updated_at = NOW() WHERE id_fab = ?";
    $stmtUpdate = $conn->prepare($sqlUpdate);
    $stmtUpdate->bind_param("sssi", $plano_ref, $nota, $observaciones, $id_fab);

    if ($stmtUpdate->execute()) {
        // Cambiar el estado del proyecto a "en proceso"
        $sqlUpdateProyecto = "UPDATE proyectos SET etapa = 'en proceso' WHERE cod_fab = (SELECT id_proyecto FROM orden_fab WHERE id_fab = ?)";
        $stmtUpdateProyecto = $conn->prepare($sqlUpdateProyecto);
        $stmtUpdateProyecto->bind_param("i", $id_fab);

        if ($stmtUpdateProyecto->execute()) {
            $mensaje = "Registro completado y proyecto enviado a 'en proceso'.";
        } else {
            $mensaje = "Error al actualizar el estado del proyecto.";
        }
        $stmtUpdateProyecto->close();
    } else {
        $mensaje = "Error al completar el registro.";
    }

    $stmtUpdate->close();
    $conn->close();

    // Redirigir después de completar el registro
    echo "<script>alert('" . addslashes($mensaje) . "'); window.location.href = 'all_projects.php';</script>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Completar Registro para OF-<?php echo htmlspecialchars($id_fab); ?></title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-4">
    <h1>Completar Registro para <b>OF-<?php echo htmlspecialchars($id_fab); ?></b></h1>
    <form method="POST" action="">
        <div class="form-group">
            <label for="plano_ref">Referencia del Plano:</label>
            <input type="text" class="form-control" id="plano_ref" name="plano_ref" value="<?php echo htmlspecialchars($of_data['plano_ref'] ?? ''); ?>" required>
        </div>
        <div class="form-group">
            <label for="nota">Nota:</label>
            <textarea class="form-control" id="nota" name="nota" rows="3"><?php echo htmlspecialchars($of_data['nota'] ?? ''); ?></textarea>
        </div>
        <div class="form-group">
            <label for="observaciones">Observaciones:</label>
            <textarea class="form-control" id="observaciones" name="observaciones" rows="3"><?php echo htmlspecialchars($of_data['observaciones'] ?? ''); ?></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Completar OF</button>
        <a href="all_projects.php" class="btn btn-secondary">Cancelar</a>
    </form>
</div>
</body>
</html>