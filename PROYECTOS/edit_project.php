<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: /login.php");
    exit();
}

require 'C:/xampp/htdocs/db_connect.php';

// Obtener la lista de proyectos para el select
$sqlProyectos = "SELECT cod_fab, nombre FROM proyectos";
$resultProyectos = $conn->query($sqlProyectos);
$proyectos = [];
if ($resultProyectos->num_rows > 0) {
    while ($row = $resultProyectos->fetch_assoc()) {
        $proyectos[] = $row;
    }
}

// Obtener clientes para la lista desplegable
$sqlClientes = "SELECT id, nombre_comercial FROM clientes_p";
$resultClientes = $conn->query($sqlClientes);
$clientes = [];
if ($resultClientes->num_rows > 0) {
    while ($row = $resultClientes->fetch_assoc()) {
        $clientes[] = $row;
    }
}

// Si se selecciona un proyecto, cargar sus datos
$proyecto = null;
$partidas = [];
$datosVigencia = null;
if (isset($_GET['cod_fab'])) {
    $cod_fab = $_GET['cod_fab'];

    // Obtener datos del proyecto
    $sqlProyecto = "SELECT * FROM proyectos WHERE cod_fab = ?";
    $stmtProyecto = $conn->prepare($sqlProyecto);
    $stmtProyecto->bind_param("s", $cod_fab);
    $stmtProyecto->execute();
    $resultProyecto = $stmtProyecto->get_result();
    $proyecto = $resultProyecto->fetch_assoc();

    // Obtener partidas del proyecto
    $sqlPartidas = "SELECT * FROM partidas WHERE cod_fab = ?";
    $stmtPartidas = $conn->prepare($sqlPartidas);
    $stmtPartidas->bind_param("s", $cod_fab);
    $stmtPartidas->execute();
    $resultPartidas = $stmtPartidas->get_result();
    while ($row = $resultPartidas->fetch_assoc()) {
        $partidas[] = $row;
    }

    // Obtener datos de vigencia del proyecto
    $sqlVigencia = "SELECT * FROM datos_vigencia WHERE cod_fab = ?";
    $stmtVigencia = $conn->prepare($sqlVigencia);
    $stmtVigencia->bind_param("s", $cod_fab);
    $stmtVigencia->execute();
    $resultVigencia = $stmtVigencia->get_result();
    $datosVigencia = $resultVigencia->fetch_assoc();
}

// Verificar si se envió el formulario de edición
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cod_fab = $_POST['cod_fab'];
    $nombre = $_POST['nombre'];
    $id_cliente = $_POST['id_cliente'];
    $descripcion = $_POST['descripcion'];
    $fecha_entrega = $_POST['fecha_entrega'];
    $partidas = json_decode($_POST['partidas'], true);

    // Capturar datos de vigencia
    $vigencia = $_POST['vigencia'];
    $precios = $_POST['precios'];
    $moneda = $_POST['moneda'];
    $condicion_pago = $_POST['condicion_pago'];
    $lab = $_POST['lab'];
    $tipo_entr = $_POST['tipo_entr'];

    $conn->begin_transaction();
    try {
        // Actualizar proyecto
        $sqlUpdateProyecto = "UPDATE proyectos SET nombre = ?, id_cliente = ?, descripcion = ?, fecha_entrega = ? WHERE cod_fab = ?";
        $stmtUpdateProyecto = $conn->prepare($sqlUpdateProyecto);
        $stmtUpdateProyecto->bind_param('sisss', $nombre, $id_cliente, $descripcion, $fecha_entrega, $cod_fab);
        $stmtUpdateProyecto->execute();

        // Eliminar registros dependientes en registro_estatus
        $sqlDeleteRegistroEstatus = "DELETE FROM registro_estatus WHERE id_partida IN (SELECT id FROM partidas WHERE cod_fab = ?)";
        $stmtDeleteRegistroEstatus = $conn->prepare($sqlDeleteRegistroEstatus);
        $stmtDeleteRegistroEstatus->bind_param('s', $cod_fab);
        $stmtDeleteRegistroEstatus->execute();

        // Eliminar partidas antiguas
        $sqlDeletePartidas = "DELETE FROM partidas WHERE cod_fab = ?";
        $stmtDeletePartidas = $conn->prepare($sqlDeletePartidas);
        $stmtDeletePartidas->bind_param('s', $cod_fab);
        $stmtDeletePartidas->execute();

        // Insertar nuevas partidas
        $sqlInsertPartida = "INSERT INTO partidas (cod_fab, descripcion, proceso, cantidad, unidad_medida, precio_unitario) 
                             VALUES (?, ?, ?, ?, ?, ?)";
        $stmtInsertPartida = $conn->prepare($sqlInsertPartida);
        foreach ($partidas as $partida) {
            $stmtInsertPartida->bind_param(
                'sssiss', 
                $cod_fab, 
                $partida['descripcion'], 
                $partida['proceso'], 
                $partida['cantidad'], 
                $partida['unidad_medida'], 
                $partida['precio_unitario']
            );
            $stmtInsertPartida->execute();
        }

        // Actualizar datos de vigencia
        $sqlUpdateVigencia = "UPDATE datos_vigencia SET vigencia = ?, precios = ?, moneda = ?, condicion_pago = ?, lab = ?, tipo_entr = ? WHERE cod_fab = ?";
        $stmtUpdateVigencia = $conn->prepare($sqlUpdateVigencia);
        $stmtUpdateVigencia->bind_param('sssssss', $vigencia, $precios, $moneda, $condicion_pago, $lab, $tipo_entr, $cod_fab);
        $stmtUpdateVigencia->execute();

        $conn->commit();
        echo "<script>alert('Cotización actualizada exitosamente.'); window.location.href = 'all_projects.php';</script>";
    } catch (Exception $e) {
        $conn->rollback();
        echo "<script>alert('Error al actualizar: " . $e->getMessage() . "');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Cotización</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="icon" href="/assets/logo.png" type="image/png">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>

<div class="container">
    <h1 class="text-center">Editar Cotización</h1>
    <a href="all_projects.php" class="btn btn-secondary">Regresar</a>
    <p>&nbsp;&nbsp;&nbsp;&nbsp;</p>

    <!-- Select para elegir proyecto -->
    <div class="form-group">
        <label for="selectProyecto">Seleccionar Cotización</label>
        <select class="form-control" id="selectProyecto" onchange="cargarProyecto(this.value)">
            <option value="">Seleccionar cotización</option>
            <?php foreach ($proyectos as $proy): ?>
                <option value="<?php echo $proy['cod_fab']; ?>" <?php echo (isset($proyecto['cod_fab']) && $proy['cod_fab'] === $proyecto['cod_fab']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($proy['cod_fab']); ?> || <?php echo htmlspecialchars($proy['nombre']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- Formulario de edición -->
    <form id="projectForm" method="POST" action="edit_project.php">
        <input type="hidden" name="cod_fab" value="<?php echo $proyecto['cod_fab'] ?? ''; ?>">
        
        <div class="form-group">
            <label for="nombre">Nombre</label>
            <input type="text" class="form-control" id="nombre" name="nombre" value="<?php echo $proyecto['nombre'] ?? ''; ?>" required>
        </div>
        <div class="form-group">
            <label for="id_cliente">Cliente</label>
            <select class="form-control" id="id_cliente" name="id_cliente" required>
                <option value="">Seleccionar cliente</option>
                <?php foreach ($clientes as $cliente): ?>
                    <option value="<?php echo $cliente['id']; ?>" 
                        <?php echo (isset($proyecto['id_cliente']) && $cliente['id'] == $proyecto['id_cliente']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cliente['nombre_comercial']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="descripcion">Nota:</label>
            <textarea class="form-control" id="descripcion" name="descripcion" rows="3"><?php echo $proyecto['descripcion'] ?? ''; ?></textarea>
        </div>
        <div class="form-group">
            <label for="fecha_entrega">Fecha de Entrega</label>
            <input type="date" class="form-control" id="fecha_entrega" name="fecha_entrega" value="<?php echo $proyecto['fecha_entrega'] ?? ''; ?>" required>
        </div>

        <!-- Datos de Vigencia -->
        <h3>Datos de Vigencia</h3>
        <div class="form-group">
            <label for="vigencia">Vigencia</label>
            <input type="text" class="form-control" id="vigencia" name="vigencia" value="<?php echo $datosVigencia['vigencia'] ?? ''; ?>" required>
        </div>
        <div class="form-group">
            <label for="precios">Precios</label>
            <input type="text" class="form-control" id="precios" name="precios" value="<?php echo $datosVigencia['precios'] ?? ''; ?>" required>
        </div>
        <div class="form-group">
            <label for="moneda">Moneda</label>
            <input type="text" class="form-control" id="moneda" name="moneda" value="<?php echo $datosVigencia['moneda'] ?? ''; ?>" required>
        </div>
        <div class="form-group">
            <label for="condicion_pago">Condición de Pago</label>
            <input type="text" class="form-control" id="condicion_pago" name="condicion_pago" value="<?php echo $datosVigencia['condicion_pago'] ?? ''; ?>" required>
        </div>
        <div class="form-group">
            <label for="lab">L.a.b.</label>
            <input type="text" class="form-control" id="lab" name="lab" value="<?php echo $datosVigencia['lab'] ?? ''; ?>" required>
        </div>
        <div class="form-group">
            <label for="tipo_entr">Tipo de Entrega</label>
            <input type="text" class="form-control" id="tipo_entr" name="tipo_entr" value="<?php echo $datosVigencia['tipo_entr'] ?? ''; ?>" required>
        </div>

        <!-- Partidas -->
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
            <tbody id="partidasTable">
                <?php if (!empty($partidas)): ?>
                    <?php foreach ($partidas as $partida): ?>
                        <tr>
                            <td><?php echo $partida['descripcion']; ?></td>
                            <td><?php echo $partida['proceso']; ?></td>
                            <td><?php echo $partida['cantidad']; ?></td>
                            <td><?php echo $partida['unidad_medida']; ?></td>
                            <td><?php echo $partida['precio_unitario']; ?></td>
                            <td><button class="btn btn-danger btn-sm removePartida">Eliminar</button></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <input type="hidden" name="partidas" id="partidas" value="<?php echo htmlspecialchars(json_encode($partidas)); ?>">
        <button type="submit" class="btn btn-success btn-block">Guardar Cambios</button>
    </form>
</div>

<script>
const partidas = <?php echo json_encode($partidas); ?>;

    // Función para agregar una partida
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

    // Agregar partida al hacer clic en el botón
    $('#addPartida').click(agregarPartida);

    // Eliminar partida
    $(document).on('click', '.removePartida', function () {
        const index = $(this).closest('tr').index();
        partidas.splice(index, 1);
        $(this).closest('tr').remove();
    });

    // Prevenir el envío del formulario al presionar Enter en los campos de partidas
    $('#descripcion_partida, #cantidad_partida, #um_partida, #precio_unitario_partida').keypress(function (e) {
        if (e.which === 13) { // 13 es el código de la tecla Enter
            e.preventDefault(); // Prevenir el envío del formulario
            agregarPartida(); // Agregar la partida
        }
    });

    // Convertir partidas a JSON antes de enviar el formulario
    $('#projectForm').submit(function () {
        $('#partidas').val(JSON.stringify(partidas));
    });

    // Función para convertir una celda en un input editable
        function hacerEditable(celda) {
            const valorOriginal = celda.textContent;
            const input = document.createElement('input');
            input.type = 'text';
            input.value = valorOriginal;
            input.classList.add('form-control');

            // Limpiar la celda y agregar el input
            celda.textContent = '';
            celda.appendChild(input);
            input.focus();

            // Guardar el valor al presionar Enter
            input.addEventListener('keypress', function (e) {
                if (e.key === 'Enter') {
                    const nuevoValor = input.value.trim();
                    if (nuevoValor !== '') {
                        celda.textContent = nuevoValor;
                        actualizarPartida(celda);
                    }
                }
            });

            // Cancelar la edición al presionar Escape
            input.addEventListener('keyup', function (e) {
                if (e.key === 'Escape') {
                    celda.textContent = valorOriginal;
                }
            });
        }

        // Función para actualizar el valor en el array partidas
        function actualizarPartida(celda) {
            const fila = celda.parentElement;
            const index = Array.from(fila.parentElement.children).indexOf(fila);
            const campo = Array.from(fila.children).indexOf(celda);

            // Obtener los nuevos valores de la fila
            const nuevaPartida = {
                descripcion: fila.children[0].textContent,
                proceso: fila.children[1].textContent,
                cantidad: fila.children[2].textContent,
                unidad_medida: fila.children[3].textContent,
                precio_unitario: fila.children[4].textContent,
            };

            // Actualizar el array partidas
            partidas[index] = nuevaPartida;
        }

        // Agregar evento de doble clic a las celdas de la tabla
        document.querySelectorAll('#partidasTable td').forEach(celda => {
            celda.addEventListener('dblclick', function () {
                if (!celda.querySelector('input')) { // Evitar editar si ya hay un input
                    hacerEditable(celda);
                }
            });
        });

    // Función para cargar los datos del proyecto seleccionado
    function cargarProyecto(cod_fab) {
        if (cod_fab) {
            window.location.href = `edit_project.php?cod_fab=${cod_fab}`;
        }
    }
</script>

</body>
</html>