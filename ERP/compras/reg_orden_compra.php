<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: /login.php");
    exit();
}

require 'C:/xampp/htdocs/db_connect.php';
require 'C:/xampp/htdocs/role.php';

date_default_timezone_set("America/Mexico_City");
$fechaActual = date("Ymd");
$fechaH = date("Y-m-d H:i:s");
//echo $fechaH;

// Obtener datos necesarios para los select
$proveedores = $conn->query("SELECT * FROM proveedores WHERE activo = TRUE")->fetch_all(MYSQLI_ASSOC);
$proyectos = $conn->query("SELECT cod_fab, nombre FROM proyectos WHERE etapa IN ('aprobado', 'en proceso', 'directo')")->fetch_all(MYSQLI_ASSOC);
$articulos = $conn->query("SELECT id_alm, codigo, descripcion FROM inventario_almacen WHERE activo = TRUE")->fetch_all(MYSQLI_ASSOC);
$usuarios = $conn->query("SELECT id, nombre FROM users WHERE role IN ('compras', 'admin', 'almacen')")->fetch_all(MYSQLI_ASSOC);

// Generar folio automático (OC-YYYYMMDD o OC-YYYYMMDD-N si hay múltiples)
$folio_base = 'OC-' . date('Ymd');
$last_oc = $conn->query("SELECT folio FROM ordenes_compra WHERE folio LIKE '$folio_base%' ORDER BY id_oc DESC LIMIT 1")->fetch_assoc();

if ($last_oc) {
    // Si ya existe al menos una OC hoy
    $parts = explode('-', $last_oc['folio']);
    if (count($parts) === 2) {
        // Es la primera del día (OC-YYYYMMDD), esta será la segunda (OC-YYYYMMDD-1)
        $folio = $folio_base . '-1';
    } else {
        // Ya tiene sufijo, incrementarlo
        $sufijo = intval($parts[2]);
        $folio = $folio_base . '-' . ($sufijo + 1);
    }
} else {
    // Primera OC del día (sin sufijo)
    $folio = $folio_base;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $folio = $_POST['folio'];
    $id_pr = $_POST['id_pr'];
    $id_fab = !empty($_POST['id_fab']) ? $_POST['id_fab'] : null;
    $fecha_requerida = $_POST['fecha_requerida'];
    $descripcion_destino = $_POST['descripcion_destino'];
    $solicitante = $_SESSION['user_id'];
    $items = $_POST['items'];

    // Iniciar transacción
    $conn->begin_transaction();

    try {
        // Insertar orden de compra principal
        $sql_oc = "INSERT INTO ordenes_compra 
                  (folio, id_pr, id_fab, fecha_requerida, descripcion_destino, solicitante) 
                  VALUES (?, ?, ?, ?, ?, ?)";
        $stmt_oc = $conn->prepare($sql_oc);
        $stmt_oc->bind_param("sisssi", $folio, $id_pr, $id_fab, $fecha_requerida, $descripcion_destino, $solicitante);
        $stmt_oc->execute();
        $id_oc = $stmt_oc->insert_id;

        // Insertar detalles de la orden
        $sql_detalle = "INSERT INTO detalle_orden_compra 
                        (id_oc, id_alm, cantidad, precio_unitario, id_pr) 
                        VALUES (?, ?, ?, ?, ?)";
        $stmt_detalle = $conn->prepare($sql_detalle);

        $subtotal_total = 0;
        foreach ($items as $item) {
            $id_alm = $item['id_alm'];
            $cantidad = $item['cantidad'];
            $precio_unitario = $item['precio_unitario'];
            $id_pr_detalle = $item['id_pr'] ?? $id_pr; // Usar proveedor específico si existe
            
            $stmt_detalle->bind_param("iiidi", $id_oc, $id_alm, $cantidad, $precio_unitario, $id_pr_detalle);
            $stmt_detalle->execute();
            
            $subtotal_total += $cantidad * $precio_unitario;
        }

        // Calcular totales (IVA del 16% en México)
        $iva = $subtotal_total * 0.16;
        $total = $subtotal_total + $iva;

        // Actualizar totales en la orden
        $sql_update = "UPDATE ordenes_compra SET subtotal = ?, iva = ?, total = ? WHERE id_oc = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("dddi", $subtotal_total, $iva, $total, $id_oc);
        $stmt_update->execute();

        // Registrar el estatus inicial
        $sql_log = "INSERT INTO logs_estatus_oc (id_oc, estatus, id_usuario) VALUES (?, 'solicitada', ?)";
        $stmt_log = $conn->prepare($sql_log);
        $stmt_log->bind_param("ii", $id_oc, $solicitante);
        $stmt_log->execute();

        // Commit de la transacción
        $conn->commit();

        //ver_orden_compra.php?id=$id_oc
        echo "<script>
                alert('Orden de compra registrada exitosamente con folio $folio');
                window.location.href = 'ver_ordenes_compra.php';
              </script>";
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Error al registrar la orden: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar Orden de Compra</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="/ERP/stprojects.css">
    <link rel="icon" href="/assets/logo.ico">
</head>
<body style="background-color: rgba(211, 211, 211, 0.4) !important;">
<div class="container mt-4">
    <h1>Registrar Orden de Compra</h1>
    <a href="ver_ordenes_compra.php" class="btn btn-secondary mb-3">Regresar</a>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST" action="reg_orden_compra.php" id="oc-form">
        <div class="form-group">
            <label for="folio">Folio</label>
            <input type="text" class="form-control" id="folio" name="folio" value="<?php echo htmlspecialchars($folio); ?>" >
        </div>
        
        <div class="form-group">
            <label for="id_pr">Proveedor</label>
            <select class="form-control" id="id_pr" name="id_pr" required>
                <option value="">Seleccionar proveedor</option>
                <?php foreach ($proveedores as $prov): ?>
                    <option value="<?php echo $prov['id_pr']; ?>">
                        <?php echo htmlspecialchars($prov['empresa']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label for="fecha_requerida">Fecha Requerida</label>
            <input type="date" class="form-control" id="fecha_requerida" name="fecha_requerida" required min="<?php echo date('Y-m-d'); ?>">
        </div>
        
        <div class="form-group">
            <label for="id_fab">Proyecto/Orden de Fabricación (opcional)</label>
            <select class="form-control" id="id_fab" name="id_fab" >
                <option value="">Sin proyecto/OF</option>
                <?php foreach ($proyectos as $proy): ?>
                    <?php 
                    // Obtener órdenes de fabricación para este proyecto
                    $ofs = $conn->query("SELECT id_fab FROM orden_fab WHERE id_proyecto = '".$proy['cod_fab']."'")->fetch_all(MYSQLI_ASSOC);
                    ?>
                    <?php if (count($ofs) > 0): ?>
                        <?php foreach ($ofs as $of): ?>
                            <option value="<?php echo $of['id_fab']; ?>">
                                <?php echo htmlspecialchars($proy['cod_fab'] . ' - OF-' . $of['id_fab']); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label for="descripcion_destino">Descripción/Destino</label>
            <input type="text" class="form-control" id="descripcion_destino" name="descripcion_destino">
        </div>
        
        <div class="form-group">
            <label>Artículos</label>
            <button type="button" class="btn btn-primary btn-block mb-2" data-toggle="modal" data-target="#articulosModal">
                Agregar Artículo
            </button>
            
            <div id="items-container">
                <div class="alert alert-info" id="no-items-message">No hay artículos agregados</div>
            </div>
        </div>
        
        <div class="form-group">
            <label>Totales</label>
            <table class="table table-bordered">
                <tr>
                    <th>Subtotal</th>
                    <td id="subtotal">$0.00</td>
                </tr>
                <tr>
                    <th>IVA (16%)</th>
                    <td id="iva">$0.00</td>
                </tr>
                <tr class="table-active">
                    <th>Total</th>
                    <td id="total">$0.00</td>
                </tr>
            </table>
        </div>
        
        <button type="submit" class="btn btn-success btn-block">Guardar Orden de Compra</button>
    </form>
</div>

<!-- Modal para artículos -->
<div class="modal fade" id="articulosModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Seleccionar Artículo</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <input type="text" class="form-control" id="search-articulo" placeholder="Buscar artículo...">
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover" id="tabla-articulos">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Descripción</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($articulos as $art): ?>
                                <tr data-id="<?php echo $art['id_alm']; ?>" 
                                    data-codigo="<?php echo htmlspecialchars($art['codigo']); ?>"
                                    data-descripcion="<?php echo htmlspecialchars($art['descripcion']); ?>">
                                    <td><?php echo htmlspecialchars($art['codigo']); ?></td>
                                    <td><?php echo htmlspecialchars($art['descripcion']); ?></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-primary btn-add-articulo">
                                            Agregar
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script>
$(document).ready(function() {
    // Mostrar modal de búsqueda
    $('#search-articulo, #btn-search-articulo').click(function() {
        $('#articulosModal').modal('show');
    });

    // Filtrar artículos en el modal
    $('#articulosModal input[type="search"]').keyup(function() {
        var search = $(this).val().toLowerCase();
        $('#tabla-articulos tbody tr').each(function() {
            var texto = $(this).text().toLowerCase();
            $(this).toggle(texto.indexOf(search) > -1);
        });
    });

    // Agregar artículo al formulario
    $('.btn-add-articulo').click(function() {
        var row = $(this).closest('tr');
        var id = row.data('id');
        var codigo = row.data('codigo');
        var descripcion = row.data('descripcion');
        
        // Verificar si el artículo ya fue agregado
        if ($(`#item-${id}`).length) {
            alert('Este artículo ya fue agregado');
            return;
        }

        // Crear HTML del nuevo item
        var itemHtml = `
        <div class="item-row" id="item-${id}">
            <input type="hidden" name="items[${id}][id_alm]" value="${id}">
            <div class="form-row">
                <div class="form-group col-md-2">
                    <label>Código</label>
                    <input type="text" class="form-control" value="${codigo}" readonly>
                </div>
                <div class="form-group col-md-4">
                    <label>Descripción</label>
                    <input type="text" class="form-control" value="${descripcion}" readonly>
                </div>
                <div class="form-group col-md-2">
                    <label>Cantidad</label>
                    <input type="number" name="items[${id}][cantidad]" class="form-control item-cantidad" 
                           min="1" value="1" required>
                </div>
                <div class="form-group col-md-2">
                    <label>Precio Unitario</label>
                    <input type="number" name="items[${id}][precio_unitario]" class="form-control item-precio" 
                           step="0.01" min="0" value="0" required>
                </div>
                <div class="form-group col-md-2">
                    <label>Subtotal</label>
                    <input type="text" class="form-control item-subtotal" value="$0.00" readonly>
                </div>
            </div>
            <div class="item-actions text-right">
                <button type="button" class="btn btn-sm btn-danger btn-remove-item" data-id="${id}">
                    Eliminar
                </button>
            </div>
        </div>`;

        // Agregar al contenedor
        $('#no-items-message').hide();
        $('#items-container').append(itemHtml);
        $('#articulosModal').modal('hide');

        // Actualizar totales
        updateTotals();
    });

    // Eliminar artículo del formulario
    $(document).on('click', '.btn-remove-item', function() {
        var id = $(this).data('id');
        $(`#item-${id}`).remove();
        
        if ($('#items-container .item-row').length === 0) {
            $('#no-items-message').show();
        }
        
        updateTotals();
    });

    // Calcular subtotal cuando cambian cantidad o precio
    $(document).on('change', '.item-cantidad, .item-precio', function() {
        var row = $(this).closest('.item-row');
        var cantidad = parseFloat(row.find('.item-cantidad').val()) || 0;
        var precio = parseFloat(row.find('.item-precio').val()) || 0;
        var subtotal = cantidad * precio;
        
        row.find('.item-subtotal').val('$' + subtotal.toFixed(2));
        updateTotals();
    });

    // Función para actualizar totales
    function updateTotals() {
        var subtotal = 0;
        
        $('.item-row').each(function() {
            var cantidad = parseFloat($(this).find('.item-cantidad').val()) || 0;
            var precio = parseFloat($(this).find('.item-precio').val()) || 0;
            subtotal += cantidad * precio;
        });
        
        var iva = subtotal * 0.16;
        var total = subtotal + iva;
        
        $('#subtotal').text('$' + subtotal.toFixed(2));
        $('#iva').text('$' + iva.toFixed(2));
        $('#total').text('$' + total.toFixed(2));
    }

    // Validar formulario antes de enviar
    $('#oc-form').submit(function(e) {
        if ($('.item-row').length === 0) {
            alert('Debe agregar al menos un artículo a la orden');
            e.preventDefault();
            return false;
        }
        
        // Validar que todos los campos requeridos estén completos
        var valid = true;
        $(this).find('[required]').each(function() {
            if (!$(this).val()) {
                $(this).addClass('is-invalid');
                valid = false;
            } else {
                $(this).removeClass('is-invalid');
            }
        });
        
        if (!valid) {
            alert('Por favor complete todos los campos requeridos');
            e.preventDefault();
            return false;
        }
        
        return true;
    });
});

$(document).on('keydown', '.item-precio', function(e) {
    if (e.key === 'Enter' || e.keyCode === 13) {
        e.preventDefault();
        var $current = $(this);
        var $next = $current.closest('.form-row').find('.form-group').eq(
            $current.closest('.form-group').index() + 1
        ).find('input');
        
        if ($next.length) {
            $next.focus();
        } else {
            // Si no hay siguiente campo, hacer submit del formulario
            $('#oc-form').submit();
        }
    }
});
</script>
</body>
</html>