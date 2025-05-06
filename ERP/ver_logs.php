<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: /login.php");
    exit();
}

require 'C:/xampp/htdocs/db_connect.php';

// Obtener el ID del proyecto desde la URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "Proyecto no especificado.";
    exit();
}

$proyecto_id = $_GET['id']; // Este es el id_fab de orden_fab

// Consultar los logs del proyecto
$sql = "SELECT 
            re.id, 
            u.username AS nombre_usuario, 
            p.descripcion AS nombre_partida, 
            re.estatus_log, 
            re.fecha_log
        FROM registro_estatus re
        INNER JOIN partidas p ON re.id_partida = p.id
        INNER JOIN users u ON re.id_usuario = u.id
        WHERE re.id_fab = ?
        ORDER BY re.fecha_log DESC";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Error en la preparación de la consulta: " . $conn->error);
}
$stmt->bind_param("s", $proyecto_id);
$stmt->execute();
$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Logs OF- <?php echo htmlspecialchars($proyecto_id) ?></title>
    <link rel="icon" href="/assets/logo.ico">
    <link rel="stylesheet" href="slogs.css">
</head>
<body>

<div class="container">
    <h1>Logs del Proyecto <b>OF-<?php echo htmlspecialchars($proyecto_id); ?></b></h1>
    <a href="ver_proyecto.php?id=<?php echo urlencode($proyecto_id); ?>" class="btn">Regresar</a>

    <div class="table-container">
        <table>
            <thead>
                <tr id="thead">
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
    </div>
</div>

</body>
</html>