<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: /login.php");
    exit();
}

require 'C:/xampp/htdocs/db_connect.php';
require 'C:/xampp/htdocs/role.php';
require 'send_email.php';

date_default_timezone_set("America/Mexico_City");

// Obtener proyectos disponibles para ser padres (solo 'directo' y 'en proceso')
$sqlProyectosPadre = "SELECT of.id_fab, p.nombre 
                     FROM orden_fab of
                     JOIN proyectos p ON of.id_proyecto = p.cod_fab
                     WHERE p.etapa IN ('directo', 'en proceso') 
                     AND of.activo = 1 
                     AND (of.es_subproyecto = 0 OR of.es_subproyecto = NULL) 
                     ORDER BY of.id_fab DESC";
$resultProyectosPadre = $conn->query($sqlProyectosPadre);
$proyectosPadre = [];
if ($resultProyectosPadre->num_rows > 0) {
    while ($row = $resultProyectosPadre->fetch_assoc()) {
        $proyectosPadre[] = $row;
    }
}

// Generar el cod_fab automáticamente
$sqlUltimoCodFab = "SELECT cod_fab FROM proyectos WHERE cod_fab LIKE 'OF-%' ORDER BY LENGTH(cod_fab) DESC, cod_fab DESC LIMIT 1";
$resultUltimoCodFab = $conn->query($sqlUltimoCodFab);

if ($resultUltimoCodFab->num_rows > 0) {
    $row = $resultUltimoCodFab->fetch_assoc();
    preg_match('/OF-(\d+)/', $row['cod_fab'], $matches);
    $ultimoNumero = isset($matches[1]) ? intval($matches[1]) + 1 : 1000;
} else {
    $ultimoNumero = 1000;
}

$cod_fab = 'OF-' . $ultimoNumero;

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cod_fab = $_POST['cod_fab'];
    $nombre = $_POST['nombre'];
    $descripcion = $_POST['descripcion'];
    $fecha_entrega = $_POST['fecha_entrega'];
    $of_padre = $_POST['of_padre'];
    $partidas = json_decode($_POST['partidas'], true);

    // IDs del cliente y comprador "Maquila Interna"
    $id_cliente_maquila = 118;    // Reemplazar con el ID correcto
    $id_comprador_maquila = 25;  // Reemplazar con el ID correcto

    $conn->begin_transaction();
    try {
        // Proyecto
        $sqlProyecto = "INSERT INTO proyectos (cod_fab, nombre, id_cliente, id_comprador, descripcion, etapa, fecha_entrega)
                        VALUES (?, ?, ?, ?, ?, 'directo', ?)";
        $stmt = $conn->prepare($sqlProyecto);
        $stmt->bind_param("ssiiss", $cod_fab, $nombre, $id_cliente_maquila, $id_comprador_maquila, $descripcion, $fecha_entrega);
        $stmt->execute();

        // Partidas
        $sqlPartida = "INSERT INTO partidas (cod_fab, descripcion, proceso, cantidad, unidad_medida, precio_unitario)
                       VALUES (?, ?, ?, ?, ?, 0)"; // Precio fijo en 0
        $stmtPartida = $conn->prepare($sqlPartida);

        foreach ($partidas as $partida) {
            $stmtPartida->bind_param(
                "sssis",
                $cod_fab,
                $partida['descripcion'],
                $partida['proceso'],
                $partida['cantidad'],
                $partida['unidad_medida']
            );
            $stmtPartida->execute();
        }

        // Orden de fabricación (ahora como subproyecto)
        $sqlOrden = "INSERT INTO orden_fab (id_proyecto, plano_ref, of_created, id_cliente, es_subproyecto, of_padre)
                    VALUES (?, 'maquila interna', NOW(), ?, 1, ?)";
        $stmtOrden = $conn->prepare($sqlOrden);
        $stmtOrden->bind_param("sii", $cod_fab, $id_cliente_maquila, $of_padre);
        $stmtOrden->execute();

        $id_fab = $stmtOrden->insert_id;

        // Enviar correo
        $to = 'cop.aamap@aamap.net';
        $subject = "Nueva Orden de Maquila Interna: OF-$id_fab";

        $body = "<h3>Orden de Maquila Interna: OF-$id_fab</h3>";
        $body .= "<small>Código de Proyecto: $cod_fab</small>";
        $body .= "<p><strong>Nombre del Proyecto:</strong> $nombre</p>";
        $body .= "<p><strong>Fecha de Entrega:</strong> $fecha_entrega</p>";
        $body .= "<p><strong>Descripción:</strong> $descripcion</p>";
        $body .= "<p><strong>Proyecto Padre:</strong> OF-$of_padre</p>";

        $body .= "<h4>Partidas:</h4>";
        $body .= "<table border='1' cellpadding='5' cellspacing='0'>
            <tr>
                <th>#</th>
                <th>Descripción</th>
                <th>Proceso</th>
                <th>Cantidad</th>
                <th>Unidad</th>
            </tr>";

        foreach ($partidas as $index => $p) {
            $body .= "<tr>
                <td>" . ($index + 1) . "</td>
                <td>{$p['descripcion']}</td>
                <td>{$p['proceso']}</td>
                <td>{$p['cantidad']}</td>
                <td>{$p['unidad_medida']}</td>
            </tr>";
        }

        $body .= "</table>";

        send_email_order($to, $subject, $body);

        $conn->commit();
        echo "<script>alert('Orden de maquila interna registrada correctamente.'); window.location.href = 'direct_projects.php';</script>";
    } catch (Exception $e) {
        $conn->rollback();
        echo "<script>alert('Error: " . addslashes($e->getMessage()) . "');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nueva Orden de Maquila Interna</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="icon" href="/assets/logo.png" type="image/png">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body { background-color: rgba(211, 211, 211, 0.4) !important; }
        .form-section { margin-bottom: 2rem; }
    </style>
</head>
<body>

<div class="container">
    <h1 class="text-center">Nueva Orden de Maquila Interna</h1>
    <a href="direct_projects.php" class="btn btn-secondary">Regresar</a>
    <p>&nbsp;</p>
    <form id="projectForm" method="POST">
        <input type="hidden" name="cod_fab" value="<?php echo $cod_fab; ?>">

        <div class="form-section">
            <h4>Información General</h4>

            <div class="form-group">
                <label for="of_padre">OF Asignada</label>
                <select class="form-control" id="of_padre" name="of_padre" required>
                    <option value="">Seleccione un proyecto padre</option>
                    <?php foreach ($proyectosPadre as $proyecto): ?>
                        <option value="<?php echo $proyecto['id_fab']; ?>">
                            OF-<?php echo $proyecto['id_fab']; ?> - <?php echo htmlspecialchars($proyecto['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="nombre">Nombre del Proyecto</label>
                <input type="text" class="form-control" id="nombre" name="nombre" required>
            </div>

            <div class="form-group">
                <label for="descripcion">Descripción</label>
                <textarea class="form-control" id="descripcion" name="descripcion" rows="3"></textarea>
            </div>

            <div class="form-group">
                <label for="fecha_entrega">Fecha Requerida</label>
                <input type="date" class="form-control" id="fecha_entrega" name="fecha_entrega" required>
            </div>
        </div>

        <hr>
        <div class="form-section">
            <h4>Partidas</h4>

            <div class="form-row" id="personalizado_partida">
                <div class="col-md-5">
                    <label for="descripcion_personalizada">Descripción</label>
                    <input type="text" class="form-control" id="descripcion_personalizada">
                </div>
                <div class="col-md-2">
                    <label for="proceso_personalizado">Proceso</label>
                    <select class="form-control" id="proceso_personalizado">
                        <option value="MAN">MAN</option>
                        <option value="MAQ">MAQ</option>
                        <option value="COM">COM</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="cantidad_personalizada">Cantidad</label>
                    <input type="number" class="form-control" id="cantidad_personalizada" min="1" value="1">
                </div>
                <div class="col-md-3">
                    <label for="um_personalizada">Unidad de Medida</label>
                    <input type="text" class="form-control" id="um_personalizada" placeholder="PZA, KG, M, etc.">
                </div>
            </div>

            <div class="form-group mt-3">
                <button type="button" class="btn btn-primary" id="addPartida">Agregar Partida</button>
            </div>

            <table class="table table-bordered mt-3">
                <thead>
                    <tr>
                        <th>Descripción</th>
                        <th>Proceso</th>
                        <th>Cantidad</th>
                        <th>Unidad</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody id="partidasTable"></tbody>
            </table>
        </div>

        <input type="hidden" name="partidas" id="partidas">
        <button type="submit" class="btn btn-success btn-block">Crear Orden de Maquila Interna</button>
    </form>
</div>

<script>
    const partidas = [];

    function agregarPartida() {
        const descripcion = $('#descripcion_personalizada').val();
        const proceso = $('#proceso_personalizado').val();
        const cantidad = $('#cantidad_personalizada').val();
        const unidad = $('#um_personalizada').val();

        if (!descripcion || !proceso || !cantidad || !unidad) {
            alert("Todos los campos de la partida son obligatorios.");
            return;
        }

        const partida = {
            descripcion: descripcion,
            proceso: proceso,
            cantidad: cantidad,
            unidad_medida: unidad
        };

        partidas.push(partida);

        $('#partidasTable').append(`
            <tr>
                <td>${descripcion}</td>
                <td>${proceso}</td>
                <td>${cantidad}</td>
                <td>${unidad}</td>
                <td><button type="button" class="btn btn-danger btn-sm removePartida">Eliminar</button></td>
            </tr>
        `);

        $('#descripcion_personalizada').val('');
        $('#proceso_personalizado').val('MAN');
        $('#cantidad_personalizada').val('1');
        $('#um_personalizada').val('');
    }

    $('#addPartida').click(agregarPartida);

    $(document).on('click', '.removePartida', function () {
        const index = $(this).closest('tr').index();
        partidas.splice(index, 1);
        $(this).closest('tr').remove();
    });

    $('#projectForm').submit(function () {
        $('#partidas').val(JSON.stringify(partidas));
        if (partidas.length === 0) {
            alert("Debes agregar al menos una partida.");
            return false;
        }
        return true;
    });
</script>

</body>
</html>