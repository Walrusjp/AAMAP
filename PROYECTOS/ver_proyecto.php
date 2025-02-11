<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

include 'C:/xampp/htdocs/PAPELERIA/db_connect.php';

// Validar y sanitizar la entrada del ID del proyecto
$proyecto_id = filter_var($_GET['id'], FILTER_SANITIZE_STRING);
if ($proyecto_id === false || empty($proyecto_id)) {
    echo "ID de proyecto no válido.";
    exit();
}

// Consultar datos del proyecto, incluyendo el pedido
$sql = "SELECT 
        p.cod_fab, 
        c.nombre AS nombre_cliente, 
        p.fecha_entrega,
        p.etapa,
        GROUP_CONCAT(CONCAT(pd.articulos, ' - ', pd.cantidad, ' - ', pd.precio) SEPARATOR '\n') AS pedido
    FROM 
        proyectos p
    LEFT JOIN 
        clientes_p c ON p.id_cliente = c.id
    LEFT JOIN 
        pedidos_p_detalle pd ON pd.id_proyecto = p.cod_fab
    WHERE 
        p.cod_fab = ?
    GROUP BY 
        p.cod_fab, c.nombre, 
        p.fecha_entrega, p.etapa";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo "Error en la preparación de la consulta: " . $conn->error;
    exit();
}
$stmt->bind_param("s", $proyecto_id);

if (!$stmt->execute()) {
    echo "Error al ejecutar la consulta: " . $stmt->error;
    exit();
}

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "Proyecto no encontrado.";
    exit();
}

$proyecto = $result->fetch_assoc();

// Consultar las partidas del proyecto
$sqlPartidas = "SELECT 
        pa.id AS partida, 
        pa.nombre AS nombre_partida, 
        pa.proceso,
        (SELECT re.estatus_log 
         FROM registro_estatus re 
         WHERE re.id_partida = pa.id 
         ORDER BY re.fecha_log DESC 
         LIMIT 1) AS estatus, 
        (SELECT MAX(re.fecha_log) 
         FROM registro_estatus re 
         WHERE re.id_partida = pa.id) AS ultimo_registro
    FROM 
        partidas pa
    WHERE 
        pa.cod_fab = ?";
$stmtPartidas = $conn->prepare($sqlPartidas);
if (!$stmtPartidas) {
    echo "Error en la preparación de la consulta de partidas: " . $conn->error;
    exit();
}
$stmtPartidas->bind_param("s", $proyecto_id);

if (!$stmtPartidas->execute()) {
    echo "Error al ejecutar la consulta de partidas: " . $stmtPartidas->error;
    exit();
}

$resultPartidas = $stmtPartidas->get_result();
$partidas = $resultPartidas->fetch_all(MYSQLI_ASSOC); // Obtener todas las partidas en un array


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
                <th>Pedido</th>
                <th>ID partida</th>
                <th>Descrip Partida</th>
                <th>Proceso</th>
                <th>Estatus</th>
                <th>Última Fecha de Registro</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $numRows = count($partidas); // Obtener el número de partidas
        if (!empty($partidas)) {
            foreach ($partidas as $key => $row) {
                echo "<tr>";
                if ($key === 0) { // Mostrar rowspan solo en la primera fila
                    echo "<td rowspan='" . $numRows . "' class='text-center align-middle'>" . htmlspecialchars($proyecto['cod_fab']) . "</td>";
                    echo "<td rowspan='" . $numRows . "' class='text-center align-middle'>" . htmlspecialchars($proyecto['nombre_cliente']) . "</td>";
                    echo "<td rowspan='" . $numRows . "' class='text-center align-middle'>" . htmlspecialchars($proyecto['fecha_entrega']) . "</td>";
                    // Mostrar la columna "pedido" solo en la primera fila
                    echo "<td rowspan='" . $numRows . "' class='align-middle'>" . nl2br(htmlspecialchars($proyecto['pedido'])) . "</td>";
                }
                echo "<td>" . htmlspecialchars($row['partida']) . "</td>";
                echo "<td>" . htmlspecialchars($row['nombre_partida']) . "</td>";
                echo "<td>" . htmlspecialchars($row['proceso']) . "</td>";
                echo "<td data-id='" . htmlspecialchars($row['partida']) . "' class='editable'>" . htmlspecialchars($row['estatus']) . "</td>";
                echo "<td>" . htmlspecialchars($row['ultimo_registro']) . "</td>";
                echo "</tr>";
            }
        } else {
            echo "<tr>";
            echo "<td colspan='9' class='text-center'>No se encontraron partidas para este proyecto.</td>";
            echo "</tr>";
        }
        ?>
        </tbody>
    </table>

    <div class="mt-4">
        <a href="all_projects.php" class="btn btn-secondary">Regresar</a>
        <a href="ver_logs.php?id=<?php echo urlencode($proyecto['cod_fab']); ?>" class="btn btn-info">Logs</a>
        <?php if ($proyecto['etapa'] == 'en proceso'): ?> 
            <a href="finish_project.php?id=<?php echo urlencode($proyecto['cod_fab']); ?>" class="btn btn-success">Finalizar Proyecto</a>
        <?php endif; ?>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script type="text/javascript">
    $(document).ready(function() {
        // Función para hacer la celda editable
        function makeEditable(td) {
            var originalValue = td.text();
            var input = $("<input type='text'>").val(originalValue);
            td.html(input);
            input.focus();

            // Guardar el cambio al presionar Enter
            input.on('keydown', function(e) {
                if (e.which == 13) { // 13 es el código de la tecla Enter
                    var newValue = $(this).val();
                    var partidaId = td.data('id');
                    updateEstatus(partidaId, newValue, td);
                }
            });

            // Restaurar el valor original si se pierde el foco
            input.on('blur', function() {
                td.html(originalValue);
            });
        }

        // Función para actualizar el estatus en la base de datos y registrar el log
        function updateEstatus(partidaId, nuevoEstatus, td) {
            $.ajax({
                url: 'actualizar_estatus.php', // Este script ahora también registrará el log
                type: 'POST',
                data: { 
                    id: partidaId, 
                    estatus: nuevoEstatus, 
                    id_proyecto: '<?php echo htmlspecialchars($proyecto['cod_fab']); ?>', // Enviamos el id_proyecto
                    id_usuario: '<?php echo $_SESSION['user_id']; ?>' // Enviamos el id_usuario
                },
                success: function(response) {
                    td.html(nuevoEstatus);
                    // Actualizar la última fecha de actualización en la fila correspondiente
                    td.closest('tr').find('td:last').text(response);
                    // Mostrar mensaje de confirmación
                    alert('Estatus actualizado correctamente.');
                },
                error: function() {
                    alert('Error al actualizar el estatus.');
                }
            });
        }

        // Detectar doble clic en las celdas editables
        $('.editable').on('dblclick', function() {
            makeEditable($(this));
        });
    });
</script>
</body>
</html>