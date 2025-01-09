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

$proyecto_id = ($_GET['id']);
//var_dump($_GET['id']);

// Comprobamos si se ha enviado el formulario para registrar la nueva partida
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener los datos del formulario para la nueva partida
    $mac = $_POST['mac'];
    $man = $_POST['man'];
    $com = $_POST['com'];
    
    // Obtener la fecha actual para el campo fecha_log
    $fecha_log = date('Y-m-d H:i:s'); // La fecha actual del servidor

    // Insertar la nueva partida en la base de datos
    $insertSql = "INSERT INTO partidas (cod_fab, mac, man, com) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($insertSql);
    $stmt->bind_param("ssss", $proyecto_id, $mac, $man, $com);
    $stmt->execute();

    // Obtener el ID de la nueva partida insertada
    $partida_id = $stmt->insert_id;

    // Ahora insertar el primer registro en la tabla de registro_estatus
    $estatus_log = 'sin registro';  // Valor por defecto
    $insertEstatusSql = "INSERT INTO registro_estatus (id_partida, estatus_log, fecha_log) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($insertEstatusSql);
    $stmt->bind_param("iss", $partida_id, $estatus_log, $fecha_log);
    $stmt->execute();

    // Redirigir a la página donde se muestran las partidas o mostrar un mensaje de éxito
    header("Location: detalle_proyecto.php?id=" . $proyecto_id);
    exit();
}

// Consultar datos del proyecto junto con las partidas
$sql = "SELECT 
        p.cod_fab, 
        c.nombre AS nombre_cliente, 
        pa.id AS partida, 
        pa.nombre AS nombre_partida, 
        pa.mac AS maq, 
        pa.man, 
        pa.com, 
        p.fecha_entrega, 
        (SELECT re.estatus_log 
         FROM registro_estatus re 
         WHERE re.id_partida = pa.id 
         ORDER BY re.fecha_log DESC 
         LIMIT 1) AS estatus, 
        (SELECT MAX(re.fecha_log) 
         FROM registro_estatus re 
         WHERE re.id_partida = pa.id) AS ultimo_registro
    FROM 
        proyectos p
    LEFT JOIN 
        clientes_p c ON p.id_cliente = c.id
    LEFT JOIN 
        partidas pa ON pa.cod_fab = p.cod_fab
    WHERE 
        p.cod_fab = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $proyecto_id); // Usamos 's' para pasar el parámetro como cadena
$stmt->execute();
$result = $stmt->get_result();


if ($result->num_rows === 0) {
    echo "Proyecto no encontrado.";
    exit();
}

$proyecto = $result->fetch_assoc();

?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ver Proyecto</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="stprojects.css">
    <link rel="icon" href="/assets/logo.ico">
</head>
<body>

<div class="container mt-4">
    <h1>Detalles del Proyecto</h1>
    <table class="table table-bordered">
        <thead class="thead-dark">
            <tr>
                <th>OF</th>
                <th>Cliente</th>
                <th>F.E</th>
                <th>ID Partida</th>
                <th>Partida</th>
                <th>COM</th>
                <th>MAN</th>
                <th>MAQ</th>
                <th>Estatus</th>
                <th>Última Fecha de Registro</th>
            </tr>
        </thead>
        <tbody>
    <?php
    // Vuelve a ejecutar la consulta para obtener todas las partidas
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Control para la primera fila
    $isFirstRow = true;

    while ($row = $result->fetch_assoc()) {
    echo "<tr>";

    if ($isFirstRow) {
        echo "<td rowspan='" . $result->num_rows . "' class='text-center align-middle'>" . htmlspecialchars($row['cod_fab']) . "</td>";
        echo "<td rowspan='" . $result->num_rows . "' class='text-center align-middle'>" . htmlspecialchars($row['nombre_cliente']) . "</td>";
        echo "<td rowspan='" . $result->num_rows . "' class='text-center align-middle'>" . htmlspecialchars($row['fecha_entrega']) . "</td>";
    }

    // Mostrar el ID y nombre de la partida
    echo "<td>" . htmlspecialchars($row['partida']) . "</td>";
    echo "<td>" . htmlspecialchars($row['nombre_partida']) . "</td>";

    $com = $row['com'] == 1 ? "&#10004;" : "&#10008;";
    $man = $row['man'] == 1 ? "&#10004;" : "&#10008;";
    $maq = $row['maq'] == 1 ? "&#10004;" : "&#10008;";

    echo "<td>" . $com . "</td>";
    echo "<td>" . $man . "</td>";
    echo "<td>" . $maq . "</td>";
    echo "<td>" . htmlspecialchars($row['estatus']) . "</td>";
    echo "<td>" . htmlspecialchars($row['ultimo_registro']) . "</td>";

    echo "</tr>";
    $isFirstRow = false;
}

    ?>
</tbody>
    </table>

    <div class="mt-4">
    <a href="all_projects.php" class="btn btn-secondary">Regresar</a>
    <a href="edit_project.php?id=<?php echo urlencode($proyecto['cod_fab']); ?>" class="btn btn-primary">Editar</a>
    <a href="ver_logs.php?id=<?php echo urlencode($proyecto['cod_fab']); ?>" class="btn btn-info">Logs</a>
    <a href="crear_partida.php?id=<?php echo urlencode($proyecto['cod_fab']); ?>" class="btn btn-success">Registrar Partida</a>
</div>
</div>

</body>
</html>
