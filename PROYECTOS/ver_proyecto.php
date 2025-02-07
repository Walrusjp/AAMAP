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

// Consultar datos del proyecto junto con las partidas
$sql = "SELECT 
        p.cod_fab, 
        c.nombre AS nombre_cliente, 
        pa.id AS partida, 
        pa.nombre AS nombre_partida, 
        pa.proceso,
        p.fecha_entrega,
        p.etapa,  -- Agregar la columna 'etapa' aquí
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
$stmt->bind_param("s", $proyecto_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "Proyecto no encontrado.";
    exit();
}

$proyecto = $result->fetch_assoc();

// Consultar datos del pedido
$sqlPedido = "SELECT articulos, cantidad, precio, um FROM pedidos_p_detalle WHERE id_proyecto = ?";
$stmtPedido = $conn->prepare($sqlPedido);
$stmtPedido->bind_param("s", $proyecto_id);
$stmtPedido->execute();
$resultPedido = $stmtPedido->get_result();

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
                <th>Descrip Partida</th>
                <th>Proceso</th>
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
    $numRows = $result->num_rows; // Almacenar el número de filas

    if ($numRows > 0) { // Si hay partidas
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";

            if ($isFirstRow) {
                echo "<td rowspan='" . $numRows . "' class='text-center align-middle'>" . htmlspecialchars($row['cod_fab']) . "</td>";
                echo "<td rowspan='" . $numRows . "' class='text-center align-middle'>" . htmlspecialchars($row['nombre_cliente']) . "</td>";
                echo "<td rowspan='" . $numRows . "' class='text-center align-middle'>" . htmlspecialchars($row['fecha_entrega']) . "</td>";
                $isFirstRow = false; // Asegurarse de que solo se ejecute una vez
            }

            // Mostrar el ID y nombre de la partida
            echo "<td>" . htmlspecialchars($row['partida']) . "</td>";
            echo "<td>" . htmlspecialchars($row['nombre_partida']) . "</td>";

            $proceso = $row['proceso'];

            echo "<td>" . $proceso . "</td>";
            echo "<td data-id='" . htmlspecialchars($row['partida']) . "' class='editable'>" . htmlspecialchars($row['estatus']) . "</td>";
            echo "<td>" . htmlspecialchars($row['ultimo_registro']) . "</td>";

            echo "</tr>";
        }
    } else { // Si no hay partidas
        echo "<tr>";
        echo "<td class='text-center align-middle'>" . htmlspecialchars($proyecto['cod_fab']) . "</td>";
        echo "<td class='text-center align-middle'>" . htmlspecialchars($proyecto['nombre_cliente']) . "</td>";
        echo "<td class='text-center align-middle'>" . htmlspecialchars($proyecto['fecha_entrega']) . "</td>";
        echo "</tr>";
    }
    ?>
</tbody>
    </table>

    <h2>Pedido</h2>
    <table class="table table-bordered">
        <thead class="thead-dark">
            <tr>
                <th>Artículo</th>
                <th>Cantidad</th>
                <th>Precio</th>
                <th>Total</th>
                <th>Unidad de Medida</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $totalPedido = 0;
        while ($rowPedido = $resultPedido->fetch_assoc()) {
            $total = $rowPedido['cantidad'] * $rowPedido['precio'];
            $totalPedido += $total;
            echo "<tr>";
            echo "<td>" . htmlspecialchars($rowPedido['articulos']) . "</td>";
            echo "<td>" . htmlspecialchars($rowPedido['cantidad']) . "</td>";
            echo "<td>" . htmlspecialchars($rowPedido['precio']) . "</td>";
            echo "<td>" . htmlspecialchars($total) . "</td>";
            echo "<td>" . htmlspecialchars($rowPedido['um']) . "</td>";
            echo "</tr>";
        }
        ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" class="text-right"><strong>Total del Pedido:</strong></td>
                <td><strong><?php echo $totalPedido; ?></strong></td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <div class="mt-4">
        <a href="all_projects.php" class="btn btn-secondary">Regresar</a>
        <!--<a href="edit_project.php?id=<?php echo urlencode($proyecto['cod_fab']); ?>" class="btn btn-primary">Editar</a>-->
        <a href="ver_logs.php?id=<?php echo urlencode($proyecto['cod_fab']); ?>" class="btn btn-info">Logs</a>
        <?php if ($proyecto['etapa'] !== 'finalizado'): ?> 
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

    // Función para actualizar el estatus en la base de datos
    function updateEstatus(partidaId, nuevoEstatus, td) {
        $.ajax({
            url: 'actualizar_estatus.php', // Crea este archivo PHP
            type: 'POST',
            data: { id: partidaId, estatus: nuevoEstatus },
            success: function(response) {
                td.html(nuevoEstatus);
                // Actualizar la última fecha de actualización en la fila correspondiente
                td.closest('tr').find('td:last').text(response); // Ajusta el índice si la columna de fecha no es la última
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