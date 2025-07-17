<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: /login.php");
    exit();
}

require 'C:/xampp/htdocs/db_connect.php';

// Obtener el ID de la OC desde la URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "Orden de compra no especificada.";
    exit();
}

$oc_id = $_GET['id'];

// Obtener información básica de la OC para el título
$sql_oc = "SELECT oc.folio, pr.empresa 
           FROM ordenes_compra oc
           LEFT JOIN proveedores pr ON oc.id_pr = pr.id_pr
           WHERE oc.id_oc = ?";
$stmt_oc = $conn->prepare($sql_oc);
$stmt_oc->bind_param("i", $oc_id);
$stmt_oc->execute();
$result_oc = $stmt_oc->get_result();
$oc_info = $result_oc->fetch_assoc();

// Consultar los logs de la orden de compra
$sql = "SELECT 
            l.id_log,
            u.username AS nombre_usuario,
            l.estatus,
            l.observaciones,
            l.fecha_cambio
        FROM logs_estatus_oc l
        INNER JOIN users u ON l.id_usuario = u.id
        WHERE l.id_oc = ?
        ORDER BY l.fecha_cambio DESC";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Error en la preparación de la consulta: " . $conn->error);
}
$stmt->bind_param("i", $oc_id);
$stmt->execute();
$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Logs de OC-<?php echo htmlspecialchars($oc_id) ?>: <?php echo htmlspecialchars($oc_info['folio'] ?? ''); ?></title>
    <link rel="icon" href="/assets/logo.ico">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f8f9fa;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            margin-bottom: 20px;
        }
        .btn {
            display: inline-block;
            padding: 8px 15px;
            background-color: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .btn:hover {
            background-color: #5a6268;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #343a40;
            color: white;
        }
        tr:hover {
            background-color: #f5f5f5;
        }
        .text-center {
            text-align: center;
        }
        .table-responsive {
            overflow-x: auto;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>Logs de OC-<?php echo htmlspecialchars($oc_id); ?>: <b><?php echo htmlspecialchars($oc_info['folio'] ?? ''); ?></b> - <?php echo htmlspecialchars($oc_info['empresa'] ?? ''); ?></h1>
    <a href="detalle_oc.php?id=<?php echo urlencode($oc_id); ?>" class="btn">Regresar</a>

    <div class="table-responsive">
        <table class="table table-striped">
            <thead class="thead-dark">
                <tr>
                    <th>ID Log</th>
                    <th>Usuario</th>
                    <th>Estatus</th>
                    <th>Observaciones</th>
                    <th>Fecha de Registro</th>
                </tr>
            </thead>
            <tbody>
            <?php
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row["id_log"]) . "</td>";
                    echo "<td>" . htmlspecialchars($row["nombre_usuario"]) . "</td>";
                    echo "<td>" . htmlspecialchars($row["estatus"]) . "</td>";
                    echo "<td>" . htmlspecialchars($row["observaciones"] ?? 'N/A') . "</td>";
                    echo "<td>" . htmlspecialchars($row["fecha_cambio"]) . "</td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='5' class='text-center'>No hay logs registrados para esta orden de compra.</td></tr>";
            }
            ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>
<?php
$conn->close();
?>