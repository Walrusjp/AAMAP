<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: /login.php");
    exit();
}

require 'C:/xampp/htdocs/db_connect.php';
require 'C:/xampp/htdocs/role.php';
require 'get_compradores.php';
require 'send_email.php';

// Obtener clientes para la lista desplegable
$sqlClientes = "SELECT id, nombre_comercial FROM clientes_p";
$resultClientes = $conn->query($sqlClientes);
$clientes = [];
if ($resultClientes->num_rows > 0) {
    while ($row = $resultClientes->fetch_assoc()) {
        $clientes[] = $row;
    }
}

// Generar el cod_fab comenzando desde el último valor registrado
$sqlUltimoCodFab = "SELECT cod_fab FROM proyectos WHERE cod_fab LIKE 'OF-%' ORDER BY LENGTH(cod_fab) DESC, cod_fab DESC LIMIT 1";
$resultUltimoCodFab = $conn->query($sqlUltimoCodFab);

if ($resultUltimoCodFab->num_rows > 0) {
    $row = $resultUltimoCodFab->fetch_assoc();
    $ultimoCodFab = $row['cod_fab'];
    // Extraer la parte numérica del último cod_fab
    preg_match('/OF-(\d+)/', $ultimoCodFab, $matches);
    if (isset($matches[1])) {
        $ultimoNumero = intval($matches[1]) + 1; // Incrementar en 1
    } else {
        $ultimoNumero = 1000; // Si no hay coincidencia, empezar desde 1000
    }
} else {
    $ultimoNumero = 1000; // Si no hay registros, empezar desde 1000
}

$cod_fab = 'OF-' . $ultimoNumero; // Formato OF-1000, OF-1001, etc.

// Verificar si se envió el formulario
// Verificar si se envió el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("Formulario enviado");
    var_dump($_POST);

    $cod_fab = $_POST['cod_fab'];
    $nombre = $_POST['nombre'];
    $id_cliente = $_POST['id_cliente'];
    $descripcion = $_POST['descripcion'];
    $fecha_entrega = $_POST['fecha_entrega'];
    $partidas = json_decode($_POST['partidas'], true);
    $id_comprador = $_POST['id_comprador'];

    $conn->begin_transaction();
    try {
        // 1. Insertar proyecto
        $sqlProyecto = "INSERT INTO proyectos (cod_fab, nombre, id_cliente, id_comprador, descripcion, etapa, fecha_entrega)
                VALUES (?, ?, ?, ?, ?, 'directo', ?)";
        $stmtProyecto = $conn->prepare($sqlProyecto);
        $stmtProyecto->bind_param('ssiiss', $cod_fab, $nombre, $id_cliente, $id_comprador, $descripcion, $fecha_entrega);
        $stmtProyecto->execute();

        // 2. Insertar partidas
        $sqlPartida = "INSERT INTO partidas (cod_fab, descripcion, proceso, cantidad, unidad_medida, precio_unitario) 
                       VALUES (?, ?, ?, ?, ?, ?)";
        $stmtPartida = $conn->prepare($sqlPartida);
        $id_partida = null;

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
            $id_partida = $stmtPartida->insert_id;
        }

        // 3. Insertar en orden_fab
        if ($id_partida !== null) {
            $sqlOrdenFab = "INSERT INTO orden_fab (id_proyecto, id_cliente, id_partida, of_created) 
                            VALUES (?, ?, ?, NOW())";
            $stmtOrdenFab = $conn->prepare($sqlOrdenFab);
            $stmtOrdenFab->bind_param('sii', $cod_fab, $id_cliente, $id_partida);
            $stmtOrdenFab->execute();
        } else {
            throw new Exception("No se pudo obtener el ID de la partida.");
        }

        // 4. Preparar y enviar correo (debe ser exitoso para hacer commit)
        $to = 'cop.aamap@aamap.net';
        $subject = "Nueva Orden de Fabricacion Directa: $cod_fab";

        // Obtener nombre del cliente
        $sqlCliente = "SELECT nombre_comercial FROM clientes_p WHERE id = ?";
        $stmtCliente = $conn->prepare($sqlCliente);
        $stmtCliente->bind_param('i', $id_cliente);
        $stmtCliente->execute();
        $resultCliente = $stmtCliente->get_result();
        $nombre_cliente = $resultCliente->fetch_assoc()['nombre_comercial'];
        $stmtCliente->close();
        
        // Construir cuerpo del mensaje
        $body = "<h3>Orden de Fabricación Directa: $cod_fab</h3>";
        $body .= "<p><strong>Proyecto:</strong> $nombre</p>";
        $body .= "<p><strong>Cliente ID:</strong> $nombre_cliente</p>";
        $body .= "<p><strong>Fecha Entrega:</strong> $fecha_entrega</p>";
        $body .= "<p><strong>Descripción:</strong> $descripcion</p>";
        
        $body .= "<h4>Partidas:</h4>";
        $body .= "<table border='1' cellpadding='5'><tr><th>#</th><th>Descripción</th><th>Cantidad</th><th>Unidad</th><th>Proceso</th></tr>";
        
        foreach ($partidas as $i => $partida) {
            $body .= "<tr>
                <td>".($i+1)."</td>
                <td>{$partida['descripcion']}</td>
                <td>{$partida['cantidad']}</td>
                <td>{$partida['unidad_medida']}</td>
                <td>{$partida['proceso']}</td>
            </tr>";
        }
        $body .= "</table>";
        
        // Intento de envío de correo (si falla, lanzará excepción)
        send_email_order($to, $subject, $body);
        
        // Si todo sale bien, hacer commit
        $conn->commit();
        echo "<script>alert('Orden de fabricación directa registrada y notificada exitosamente.'); window.location.href = 'direct_projects.php';</script>";
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error en la transacción: " . $e->getMessage());
        echo "<script>alert('Error al registrar: " . addslashes($e->getMessage()) . "');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nueva Orden de Fabricación Directa</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="icon" href="/assets/logo.png" type="image/png">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body style="background-color: rgba(211, 211, 211, 0.4) !important;">

<div class="container">
    <h1 class="text-center">Nueva Orden de Fabricación Directa</h1>
    <a href="direct_projects.php" class="btn btn-secondary">Regresar</a>
    <p>&nbsp;&nbsp;&nbsp;&nbsp;</p>
    <form id="projectForm" method="POST" action="new_of.php">
        <div class="form-group">
            <!-- Campo oculto para el código de fabricación -->
            <input type="text" class="form-control" id="cod_fab" name="cod_fab" value="<?php echo $cod_fab; ?>" hidden required readonly>
        </div>
        <div class="form-group">
            <label for="nombre">Nombre del Proyecto</label>
            <input type="text" class="form-control" id="nombre" name="nombre" required>
        </div>
        <div class="form-group">
            <label for="id_cliente">Cliente</label>
            <select class="form-control" id="id_cliente" name="id_cliente" required>
                <option value="">Seleccionar cliente</option>
                <?php
                // IDs de clientes válidos
                $clientesValidos = [113, 114];
                foreach ($clientes as $cliente):
                    if (in_array($cliente['id'], $clientesValidos)): // Solo mostrar clientes con ID 100 y 105
                ?>
                    <option value="<?php echo $cliente['id']; ?>"><?php echo htmlspecialchars($cliente['nombre_comercial']); ?></option>
                <?php endif; endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="id_comprador">Comprador</label>
            <select class="form-control" id="id_comprador" name="id_comprador" required>
                <option value="">Seleccionar comprador</option>
            </select>
        </div>
        <div class="form-group">
            <label for="descripcion">Nota:</label>
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
                <!-- Campo oculto para el proceso, siempre será "MAN"
                <input type="hidden" id="proceso" value="com">
                <input type="text" class="form-control" value="COM" readonly>-->
                <select class="form-control" id="proceso">
                    <option value="com">COM</option>
                    <option value="man">MAN</option>
                    <option value="maq">MAQ</option>
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
        <button type="submit" class="btn btn-success btn-block">Crear Orden de Fabricación</button>
    </form>
</div>

<script>
    const partidas = [];

    function agregarPartida() {
        const descripcion = $('#descripcion_partida').val();
        const proceso = $('#proceso').val(); // Siempre será "MAN"
        const cantidad = $('#cantidad_partida').val();
        const unidad_medida = $('#um_partida').val();
        const precio_unitario = $('#precio_unitario_partida').val();

        if (descripcion && cantidad && unidad_medida && precio_unitario) {
            partidas.push({ descripcion, proceso, cantidad, unidad_medida, precio_unitario });
            /*$('#partidasTable').append(`
                <tr>
                    <td>${descripcion}</td>
                    <td>MAN</td> <!-- Mostrar "MAN" directamente -->
                    <td>${cantidad}</td>
                    <td>${unidad_medida}</td>
                    <td>${precio_unitario}</td>
                    <td><button class="btn btn-danger btn-sm removePartida">Eliminar</button></td>
                </tr>
            `);*/
            $('#partidasTable').append(`
                <tr>
                    <td>${descripcion}</td>
                    <td>${proceso.toUpperCase()}</td> <!-- Mostrar el proceso seleccionado -->
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
    }

    $('#addPartida').click(agregarPartida);

    $(document).on('click', '.removePartida', function () {
        const index = $(this).closest('tr').index();
        partidas.splice(index, 1);
        $(this).closest('tr').remove();
    });

    $('#projectForm').submit(function () {
        $('#partidas').val(JSON.stringify(partidas));
    });

    // Prevenir el envío del formulario al presionar Enter en los campos de partidas
    $('#descripcion_partida, #cantidad_partida, #um_partida, #precio_unitario_partida').keypress(function (e) {
        if (e.which === 13) { // 13 es el código de la tecla Enter
            e.preventDefault(); // Prevenir el envío del formulario
            agregarPartida(); // Agregar la partida
        }
    });

    $(document).ready(function() {
        // Cuando se cambia el cliente, cargar los compradores
        $('#id_cliente').change(function() {
            var id_cliente = $(this).val();
            if (id_cliente) {
                $.ajax({
                    url: 'get_compradores.php', // Archivo PHP que devuelve los compradores
                    type: 'POST',
                    data: { id_cliente: id_cliente },
                    success: function(response) {
                        $('#id_comprador').html(response); // Actualizar el select de compradores
                    }
                });
            } else {
                $('#id_comprador').html('<option value="">Seleccionar comprador</option>');
            }
        });
    });
</script>

</body>
</html>