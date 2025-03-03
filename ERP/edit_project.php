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

// Consultar datos del proyecto, partidas y artículos
$sql = "SELECT 
        p.cod_fab, 
        p.nombre AS nombre_proyecto,
        p.id_cliente,
        c.nombre AS nombre_cliente, 
        p.descripcion,
        p.fecha_entrega,
        p.unidad_medida,
        p.etapa
    FROM 
        proyectos p
    LEFT JOIN 
        clientes_p c ON p.id_cliente = c.id
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

// Obtener partidas
$sqlPartidas = "SELECT * FROM partidas WHERE cod_fab = ?";
$stmtPartidas = $conn->prepare($sqlPartidas);
$stmtPartidas->bind_param("s", $proyecto_id);
$stmtPartidas->execute();
$resultPartidas = $stmtPartidas->get_result();
$partidas = $resultPartidas->fetch_all(MYSQLI_ASSOC);

// Obtener artículos
$sqlArticulos = "SELECT * FROM pedidos_p_detalle WHERE id_proyecto = ?";
$stmtArticulos = $conn->prepare($sqlArticulos);
$stmtArticulos->bind_param("s", $proyecto_id);
$stmtArticulos->execute();
$resultArticulos = $stmtArticulos->get_result();
$articulos = $resultArticulos->fetch_all(MYSQLI_ASSOC);

// Procesar el formulario si se envió
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener datos del formulario
    $cod_fab = $_POST['cod_fab'];
    $nombre = $_POST['nombre'];
    $id_cliente = $_POST['id_cliente'];
    $descripcion = $_POST['descripcion'];
    $fecha_entrega = $_POST['fecha_entrega'];
    $unidad_medida = $_POST['unidad_medida'];
    $etapa = $_POST['etapa'];
    $partida_nombres = $_POST['partida_nombre'];
    $partida_macs = isset($_POST['partida_mac']) ? $_POST['partida_mac'] : [];
    $partida_mans = isset($_POST['partida_man']) ? $_POST['partida_man'] : [];
    $partida_coms = isset($_POST['partida_com']) ? $_POST['partida_com'] : [];
    $articulo_nombres = $_POST['articulo_nombre'];
    $articulo_cantidades = $_POST['articulo_cantidad'];
    $articulo_precios = $_POST['articulo_precio'];
    $articulo_ums = $_POST['articulo_um'];

    try {
        $conn->begin_transaction();

        // Actualizar proyecto
        $sqlProyecto = "UPDATE proyectos SET 
            nombre = ?, 
            id_cliente = ?, 
            descripcion = ?, 
            fecha_entrega = ?, 
            unidad_medida = ?,
            etapa = ?
            WHERE cod_fab = ?";
        $stmtProyecto = $conn->prepare($sqlProyecto);
        $stmtProyecto->bind_param("sisssss", $nombre, $id_cliente, $descripcion, $fecha_entrega, $unidad_medida, $etapa, $cod_fab);
        $stmtProyecto->execute();

        // Actualizar partidas
        $sqlPartidaUpdate = "UPDATE partidas SET nombre = ?, mac = ?, man = ?, com = ? WHERE id = ?";
        $stmtPartidaUpdate = $conn->prepare($sqlPartidaUpdate);
        for ($i = 0; $i < count($partida_nombres); $i++) {
            $partida_id = $partidas[$i]['id']; 
            $partida_nombre = $partida_nombres[$i];
            $partida_mac = in_array($i, $partida_macs) ? 1 : 0;
            $partida_man = in_array($i, $partida_mans) ? 1 : 0;
            $partida_com = in_array($i, $partida_coms) ? 1 : 0;
            $stmtPartidaUpdate->bind_param("siiii", $partida_nombre, $partida_mac, $partida_man, $partida_com, $partida_id);
            $stmtPartidaUpdate->execute();
        }

        // Actualizar artículos
        $sqlArticuloUpdate = "UPDATE pedidos_p_detalle SET articulos = ?, cantidad = ?, precio = ?, um = ? WHERE id = ?";
        $stmtArticuloUpdate = $conn->prepare($sqlArticuloUpdate);
        for ($i = 0; $i < count($articulo_nombres); $i++) {
            $articulo_id = $articulos[$i]['id']; 
            $articulo_nombre = $articulo_nombres[$i];
            $articulo_cantidad = $articulo_cantidades[$i];
            $articulo_precio = $articulo_precios[$i];
            $articulo_um = $articulo_ums[$i];
            $stmtArticuloUpdate->bind_param("sidsi", $articulo_nombre, $articulo_cantidad, $articulo_precio, $articulo_um, $articulo_id);
            $stmtArticuloUpdate->execute();
        }

        $conn->commit();
        echo "<script>alert('Proyecto actualizado exitosamente.'); window.location.href = 'ver_proyecto.php?id=" . urlencode($cod_fab) . "';</script>";

    } catch (Exception $e) {
        $conn->rollback();
        echo "<script>alert('Error al actualizar el proyecto: " . $e->getMessage() . "');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Proyecto</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> </head>
<body>

<div class="container mt-4">
    <h1>Editar Proyecto</h1>
    <form id="projectForm" method="POST" action="edit_project.php">
        <input type="hidden" name="cod_fab" value="<?php echo htmlspecialchars($proyecto['cod_fab']); ?>">

        <div class="form-group">
            <label for="nombre">Nombre del Proyecto</label>
            <input type="text" class="form-control" id="nombre" name="nombre" value="<?php echo htmlspecialchars($proyecto['nombre_proyecto']); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="id_cliente">Cliente</label>
            <select class="form-control" id="id_cliente" name="id_cliente" required>
                <option value="">Seleccionar cliente</option>
                <?php 
                // Obtener clientes para el select
                $sqlClientes = "SELECT id, nombre FROM clientes_p";
                $resultClientes = $conn->query($sqlClientes);
                while ($row = $resultClientes->fetch_assoc()) {
                    $selected = ($row['id'] == $proyecto['id_cliente']) ? 'selected' : '';
                    echo "<option value='" . $row['id'] . "' $selected>" . htmlspecialchars($row['nombre']) . "</option>";
                }
                ?>
            </select>
        </div>

        <div class="form-group">
            <label for="descripcion">Descripción</label>
            <textarea class="form-control" id="descripcion" name="descripcion" rows="3"><?php echo htmlspecialchars($proyecto['descripcion']); ?></textarea>
        </div>
        <div class="form-group">
            <label for="fecha_entrega">Fecha de Entrega</label>
            <input type="date" class="form-control" id="fecha_entrega" name="fecha_entrega" value="<?php echo htmlspecialchars($proyecto['fecha_entrega']); ?>" required>
        </div>
        <div class="form-group">
            <label for="unidad_medida">Unidad de Medida</label>
            <input type="text" class="form-control" id="unidad_medida" name="unidad_medida" value="<?php echo htmlspecialchars($proyecto['unidad_medida']); ?>" required>
        </div>
        <div class="form-group">
            <label for="etapa">Etapa</label>
            <select class="form-control" id="etapa" name="etapa" required>
                <option value="en proceso" <?php if ($proyecto['etapa'] == 'en proceso') echo 'selected'; ?>>En proceso</option>
                <option value="finalizado" <?php if ($proyecto['etapa'] == 'finalizado') echo 'selected'; ?>>Finalizado</option>
            </select>
        </div>

        <h3>Partidas</h3>
        <div class="form-row">
            <div class="col">
                <input type="text" class="form-control" id="nombre_partida" placeholder="Nombre de la Partida">
            </div>
            <div class="col">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="mac">
                    <label class="form-check-label" for="mac">Mac</label>
                </div>
            </div>
            <div class="col">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="man">
                    <label class="form-check-label" for="man">Man</label>
                </div>
            </div>
            <div class="col">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="com">
                    <label class="form-check-label" for="com">Com</label>
                </div>
            </div>
            <div class="col">
                <button type="button" class="btn btn-primary" id="addPartida">Agregar Partida</button>
            </div>
        </div>

        <table class="table mt-3">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Mac</th>
                    <th>Man</th>
                    <th>Com</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody id="partidasTable">
                <?php foreach ($partidas as $partida): ?>
                    <tr>
                        <td><input type="text" class="form-control" name="partida_nombre[]" value="<?php echo htmlspecialchars($partida['nombre']); ?>"></td>
                        <td><input type="checkbox" name="partida_mac[]" value="1" <?php if ($partida['mac']) echo 'checked'; ?>></td>
                        <td><input type="checkbox" name="partida_man[]" value="1" <?php if ($partida['man']) echo 'checked'; ?>></td>
                        <td><input type="checkbox" name="partida_com[]" value="1" <?php if ($partida['com']) echo 'checked'; ?>></td>
                        <td><button type="button" class="btn btn-danger btn-sm removePartida">Eliminar</button></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
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
            <tbody id="articulosTable">
                <?php foreach ($articulos as $articulo): ?>
                    <tr>
                        <td><input type="text" class="form-control" name="articulo_nombre[]" value="<?php echo htmlspecialchars($articulo['articulos']); ?>"></td>
                        <td><input type="number" class="form-control" name="articulo_cantidad[]" value="<?php echo htmlspecialchars($articulo['cantidad']); ?>"></td>
                        <td><input type="number" class="form-control" name="articulo_precio[]" value="<?php echo htmlspecialchars($articulo['precio']); ?>"></td>
                        <td><input type="text" class="form-control" name="articulo_um[]" value="<?php echo htmlspecialchars($articulo['um']); ?>"></td>
                        <td><button type="button" class="btn btn-danger btn-sm removeArticulo">Eliminar</button></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php
        // Procesar el formulario si se envió
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            echo'<button type="submit" class="btn btn-success btn-block">Guardar Cambios</button>';
        }
        ?>
        
    </form>
</div>

<script>
$(document).ready(function() {
    // Agregar Partida
    $('#addPartida').click(function() {
        const nombre = $('#nombre_partida').val();
        const mac = $('#mac').is(':checked') ? 1 : 0;
        const man = $('#man').is(':checked') ? 1 : 0;
        const com = $('#com').is(':checked') ? 1 : 0;

        if (nombre) {
            $('#partidasTable').append(`
                <tr>
                    <td><input type="text" class="form-control" name="partida_nombre[]" value="${nombre}"></td>
                    <td><input type="checkbox" name="partida_mac[]" value="1" ${mac ? 'checked' : ''}></td>
                    <td><input type="checkbox" name="partida_man[]" value="1" ${man ? 'checked' : ''}></td>
                    <td><input type="checkbox" name="partida_com[]" value="1" ${com ? 'checked' : ''}></td>
                    <td><button type="button" class="btn btn-danger btn-sm removePartida">Eliminar</button></td>
                </tr>
            `);
            $('#nombre_partida').val('');
            $('#mac').prop('checked', false);
            $('#man').prop('checked', false);
            $('#com').prop('checked', false);
        }
    });

    // Eliminar Partida
    $(document).on('click', '.removePartida', function() {
        $(this).closest('tr').remove();
    });

    // Agregar Artículo
    $('#addArticulo').click(function() {
        const nombreArticulo = $('#nombre_articulo').val();
        const cantidad = $('#cantidad_articulo').val();
        const precio = $('#precio_articulo').val();
        const um = $('#um_articulo').val();

        if (nombreArticulo && cantidad && precio && um) {
            $('#articulosTable').append(`
                <tr>
                    <td><input type="text" class="form-control" name="articulo_nombre[]" value="${nombreArticulo}"></td>
                    <td><input type="number" class="form-control" name="articulo_cantidad[]" value="${cantidad}"></td>
                    <td><input type="number" class="form-control" name="articulo_precio[]" value="${precio}"></td>
                    <td><input type="text" class="form-control" name="articulo_um[]" value="${um}"></td>
                    <td><button type="button" class="btn btn-danger btn-sm removeArticulo">Eliminar</button></td>
                </tr>
            `);
            $('#nombre_articulo').val('');
            $('#cantidad_articulo').val('');
            $('#precio_articulo').val('');
            $('#um_articulo').val('');
        }
    });

    // Eliminar Artículo
    $(document).on('click', '.removeArticulo', function() {
        $(this).closest('tr').remove();
    });
});
</script>

</body>
</html>