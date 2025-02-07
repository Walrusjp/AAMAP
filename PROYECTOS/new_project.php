<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

include 'C:/xampp/htdocs/PAPELERIA/db_connect.php';

// Obtener clientes para la lista desplegable
$sqlClientes = "SELECT id, nombre FROM clientes_p";
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
    $unidad_medida = $_POST['unidad_medida'];
    $partidas = json_decode($_POST['partidas'], true); // Partidas enviadas en formato JSON
    $articulos = json_decode($_POST['articulos'], true); // Artículos enviados en formato JSON

    // Iniciar transacción
    $conn->begin_transaction();
    try {
        // Insertar proyecto
        $sqlProyecto = "INSERT INTO proyectos (cod_fab, nombre, id_cliente, descripcion, etapa, fecha_entrega, unidad_medida)
                        VALUES (?, ?, ?, ?, 'en proceso', ?, ?)";
        $stmtProyecto = $conn->prepare($sqlProyecto);
        $stmtProyecto->bind_param('ssisss', $cod_fab, $nombre, $id_cliente, $descripcion, $fecha_entrega, $unidad_medida);
        $stmtProyecto->execute();
        $id_proyecto = $stmtProyecto->insert_id;

        // Insertar partidas
        $sqlPartida = "INSERT INTO partidas (cod_fab, nombre, proceso) VALUES (?, ?, ?)";
        $stmtPartida = $conn->prepare($sqlPartida);
        foreach ($partidas as $partida) {
            $stmtPartida->bind_param(
                'sss',
                $cod_fab,
                $partida['nombre'],
                $partida['proceso']
            );
            $stmtPartida->execute();
        }

        // Insertar artículos
        $sqlArticulo = "INSERT INTO pedidos_p_detalle (id_proyecto, articulos, cantidad, precio, um) VALUES (?, ?, ?, ?, ?)";
        $stmtArticulo = $conn->prepare($sqlArticulo);
        foreach ($articulos as $articulo) {
            $stmtArticulo->bind_param(
                'ssids',
                $cod_fab, // Asignar el ID del proyecto recién creado
                $articulo['articulos'],
                $articulo['cantidad'],
                $articulo['precio'],
                $articulo['um']
            );
            $stmtArticulo->execute();
        }

        // Confirmar transacción
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
    <title>Nuevo Proyecto</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>

<div class="container">
    <h1 class="text-center">Nuevo Proyecto</h1>
    <form id="projectForm" method="POST" action="new_project.php">
        <div class="form-group">
            <label for="cod_fab">Código de Fabricación</label>
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
                    <option value="<?php echo $cliente['id']; ?>"><?php echo htmlspecialchars($cliente['nombre']); ?></option>
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
        <div class="form-group">
            <label for="unidad_medida">Unidad de Medida</label>
            <input type="text" class="form-control" id="unidad_medida" name="unidad_medida" required>
        </div>

        <h3>Partidas</h3>
        <div class="form-row">
            <div class="col">
                <input type="text" class="form-control" id="nombre_partida" placeholder="Nombre de la Partida">
            </div>
            <div class="col">
                <select class="form-control" id="proceso">
                    <option value="man">MAN</option>
                    <option value="maq">MAQ</option>
                    <option value="com">COM</option>
                </select>
            </div>
            <div class="col">
                <button type="button" class="btn btn-primary" id="addPartida">Agregar Partida</button>
            </div>
        </div>

        <table class="table mt-3">
            <thead>
            <tr>
                <th>Nombre</th>
                <th>Proceso</th>
                <th>Acción</th>
            </tr>
            </thead>
            <tbody id="partidasTable"></tbody>
        </table>

        <h3>Artículos</h3>
        <div class="form-row">
            <div class="col">
                <input type="text" class="form-control" id="nombre_articulo" placeholder="Nombre del Artículo">
            </div>
            <div class="col">
                <input type="number" class="form-control" id="cantidad_articulo" placeholder="Cantidad">
            </div>
            <div class="col">
                <input type="number" class="form-control" id="precio_articulo" placeholder="Precio">
            </div>
            <div class="col">
                <input type="text" class="form-control" id="um_articulo" placeholder="Unidad de Medida">
            </div>
            <div class="col">
                <button type="button" class="btn btn-primary" id="addArticulo">Agregar Artículo</button>
            </div>
        </div>

        <table class="table mt-3">
            <thead>
            <tr>
                <th>Artículo</th>
                <th>Cantidad</th>
                <th>Precio</th>
                <th>Unidad de Medida</th>
                <th>Acción</th>
            </tr>
            </thead>
            <tbody id="articulosTable"></tbody>
        </table>

        <input type="hidden" name="partidas" id="partidas">
        <input type="hidden" name="articulos" id="articulos">
        <button type="submit" class="btn btn-success btn-block">Crear Proyecto</button>
        <a href="all_projects.php" class="btn btn-secondary btn-block">Regresar</a>
    </form>
</div>

<script>
    const partidas = [];
    const articulos = [];

    $('#addPartida').click(function () {
        const nombre = $('#nombre_partida').val();
        const proceso = $('#proceso').val();

        if (nombre) {
            partidas.push({ nombre, proceso });
            $('#partidasTable').append(`
                <tr>
                    <td>${nombre}</td>
                    <td>${proceso}</td>
                    <td><button class="btn btn-danger btn-sm removePartida">Eliminar</button></td>
                </tr>
            `);
            $('#nombre_partida').val('');
        }
    });

    $(document).on('click', '.removePartida', function () {
        const index = $(this).closest('tr').index();
        partidas.splice(index, 1);
        $(this).closest('tr').remove();
    });

   $('#addArticulo').click(function () {
    const nombreArticulo = $('#nombre_articulo').val(); // Cambiar nombre de variable
    const cantidad = $('#cantidad_articulo').val();
    const precio = $('#precio_articulo').val();
    const um = $('#um_articulo').val();

    if (nombreArticulo && cantidad && precio && um) {
        articulos.push({ articulos: nombreArticulo, cantidad, precio, um }); // Usar nombreArticulo aquí
        $('#articulosTable').append(`
            <tr>
                <td>${nombreArticulo}</td>
                <td>${cantidad}</td>
                <td>${precio}</td>
                <td>${um}</td>
                <td><button class="btn btn-danger btn-sm removeArticulo">Eliminar</button></td>
            </tr>
        `);
        $('#nombre_articulo').val('');
        $('#cantidad_articulo').val('');
        $('#precio_articulo').val('');
        $('#um_articulo').val('');
    }
});

    $(document).on('click', '.removeArticulo', function () {
        const index = $(this).closest('tr').index();
        articulos.splice(index, 1);
        $(this).closest('tr').remove();
    });

    $('#projectForm').submit(function () {
        $('#partidas').val(JSON.stringify(partidas));
        $('#articulos').val(JSON.stringify(articulos));
    });
</script>

</body>
</html>