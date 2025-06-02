<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: /login.php");
    exit();
}

require 'C:/xampp/htdocs/db_connect.php';
require 'C:/xampp/htdocs/role.php';
require 'get_compradores.php';

// Obtener clientes para la lista desplegable
$sqlClientes = "SELECT id, nombre_comercial FROM clientes_p";
$resultClientes = $conn->query($sqlClientes);
$clientes = [];
if ($resultClientes->num_rows > 0) {
    while ($row = $resultClientes->fetch_assoc()) {
        $clientes[] = $row;
    }
}
date_default_timezone_set("America/Mexico_City");
// Generar el cod_fab inicial (YYYYMMDD)
$fechaActual = date("Ymd");
$fechaH = date("Y-m-d H:i:s");
$codFabBase = $fechaActual;
$cadenaBusqueda = $codFabBase . "%";
//echo $fechaH;
//echo $fechaActual;

// Verificar si ya existe un proyecto con el mismo cod_fab base
$sqlVerificar = "SELECT cod_fab FROM proyectos WHERE cod_fab LIKE ?";
$stmtVerificar = $conn->prepare($sqlVerificar);
$stmtVerificar->bind_param("s", $cadenaBusqueda);
$stmtVerificar->execute();
$resultVerificar = $stmtVerificar->get_result();

$sufijo = 1; // Inicializar el sufijo en 1

if ($resultVerificar->num_rows > 0) {
    $sufijos = []; // Array para almacenar los sufijos existentes
    while ($row = $resultVerificar->fetch_assoc()) {
        $cod_fab_existente = $row['cod_fab'];
        // Extraer el sufijo si existe
        if (preg_match('/^' . $codFabBase . '-(\d+)$/', $cod_fab_existente, $matches)) {
            $sufijos[] = intval($matches[1]); // Almacenar el sufijo numérico
        }
    }
    // Si hay sufijos, calcular el siguiente
    if (!empty($sufijos)) {
        $sufijo = max($sufijos) + 1; // Incrementar el sufijo máximo encontrado
    }
    // Asignar el nuevo cod_fab con el sufijo
    $cod_fab = $codFabBase . "-" . $sufijo;
} else {
    // Si no hay registros, usar el cod_fab base sin sufijo
    $cod_fab = $codFabBase;
}

// Verificar si se envió el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cod_fab = $_POST['cod_fab'];
    $nombre = $_POST['nombre'];
    $id_cliente = $_POST['id_cliente'];
    $descripcion = $_POST['descripcion'];
    $fecha_entrega = $_POST['fecha_entrega'];
    $partidas = json_decode($_POST['partidas'], true);
    $id_comprador = $_POST['id_comprador'];

    // Capturar datos de vigencia
    $vigencia = $_POST['vigencia'];
    $precios = $_POST['precios'];
    $moneda = $_POST['moneda'];
    $condicion_pago = $_POST['condicion_pago'];
    $lab = $_POST['lab'];
    $tipo_entr = $_POST['tipo_entr'];

    $conn->begin_transaction();
    try {
        // Insertar proyecto
        $sqlProyecto = "INSERT INTO proyectos (cod_fab, nombre, id_cliente, id_comprador, descripcion, etapa)
                VALUES (?, ?, ?, ?, ?, 'creado')";
        $stmtProyecto = $conn->prepare($sqlProyecto);
        $stmtProyecto->bind_param('ssiis', $cod_fab, $nombre, $id_cliente, $id_comprador, $descripcion);
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

        // Insertar datos de vigencia
        $sqlVigencia = "INSERT INTO datos_vigencia (cod_fab, vigencia, precios, moneda, condicion_pago, lab, tipo_entr)
                    VALUES(?,?,?,?,?,?,?)";
        $stmtVigencia = $conn->prepare($sqlVigencia);
        $stmtVigencia->bind_param('sssssss', $cod_fab, $vigencia, $precios, $moneda, $condicion_pago, $lab, $tipo_entr);
        $stmtVigencia->execute();

        $conn->commit();
        echo "<script>alert('Cotización registrada exitosamente.'); window.location.href = 'all_projects.php';</script>";
    } catch (Exception $e) {
        $conn->rollback();
        echo "<script>alert('Error al registrar: " . $e->getMessage() . "');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nueva Cotización</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="icon" href="/assets/logo.png" type="image/png">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body style="background-color: rgba(211, 211, 211, 0.4) !important;">

<div class="container" >
    <h1 class="text-center">Nueva Cotización</h1>
    <a href="all_projects.php" class="btn btn-secondary">Regresar</a>
    <p>&nbsp;&nbsp;&nbsp;&nbsp;</p>
    <form id="projectForm" method="POST" action="new_project.php">
        <?php //if($username === 'h.galicia'): ?>
            <!--<div class="form-group">
                <label for="cod_fab">Número de Cotización</label>
                <input type="text" class="form-control" id="cod_fab" name="cod_fab" value="<?php echo $cod_fab; ?>"  required readonly>
            </div>-->
        <?php //endif; ?>
        <?php //if($username === 'admin'): ?>
            <div class="form-group">
                <label for="cod_fab">Número de Cotización</label>
                <input type="text" class="form-control" id="cod_fab" name="cod_fab" value="<?php echo $cod_fab; ?>"  required>
            </div>
        <?php //endif; ?>
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
            <label for="id_comprador">Comprador</label>
            <select class="form-control" id="id_comprador" name="id_comprador" required>
                <option value="">Seleccionar comprador</option>
            </select>
        </div>
        <div class="form-group">
            <label for="descripcion">Nota:</label>
            <textarea class="form-control" id="descripcion" name="descripcion" rows="3"></textarea>
        </div>

        <!-- Dentro del formulario existente -->
        <h3>Datos de Vigencia</h3>
        <div class="form-group">
            <label for="vigencia">Vigencia</label>
            <input type="text" class="form-control" id="vigencia" name="vigencia" value="<?php setlocale(LC_TIME, "es_ES.UTF-8", "Spanish_Spain", "es_ES"); echo strftime("%B %Y"); ?>" required>
        </div>
        <div class="form-group">
            <label for="precios">Precios</label>
            <input type="text" class="form-control" id="precios" name="precios" value="Sujetos a cambio sin previo aviso" required>
        </div>
        <div class="form-group">
            <label for="moneda">Moneda</label>
            <input type="text" class="form-control" id="moneda" name="moneda" value="MXN/USD/EU" required>
        </div>
        <div class="form-group">
            <label for="condicion_pago">Condición de Pago</label>
            <input type="text" class="form-control" id="condicion_pago" name="condicion_pago" value="60% anticipo y 40% contra aviso de entrega" required>
        </div>
        <div class="form-group">
            <label for="lab">L.a.b.</label>
            <input type="text" class="form-control" id="lab" name="lab" value="Puebla, Pue." required>
        </div>
        <div class="form-group">
            <label for="tipo_entr">Tiempo de Entrega</label>
            <input type="text" class="form-control" id="tipo_entr" name="tipo_entr" value="A convenir con el cliente" required>
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
    </form>
</div>

<script>
    const partidas = [];

    function agregarPartida() {
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