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

$proyecto_id = $_GET['id'];

// Consultar los logs del proyecto, incluyendo el nombre de usuario y la descripción de la partida
$sql = "SELECT re.id, u.username AS nombre_usuario, p.descripcion AS nombre_partida, re.estatus_log, re.fecha_log
        FROM registro_estatus re
        INNER JOIN partidas p ON re.id_partida = p.id
        INNER JOIN users u ON re.id_usuario = u.id
        WHERE p.cod_fab = ?  -- Filtrar por codigo de fabricacion de la partida
        ORDER BY re.fecha_log DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $proyecto_id);
$stmt->execute();
$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ver Logs del Proyecto</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>

<div class="container mt-4">
    <h1>Logs del Proyecto <?php echo htmlspecialchars($proyecto_id); ?></h1>

    <table class="table table-bordered">
        <thead class="thead-dark">
            <tr>
                <th>ID Log</th>
                <th>Usuario</th>
                <th>Partida</th>
                <th>Estatus</th>
                <th>Fecha de Registro</th>
            </tr>
        </thead>
        <tbody>
        <?php
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $row["id"] . "</td>";
                echo "<td>" . htmlspecialchars($row["nombre_usuario"]) . "</td>";
                echo "<td>" . htmlspecialchars($row["nombre_partida"]) . "</td>";
                echo "<td>" . htmlspecialchars($row["estatus_log"]) . "</td>";
                echo "<td>" . htmlspecialchars($row["fecha_log"]) . "</td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='5' class='text-center'>No hay logs registrados para este proyecto.</td></tr>";
        }
        ?>
        </tbody>
    </table>

    <a href="ver_proyecto.php?id=<?php echo urlencode($proyecto_id); ?>" class="btn btn-secondary mt-3">Regresar</a>
</div>

</body>
</html>