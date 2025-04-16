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

// Obtener productos directos para el select
$sqlProductos = "SELECT pd.*, cp.nombre_comercial as cliente_nombre 
                 FROM productos_p_directas pd
                 JOIN clientes_p cp ON pd.id_cliente = cp.id";
$resultProductos = $conn->query($sqlProductos);
$productos = [];
if ($resultProductos->num_rows > 0) {
    while ($row = $resultProductos->fetch_assoc()) {
        $productos[] = $row;
    }
}

// Generar el cod_fab comenzando desde el último valor registrado
$sqlUltimoCodFab = "SELECT cod_fab FROM proyectos WHERE cod_fab LIKE 'OF-%' ORDER BY LENGTH(cod_fab) DESC, cod_fab DESC LIMIT 1";
$resultUltimoCodFab = $conn->query($sqlUltimoCodFab);

if ($resultUltimoCodFab->num_rows > 0) {
    $row = $resultUltimoCodFab->fetch_assoc();
    $ultimoCodFab = $row['cod_fab'];
    preg_match('/OF-(\d+)/', $ultimoCodFab, $matches);
    if (isset($matches[1])) {
        $ultimoNumero = intval($matches[1]) + 1;
    } else {
        $ultimoNumero = 1000;
    }
} else {
    $ultimoNumero = 1000;
}

$cod_fab = 'OF-' . $ultimoNumero;

// Verificar si se envió el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
        $sqlOrdenFab = "INSERT INTO orden_fab (id_proyecto, id_cliente, of_created) 
        VALUES (?, ?, NOW())";
        $stmtOrdenFab = $conn->prepare($sqlOrdenFab);
        $stmtOrdenFab->bind_param('si', $cod_fab, $id_cliente);
        $stmtOrdenFab->execute();

        // 4. Preparar y enviar correo
        $to = 'cop.aamap@aamap.net';
        $subject = "Nueva Orden de Fabricacion Directa: $cod_fab";

        $sqlCliente = "SELECT nombre_comercial FROM clientes_p WHERE id = ?";
        $stmtCliente = $conn->prepare($sqlCliente);
        $stmtCliente->bind_param('i', $id_cliente);
        $stmtCliente->execute();
        $resultCliente = $stmtCliente->get_result();
        $nombre_cliente = $resultCliente->fetch_assoc()['nombre_comercial'];
        $stmtCliente->close();
        
        $body = "<h3>Orden de Fabricación Directa: $cod_fab</h3>";
        $body .= "<p><strong>Proyecto:</strong> $nombre</p>";
        $body .= "<p><strong>Cliente ID:</strong> $nombre_cliente</p>";
        $body .= "<p><strong>Fecha Entrega:</strong> $fecha_entrega</p>";
        $body .= "<p><strong>Descripción:</strong> $descripcion</p>";
        
        $body .= "<h4>Partidas:</h4>";
        $body .= "<table border='1' cellpadding='5'><tr><th>#</th><th>Descripción</th><th>Cantidad</th><th>Unidad</th><th>Proceso</th><th>Precio Unitario</th></tr>";
        
        foreach ($partidas as $i => $partida) {
            $body .= "<tr>
                <td>".($i+1)."</td>
                <td>{$partida['descripcion']}</td>
                <td>{$partida['cantidad']}</td>
                <td>{$partida['unidad_medida']}</td>
                <td>{$partida['proceso']}</td>
                <td>{$partida['precio_unitario']}</td>
            </tr>";
        }
        $body .= "</table>";
        
        send_email_order($to, $subject, $body);
        
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
    <style>
        body { background-color: rgba(211, 211, 211, 0.4) !important; }
        .product-info { display: none; }
    </style>
</head>
<body>

<div class="container">
    <h1 class="text-center">Nueva Orden de Fabricación Directa</h1>
    <a href="direct_projects.php" class="btn btn-secondary">Regresar</a>
    <p>&nbsp;</p>
    <form id="projectForm" method="POST" action="new_of.php">
        <input type="text" class="form-control" id="cod_fab" name="cod_fab" value="<?php echo $cod_fab; ?>" hidden required readonly>
        
        <div class="form-group">
            <label for="nombre">Nombre del Proyecto</label>
            <input type="text" class="form-control" id="nombre" name="nombre" required>
        </div>
        
        <div class="form-group">
            <label for="id_cliente">Cliente</label>
            <select class="form-control" id="id_cliente" name="id_cliente" required>
                <option value="">Seleccionar cliente</option>
                <?php foreach ($clientes as $cliente): ?>
                    <?php if (in_array($cliente['id'], [113, 114])): ?>
                        <option value="<?php echo $cliente['id']; ?>"><?php echo htmlspecialchars($cliente['nombre_comercial']); ?></option>
                    <?php endif; ?>
                <?php endforeach; ?>
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
        <div class="form-group">
            <label>Modo de entrada:</label>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="modo_partida" id="modo_cargar" value="cargar" checked>
                <label class="form-check-label" for="modo_cargar">Cargar desde producto</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="modo_partida" id="modo_personalizado" value="personalizado">
                <label class="form-check-label" for="modo_personalizado">Personalizado</label>
            </div>
        </div>

        <h3>Partidas</h3>
        <!-- Modo Cargar Datos -->
<div id="cargar_partida" class="form-row">
    <div class="col-md-4">
        <label for="producto_select">Producto</label>
        <select class="form-control" id="producto_select">
            <option value="">Seleccionar producto</option>
            <?php foreach ($productos as $producto): ?>
                <option value="<?php echo $producto['id_pd']; ?>" 
                        data-descripcion="<?php echo htmlspecialchars($producto['descripcion']); ?>"
                        data-proceso="<?php echo $producto['proceso']; ?>"
                        data-um="<?php echo $producto['um']; ?>"
                        data-precio="<?php echo $producto['precio_unitario']; ?>">
                    <?php echo htmlspecialchars($producto['descripcion']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
        <div class="col-md-2">
            <label for="proceso_partida">Proceso</label>
            <input type="text" class="form-control" id="proceso_partida" readonly>
        </div>
        <div class="col-md-2">
            <label for="um_partida">Unidad de Medida</label>
            <input type="text" class="form-control" id="um_partida" readonly>
        </div>
        <div class="col-md-2">
            <label for="precio_unitario_partida">Precio Unitario</label>
            <input type="text" class="form-control" id="precio_unitario_partida" readonly>
        </div>
        <div class="col-md-2">
            <label for="cantidad_partida">Cantidad</label>
            <input type="number" class="form-control" id="cantidad_partida" min="1" value="1">
        </div>
    </div>

    <!-- Modo Personalizado -->
    <div id="personalizado_partida" class="form-row" style="display: none;">
        <div class="col-md-4">
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
        <div class="col-md-2">
            <label for="um_personalizada">Unidad de Medida</label>
            <input type="text" class="form-control" id="um_personalizada">
        </div>
        <div class="col-md-2">
            <label for="precio_personalizado">Precio Unitario</label>
            <input type="number" step="0.01" class="form-control" id="precio_personalizado">
        </div>
    </div>

        <div class="form-row mt-2">
            <div class="col-md-12">
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

    // Manejar cambio de modo partidas
    $('input[name="modo_partida"]').change(function() {
        if ($(this).val() === 'cargar') {
            $('#cargar_partida').show();
            $('#personalizado_partida').hide();
            // Limpiar campos personalizados
            $('#descripcion_personalizada').val('');
            $('#proceso_personalizado').val('MAN');
            $('#cantidad_personalizada').val('1');
            $('#um_personalizada').val('');
            $('#precio_personalizado').val('');
        } else {
            $('#cargar_partida').hide();
            $('#personalizado_partida').show();
            // Limpiar campos de carga
            $('#producto_select').val('');
            $('#proceso_partida').val('');
            $('#um_partida').val('');
            $('#precio_unitario_partida').val('');
            $('#cantidad_partida').val('1');
        }
    });

    // Cuando se selecciona un producto en modo carga
    $('#producto_select').change(function() {
        const selectedOption = $(this).find('option:selected');
        if (selectedOption.val()) {
            $('#proceso_partida').val(selectedOption.data('proceso').toUpperCase());
            $('#um_partida').val(selectedOption.data('um'));
            $('#precio_unitario_partida').val(selectedOption.data('precio'));
            $('#cantidad_partida').focus();
        }
    });

    // Función para agregar partidas
    function agregarPartida() {
        const modo = $('input[name="modo_partida"]:checked').val();
        let partida = {};
        
        if (modo === 'cargar') {
            const selectedOption = $('#producto_select').find('option:selected');
            const cantidad = $('#cantidad_partida').val();
            
            if (!selectedOption.val() || !cantidad) {
                alert('Selecciona un producto e ingresa cantidad');
                return;
            }
            
            partida = {
                descripcion: selectedOption.data('descripcion'),
                proceso: selectedOption.data('proceso'),
                cantidad: cantidad,
                unidad_medida: selectedOption.data('um'),
                precio_unitario: selectedOption.data('precio')
            };
        } else {
            const descripcion = $('#descripcion_personalizada').val();
            const proceso = $('#proceso_personalizado').val();
            const cantidad = $('#cantidad_personalizada').val();
            const um = $('#um_personalizada').val();
            const precio = $('#precio_personalizado').val();
            
            if (!descripcion || !proceso || !cantidad || !um || !precio) {
                alert('Completa todos los campos personalizados');
                return;
            }
            
            partida = {
                descripcion: descripcion,
                proceso: proceso,
                cantidad: cantidad,
                unidad_medida: um,
                precio_unitario: precio
            };
        }
        
        partidas.push(partida);
        
        // Agregar a la tabla
        $('#partidasTable').append(`
            <tr>
                <td>${partida.descripcion}</td>
                <td>${partida.proceso.toUpperCase()}</td>
                <td>${partida.cantidad}</td>
                <td>${partida.unidad_medida}</td>
                <td>$${parseFloat(partida.precio_unitario).toFixed(2)}</td>
                <td><button class="btn btn-danger btn-sm removePartida">Eliminar</button></td>
            </tr>
        `);
        
        // Limpiar campos según el modo
        if (modo === 'cargar') {
            $('#producto_select').val('');
            $('#cantidad_partida').val('1');
            $('#proceso_partida, #um_partida, #precio_unitario_partida').val('');
        } else {
            $('#descripcion_personalizada, #um_personalizada, #precio_personalizado').val('');
            $('#cantidad_personalizada').val('1');
            $('#proceso_personalizado').val('MAN');
        }
    }

    // Event listeners
    $('#addPartida').click(agregarPartida);
    
    $(document).on('click', '.removePartida', function() {
        const index = $(this).closest('tr').index();
        partidas.splice(index, 1);
        $(this).closest('tr').remove();
    });

    $('#projectForm').submit(function() {
        $('#partidas').val(JSON.stringify(partidas));
        return true;
    });

    // Cargar compradores cuando se selecciona un cliente
    $('#id_cliente').change(function() {
        const id_cliente = $(this).val();
        if (id_cliente) {
            $.ajax({
                url: 'get_compradores.php',
                type: 'POST',
                data: { id_cliente: id_cliente },
                success: function(response) {
                    $('#id_comprador').html(response);
                }
            });
        } else {
            $('#id_comprador').html('<option value="">Seleccionar comprador</option>');
        }
    });

    // Permitir agregar partida con Enter en ambos modos
    $('#cantidad_partida, #precio_personalizado').keypress(function(e) {
        if (e.which === 13) {
            e.preventDefault();
            agregarPartida();
        }
    });
</script>

</body>
</html>