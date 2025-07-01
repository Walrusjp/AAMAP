<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: /login.php");
    exit();
}

require 'C:/xampp/htdocs/db_connect.php';
require 'C:/xampp/htdocs/role.php';

date_default_timezone_set("America/Mexico_City");
$fechaH = date("Y-m-d H:i:s");

// Procesar el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $razon = $conn->real_escape_string($_POST['razon']);
    $observaciones = $conn->real_escape_string($_POST['observaciones'] ?? '');
    $id_usuario = $_SESSION['user_id'];
    $productos = json_decode($_POST['productos'], true);
    $id_fab = isset($_POST['id_fab']) ? intval($_POST['id_fab']) : null;

    $conn->begin_transaction();
    try {
        // Generar folio único
        $datePrefix = 'SI-' . date('Ymd') . '-';
        
        $sql = "SELECT folio FROM solicitudes_internas WHERE folio LIKE '$datePrefix%' ORDER BY folio DESC LIMIT 1";
        $result = $conn->query($sql);

        if ($result && $row = $result->fetch_assoc()) {
            $lastFolio = $row['folio'];
            $lastNumber = intval(substr($lastFolio, strrpos($lastFolio, '-') + 1));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        $numberPadded = str_pad($newNumber, 4, '0', STR_PAD_LEFT);
        $folio = $datePrefix . $numberPadded;
        
        // 1. Insertar la solicitud principal
        $insert_solicitud = "INSERT INTO solicitudes_internas 
                    (folio, id_usuario_solicitante, razon, observaciones, fecha_solicitud, estatus, id_fab) 
                    VALUES ('$folio', $id_usuario, '$razon', '$observaciones', '$fechaH', 'pendiente', " 
                    . ($id_fab ? "$id_fab" : "NULL") . ")";
        
        if (!$conn->query($insert_solicitud)) {
            throw new Exception("Error al crear la solicitud: " . $conn->error);
        }
        
        $id_solicitud = $conn->insert_id;
        
        // 2. Insertar los detalles de cada producto
        foreach ($productos as $producto) {
            $id_alm = intval($producto['id_alm']);
            $cantidad = intval($producto['cantidad']);
            
            // Verificar stock
            $stock_query = "SELECT existencia FROM inventario_almacen WHERE id_alm = $id_alm";
            $stock_result = $conn->query($stock_query);
            $stock_data = $stock_result->fetch_assoc();
            
            if ($stock_data['existencia'] < $cantidad) {
                throw new Exception("No hay suficiente stock para el producto: " . $producto['codigo']);
            }
            
            // Insertar detalle
            $insert_detalle = "INSERT INTO solicitudes_detalle 
                              (id_solicitud, id_alm, cantidad) 
                              VALUES ($id_solicitud, $id_alm, $cantidad)";
            
            if (!$conn->query($insert_detalle)) {
                throw new Exception("Error al registrar el detalle: " . $conn->error);
            }
        }
        
        $conn->commit();
        $_SESSION['success'] = "Solicitud registrada con folio: $folio";
        header("Location: req_interna.php");
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "Error al registrar la solicitud: " . $e->getMessage();
    }
}

// Obtener categorías para el select (igual que antes)
$categorias = $conn->query("SELECT * FROM categorias_almacen WHERE id_cat_alm in (1,2,3,6,7)")->fetch_all(MYSQLI_ASSOC);
$nombresPersonalizados = [
    'consumibles' => 'Consumibles',
    'epp' => 'EPP',
    'MP' => 'MP',
    'miscelaneos' => 'Miscelaneos',
    'rotecna' => 'Rotecna'
];

foreach ($categorias as &$categoria) {
    $categoria['categoria_nombre'] = $nombresPersonalizados[$categoria['categoria'] ?? $categoria['categoria']];
}
unset($categoria);

// Obtener órdenes de fabricación activas para el select
$ordenes_fab = $conn->query("
    SELECT of.id_fab, p.nombre as proyecto_nombre, of.plano_ref 
    FROM orden_fab of
    JOIN proyectos p ON of.id_proyecto = p.cod_fab
    WHERE of.id_proyecto IS NOT NULL
    AND p.etapa IN ('en proceso', 'directo')
    ORDER BY of.id_fab DESC
")->fetch_all(MYSQLI_ASSOC);

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Solicitud Interna de Almacén</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="/ERP/stprojects.css">
    <link rel="icon" href="/assets/logo.ico">
    <style>
        .form-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .product-row {
            margin-bottom: 15px;
        }
        #productosTable tbody tr:hover {
            background-color: #f1f1f1;
        }
        .stock-info {
            font-size: 0.9rem;
            margin-top: 5px;
            padding: 3px 10px;
            border-radius: 4px;
            display: inline-block;
        }
        .stock-ok {
            background-color: #d4edda;
            color: #155724;
        }
        .stock-low {
            background-color: #f8d7da;
            color: #721c24;
        }
        /* Estilo para el select de órdenes de fabricación */
        #id_fab {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background-color: white;
        }

        #id_fab option {
            padding: 5px;
            white-space: normal; /* Permite texto multilínea */
        }
    </style>
</head>
<body>
<div class="navbar" style="display: flex; align-items: center; justify-content: space-between; padding: 0px; background-color: #f8f9fa; position: relative;">
    <!-- Logo -->
    <img src="/assets/grupo_aamap.webp" alt="Logo AAMAP" style="width: 18%; position: absolute; top: 25px; left: 10px;">

    <!-- Contenedor de elementos alineados a la derecha -->
    <div class="sticky-header" style="width: 100%;">
        <div class="container" style="display: flex; justify-content: flex-end; align-items: center;">
            <div style="position: absolute; top: 90px; left: 600px;"><p style="font-size: 2.5em; font-family: 'Verdana';"><b>E R P</b></p></div>
            <!-- Botones -->
            <div style="display: flex; align-items: center; gap: 10px; flex-wrap: nowrap;">
                <?php if($username == 'CIS' || $username == 'admin'): ?>
                    <a href="panel_almacen.php" class="btn btn-warning chompa">Requisición Interna</a>
                    <a href="devolucion_prestamo.php" class="btn btn-warning chompa">Asignación de Herramientas</a>
                <?php endif; ?>
                <a href="req_interna.php" class="btn btn-info chompa" style="border: 3px solid gray;">Nueva Requisición</a>
                <a href="prestamo_almacen.php" class="btn btn-info chompa">Nueva Asignación</a>
                <a href="/ERP/all_projects.php" class="btn btn-secondary chompa">Regresar</a>
            </div>
        </div>
    </div>
</div>

<div class="form-container">
    <h2 class="text-center mb-4">Nueva Requisición Interna de Almacén</h2>
    
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>
    
    <form id="solicitudForm" method="POST" action="req_interna.php">
        <div class="form-group">
            <label for="razon">Motivo de la solicitud:</label>
            <textarea class="form-control" id="razon" name="razon" rows="2" required></textarea>
        </div>
        <div class="form-group">
            <label for="id_fab">Orden de Fabricación (opcional):</label>
            <select class="form-control" id="id_fab" name="id_fab">
                <option value="">-- Seleccione una orden --</option> <!-- Mantenimiento interno por default -->
                <?php foreach ($ordenes_fab as $of): ?>
                    <option value="<?php echo $of['id_fab']; ?>">
                        #<?php echo $of['id_fab']; ?> - <?php echo htmlspecialchars($of['proyecto_nombre']); ?> (<?php echo htmlspecialchars($of['plano_ref']); ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <h4>Productos solicitados</h4>
        <div class="row product-row">
            <div class="col-md-5">
                <div class="form-group">
                    <label for="categoria">Categoría:</label>
                    <select class="form-control" id="categoria" name="categoria">
                        <option value="">Seleccione una categoría</option>
                        <?php foreach ($categorias as $cat): ?>
                            <option value="<?php echo $cat['id_cat_alm']; ?>" 
                                    title="<?php echo htmlspecialchars($cat['categoria']); ?>">
                                <?php echo htmlspecialchars($cat['categoria_nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="col-md-5">
                <div class="form-group">
                    <label for="producto">Producto:</label>
                    <select class="form-control" id="producto" name="producto" disabled>
                        <option value="">Primero seleccione una categoría</option>
                    </select>
                    <div id="stockInfo" class="stock-info" style="display: none;"></div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label for="cantidad">Cantidad:</label>
                    <input type="number" class="form-control" id="cantidad" name="cantidad" min="1" value="1">
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12 text-right">
                <button type="button" class="btn btn-primary" id="addProducto">Agregar Producto</button>
            </div>
        </div>
        
        <table class="table table-bordered mt-3" id="productosTable">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Descripción</th>
                    <th>Categoría</th>
                    <th>Cantidad</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
        
        <input type="hidden" name="productos" id="productos">
        <button type="submit" class="btn btn-success btn-block" id="btnRegistrar">Generar Solicitud</button>
    </form>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function() {
    const nombresCategoria = {
        consumibles: 'Consumibles',
        epp: 'Equipo de Proteción Personal',
        MP: 'Materia Prima',
        miscelaneos: 'Miscelaneos'
    };
    const productos = [];
    
    // Cargar productos cuando se selecciona una categoría
    $('#categoria').change(function() {
        var id_cat = $(this).val();
        
        if (id_cat) {
            $.ajax({
                url: 'get_productos_by_categoria.php',
                type: 'GET',
                data: { id_cat: id_cat },
                dataType: 'json',
                success: function(data) {
                    $('#producto').empty();
                    if (data.length > 0) {
                        $('#producto').append('<option value="">Seleccione un producto</option>');
                        $.each(data, function(index, producto) {
                            const nombreCategoria = nombresCategoria[producto.categoria] || producto.categoria;
                            $('#producto').append('<option value="' + producto.id_alm + '" data-stock="' + producto.existencia + '" data-codigo="' + producto.codigo + '" data-descripcion="' + producto.descripcion + '" data-categoria="' + nombreCategoria + '">' + 
                                producto.codigo + ' - ' + producto.descripcion + '</option>');
                        });
                        $('#producto').prop('disabled', false);
                    } else {
                        $('#producto').append('<option value="">No hay productos en esta categoría</option>');
                        $('#producto').prop('disabled', true);
                    }
                }
            });
        } else {
            $('#producto').empty().append('<option value="">Primero seleccione una categoría</option>');
            $('#producto').prop('disabled', true);
        }
    });
    
    // Agregar producto a la tabla
    $('#addProducto').click(function() {
        const id_alm = $('#producto').val();
        const cantidad = parseInt($('#cantidad').val());
        const stock = parseInt($('#producto option:selected').data('stock'));
        const codigo = $('#producto option:selected').data('codigo');
        const descripcion = $('#producto option:selected').data('descripcion');
        const categoria = $('#producto option:selected').data('categoria');
        
        if (!id_alm) {
            alert('Seleccione un producto válido');
            return;
        }
        
        if (cantidad < 1) {
            alert('La cantidad debe ser al menos 1');
            return;
        }
        
        if (cantidad > stock) {
            alert('No hay suficiente stock disponible (Stock: ' + stock + ')');
            return;
        }
        
        // Verificar si el producto ya fue agregado
        const index = productos.findIndex(p => p.id_alm == id_alm);
        if (index >= 0) {
            productos[index].cantidad += cantidad;
        } else {
            productos.push({
                id_alm: id_alm,
                codigo: codigo,
                descripcion: descripcion,
                categoria: categoria,
                cantidad: cantidad,
                stock: stock
            });
        }
        
        actualizarTablaProductos();
        
        // Limpiar selección
        $('#producto').val('').trigger('change');
        $('#cantidad').val(1);
    });
    
    // Actualizar tabla de productos
    function actualizarTablaProductos() {
        const tbody = $('#productosTable tbody');
        tbody.empty();
        
        productos.forEach((producto, index) => {

            tbody.append(`
                <tr>
                    <td>${producto.codigo}</td>
                    <td>${producto.descripcion}</td>
                    <td>${producto.categoria}</td>
                    <td>${producto.cantidad}</td>
                    <td><button type="button" class="btn btn-danger btn-sm removeProducto" data-index="${index}">Eliminar</button></td>
                </tr>
            `);
        });
        
        // Actualizar campo oculto
        $('#productos').val(JSON.stringify(productos));
        $('#btnRegistrar').prop('disabled', productos.length === 0);
    }
    
    // Eliminar producto
    $(document).on('click', '.removeProducto', function() {
        const index = $(this).data('index');
        productos.splice(index, 1);
        actualizarTablaProductos();
    });
    
    // Validar formulario antes de enviar
    $('#solicitudForm').submit(function() {
        if (productos.length === 0) {
            alert('Debe agregar al menos un producto');
            return false;
        }
        return true;
    });
});

// Mostrar stock cuando se selecciona un producto
$('#producto').change(function() {
    const stock = parseInt($(this).find(':selected').data('stock')) || 0;
    const stockInfo = $('#stockInfo');
    
    if (stock > 0 && $(this).val()) {
        stockInfo.text('Stock disponible: ' + stock);
        stockInfo.removeClass('stock-low').addClass('stock-ok');
        stockInfo.show();
        
        // Establecer el máximo en el input de cantidad
        $('#cantidad').attr('max', stock);
    } else {
        stockInfo.hide();
    }
    
    // Resaltar si el stock es bajo (menos de 10 unidades)
    if (stock < 10) {
        stockInfo.removeClass('stock-ok').addClass('stock-low');
    }
});

// Validar cantidad contra el stock máximo
$('#cantidad').on('input', function() {
    const max = parseInt($(this).attr('max')) || 0;
    const value = parseInt($(this).val()) || 0;
    
    if (value > max) {
        $(this).val(max);
        alert('No puede solicitar más del stock disponible');
    }
});
</script>
</body>
</html>