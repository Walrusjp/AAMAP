<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: /login.php");
    exit();
}

require 'C:/xampp/htdocs/db_connect.php';
require 'C:/xampp/htdocs/role.php';

// Procesar la actualización de la etapa si se presiona el botón
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['generar_cotizacion'])) {
    $proyecto_id = $_POST['proyecto_id'];

    // Actualizar la etapa a "en proceso"
    $sqlUpdate = "UPDATE proyectos SET etapa = 'en proceso' WHERE cod_fab = (SELECT id_proyecto FROM orden_fab WHERE id_fab = ?)";
    $stmtUpdate = $conn->prepare($sqlUpdate);
    $stmtUpdate->bind_param("s", $proyecto_id);

    if ($stmtUpdate->execute()) {
        $stmtUpdate->close();
        // Redirigir a ver_cot.php después de actualizar la etapa
        header("Location: ver_cot.php?id=" . urlencode($proyecto_id));
        exit();
    } else {
        echo "Error al actualizar el estado del proyecto.";
        exit();
    }
}

// Validar y sanitizar la entrada del ID del proyecto
$proyecto_id = filter_var($_GET['id'], FILTER_SANITIZE_STRING);
if ($proyecto_id === false || empty($proyecto_id)) {
    echo "ID de proyecto no válido.";
    exit();
}

// Consultar datos del proyecto
$sql = "SELECT 
            of.id_fab AS proyecto_id,
            p.cod_fab,
            p.nombre AS nombre_proyecto,
            c.nombre_comercial AS nombre_cliente,
            p.fecha_entrega,
            p.etapa
        FROM orden_fab of
        INNER JOIN proyectos p ON of.id_proyecto = p.cod_fab
        INNER JOIN clientes_p c ON of.id_cliente = c.id
        WHERE of.id_fab = ?";

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

// Consultar las partidas del proyecto con subtotales, IVA y totales
$sqlPartidas = "SELECT 
                    pa.id AS partida_id,
                    pa.descripcion AS nombre_partida, 
                    pa.proceso,
                    pa.cantidad,
                    pa.unidad_medida,
                    (SELECT re.estatus_log
                     FROM registro_estatus re
                     WHERE re.id_partida = pa.id
                     ORDER BY re.fecha_log DESC
                     LIMIT 1) AS estatus,
                    (SELECT MAX(re.fecha_log)
                     FROM registro_estatus re
                     WHERE re.id_partida = pa.id) AS ultimo_registro
                FROM partidas pa
                WHERE pa.cod_fab = (SELECT id_proyecto FROM orden_fab WHERE id_fab = ?)";

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
$partidas = $resultPartidas->fetch_all(MYSQLI_ASSOC);

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Proyecto OF-<?php echo htmlspecialchars($proyecto['proyecto_id']) ?></title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="stprojects.css">
    <link rel="icon" href="/assets/logo.ico">
</head>
<body>

<div class="container mt-4">
    <?php if ($proyecto['etapa'] != 'creado'): ?>
    <?php echo "<h1>Proyecto: " . htmlspecialchars($proyecto['nombre_proyecto']) . "</h1>";?>
    <table class="table table-bordered">
        <thead class="thead-dark">
            <tr>
                <th>OF</th>
                <th>Cliente</th>
                <th>F.E</th>
                <th>ID partida</th>
                <th>Descrip Partida</th>
                <th>Proceso</th>
                <th>Cantidad</th>
                <th>UM</th>
                <th>Estatus</th>
                <th>Última Fecha de Registro</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $pr = 1;
        $numRows = count($partidas);
        if (!empty($partidas)) {
            foreach ($partidas as $key => $row) {
                echo "<tr>";
                if ($key === 0) {
                    echo "<td rowspan='" . $numRows . "' class='text-center align-middle'>" . 'OF-' . htmlspecialchars($proyecto['proyecto_id']) . "</td>";
                    echo "<td rowspan='" . $numRows . "' class='text-center align-middle'>" . htmlspecialchars($proyecto['nombre_cliente']) . "</td>";
                    echo "<td rowspan='" . $numRows . "' class='text-center align-middle'>" . htmlspecialchars($proyecto['fecha_entrega']) . "</td>";
                }
                echo "<td>" . $pr . "</td>";
                echo "<td>" . htmlspecialchars($row['nombre_partida']) . "</td>";
                echo "<td>" . htmlspecialchars($row['proceso']) . "</td>";
                echo "<td>" . htmlspecialchars($row['cantidad']) . "</td>";
                echo "<td>" . htmlspecialchars($row['unidad_medida']) . "</td>";
                $pr = $pr + 1;
                if ($proyecto['etapa'] == 'en proceso'):
                    echo "<td data-id='" . htmlspecialchars($row['partida_id']) . "' class='editable'>" . htmlspecialchars($row['estatus']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['ultimo_registro']) . "</td>";
                    echo "</tr>";
                endif;

                if ($proyecto['etapa'] == 'finalizado' or $proyecto['etapa'] == 'facturacion'):
                    echo '
                        <td data-id="'. htmlspecialchars($row['partida_id']). '">'. htmlspecialchars($row['estatus']). '</td>
                        <td>'. htmlspecialchars($row['ultimo_registro']). '</td>
                    ';
                endif;
            }
        } else {
            echo "<tr>";
            echo "<td colspan='14' class='text-center'>No se encontraron partidas para este proyecto.</td>";
            echo "</tr>";
        }
        ?>
        </tbody>
    </table>
    <?php endif; ?>

    <div class="mt-4 d-flex justify-content-center gap-3">
        <a href="all_projects.php" class="btn btn-secondary mr-3">Regresar</a>
        <?php if ($proyecto['etapa'] != 'creado'): ?>
            <a href="ver_logs.php?id=<?php echo urlencode($proyecto['proyecto_id']); ?>" class="btn btn-info mr-3">Logs</a>
        <?php endif; ?>

        <?php if($username == 'h.galicia' || $username == 'admin'):
        if ($proyecto['etapa'] == 'facturacion'): ?> 
            <a href="finish_project.php?id=<?php echo urlencode($proyecto['proyecto_id']); ?>" class="btn btn-info mr-3">Finalizar Proyecto</a>
        <?php endif; 
            endif; ?>

        <?php if($username == 'CIS' || $username == 'admin'):
        if ($proyecto['etapa'] == 'en proceso'): ?>
            <a href="mandar_facturacion.php?id=<?php echo urlencode($proyecto['proyecto_id']); ?>" class="btn btn-info mr-3">Mandar a Facturar</a>
        <?php endif; 
        endif; ?>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script type="text/javascript">
    $(document).ready(function() {
        // Función para hacer la celda editable
        function makeEditable(td) {
            var originalValue = td.text().trim(); // Obtener el valor original y eliminar espacios en blanco
            var input = $("<input type='text'>").val(originalValue);
            td.html(input);
            input.focus();

            // Guardar el cambio al presionar Enter
            input.on('keydown', function(e) {
                if (e.which == 13) { // 13 es el código de la tecla Enter
                    var newValue = $(this).val().trim(); // Obtener el nuevo valor y eliminar espacios en blanco
                    if (newValue !== originalValue) { // Solo actualizar si hay un cambio
                        var partidaId = td.data('id');
                        updateEstatus(partidaId, newValue, td);
                    } else {
                        td.html(originalValue); // Restaurar el valor original si no hay cambios
                    }
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
                    id_fab: '<?php echo htmlspecialchars($proyecto['proyecto_id']); ?>', // Enviamos el id_proyecto
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