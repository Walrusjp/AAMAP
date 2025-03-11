<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: /login.php");
    exit();
}

require 'C:/xampp/htdocs/db_connect.php';

// Obtener clientes para la lista desplegable
$sqlClientes = "SELECT id, nombre_comercial FROM clientes_p";
$resultClientes = $conn->query($sqlClientes);
$clientes = [];
if ($resultClientes->num_rows > 0) {
    while ($row = $resultClientes->fetch_assoc()) {
        $clientes[] = $row;
    }
}

// Verificar si se envió el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cod_fab = $_POST['cod_fab'];
    $nombre = $_POST['nombre'];
    $id_cliente = $_POST['id_cliente'];
    $descripcion = $_POST['descripcion'];
    $fecha_entrega = $_POST['fecha_entrega'];
    $partidas = json_decode($_POST['partidas'], true);

    $conn->begin_transaction();
    try {
        // Insertar proyecto
        $sqlProyecto = "INSERT INTO proyectos (cod_fab, nombre, id_cliente, descripcion, etapa, fecha_entrega)
                        VALUES (?, ?, ?, ?, 'creado', ?)";
        $stmtProyecto = $conn->prepare($sqlProyecto);
        $stmtProyecto->bind_param('ssiss', $cod_fab, $nombre, $id_cliente, $descripcion, $fecha_entrega);
        $stmtProyecto->execute();
        $id_proyecto = $stmtProyecto->insert_id;

        // Insertar partidas
        $sqlPartida = "INSERT INTO partidas (cod_fab, descripcion, proceso, cantidad, unidad_medida, precio_unitario) 
                       VALUES (?, ?, ?, ?, ?, ?)";
        $stmtPartida = $conn->prepare($sqlPartida);
        foreach ($partidas as $partida) {
            $stmtPartida->bind_param(
                'sssiss', 
                $cod_fab, 
                $partida['descripcion'], 
                $partida['proceso'], 
                $partida['cantidad'], 
                $partida['unidad_medida'], 
                $partida['precio_unitario']
            );
            $stmtPartida->execute();
        }

        $conn->commit();
        echo "<script>alert('Proyecto registrado exitosamente.'); window.location.href = 'all_projects.php';</script>";
    } catch (Exception $e) {
        $conn->rollback();
        echo "<script>alert('Error al registrar el proyecto: " . $e->getMessage() . "');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nueva Cotización</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>

<div class="container">
    <h1 class="text-center">Nueva Cotización</h1>
    <form id="projectForm" method="POST" action="new_project.php">
        <div class="form-group">
            <label for="cod_fab">Número de Cotización</label>
            <input type="text" class="form-control" id="cod_fab" name="cod_fab" required>
        </div>
        <div class="form-group">
            <label for="nombre">Nombre del Proyecto</label>
            <input type="text" class="form-control" id="nombre" name="nombre" required>
        </div>
        <div class="form-group">
            <label for="id_cliente">Cliente</label>
            <select class="form-control" id="id_cliente" name="id_cliente" required>
                <option value="">Seleccionar cliente</option>
                <?php foreach ($clientes as $cliente): ?>
                    <option value="<?php echo $cliente['id']; ?>"><?php echo htmlspecialchars($cliente['nombre_comercial']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="descripcion">Descripción</label>
            <textarea class="form-control" id="descripcion" name="descripcion" rows="3"></textarea>
        </div>
        <div class="form-group">
            <label for="fecha_entrega">Fecha de Entrega</label>
            <input type="date" class="form-control" id="fecha_entrega" name="fecha_entrega" required>
        </div>

        <h3>Partidas</h3>
        <div class="form-row">
            <div class="col">
                <input type="text" class="form-control" id="descripcion_partida" placeholder="Descripción de la Partida">
            </div>
            <div class="col">
                <select class="form-control" id="proceso">
                    <option value="man">MAN</option>
                    <option value="maq">MAQ</option>
                    <option value="com">COM</option>
                </select>
            </div>
            <div class="col">
                <input type="number" class="form-control" id="cantidad_partida" placeholder="Cantidad">
            </div>
            <div class="col">
                <input type="text" class="form-control" id="um_partida" placeholder="Unidad de Medida">
            </div>
            <div class="col">
                <input type="number" class="form-control" id="precio_unitario_partida" placeholder="Precio Unitario">
            </div>
            <div class="col">
                <button type="button" class="btn btn-primary" id="addPartida">Agregar Partida</button>
            </div>
        </div>

        <table class="table mt-3">
            <thead>
            <tr>
                <th>Descripción</th>
                <th>Proceso</th>
                <th>Cantidad</th>
                <th>Unidad de Medida</th>
                <th>Precio Unitario</th>
                <th>Acción</th>
            </tr>
            </thead>
            <tbody id="partidasTable"></tbody>
        </table>

        <input type="hidden" name="partidas" id="partidas">
        <button type="submit" class="btn btn-success btn-block">Crear Cotización</button>
        <a href="all_projects.php" class="btn btn-secondary btn-block">Regresar</a>
    </form>
</div>

<script>
    const partidas = [];

    $('#addPartida').click(function () {
        const descripcion = $('#descripcion_partida').val();
        const proceso = $('#proceso').val();
        const cantidad = $('#cantidad_partida').val();
        const unidad_medida = $('#um_partida').val();
        const precio_unitario = $('#precio_unitario_partida').val();

        if (descripcion && cantidad && unidad_medida && precio_unitario) {
            partidas.push({ descripcion, proceso, cantidad, unidad_medida, precio_unitario });
            $('#partidasTable').append(`
                <tr>
                    <td>${descripcion}</td>
                    <td>${proceso}</td>
                    <td>${cantidad}</td>
                    <td>${unidad_medida}</td>
                    <td>${precio_unitario}</td>
                    <td><button class="btn btn-danger btn-sm removePartida">Eliminar</button></td>
                </tr>
            `);
            // Limpiar campos
            $('#descripcion_partida').val('');
            $('#cantidad_partida').val('');
            $('#um_partida').val('');
            $('#precio_unitario_partida').val('');
        }
    });

    $(document).on('click', '.removePartida', function () {
        const index = $(this).closest('tr').index();
        partidas.splice(index, 1);
        $(this).closest('tr').remove();
    });

    $('#projectForm').submit(function () {
        $('#partidas').val(JSON.stringify(partidas));
    });
</script>

</body>
</html>