<?php
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['username'])) {
    header("Location: /login.php");
    exit();
}

require 'C:/xampp/htdocs/db_connect.php';
require 'C:/xampp/htdocs/role.php';

// Procesar el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_alm = intval($_POST['id_alm']);
    $cantidad = intval($_POST['cantidad']);
    $razon = $conn->real_escape_string($_POST['razon']);
    $id_usuario = $_SESSION['user_id']; // Asume que el ID de usuario está en la sesión
    
    // Verificar stock disponible
    $stock_query = "SELECT existencia FROM inventario_almacen WHERE id_alm = $id_alm";
    $stock_result = $conn->query($stock_query);
    $stock_data = $stock_result->fetch_assoc();
    
    if ($stock_data['existencia'] >= $cantidad) {
        // Generar folio único (ejemplo: SI-20250619-1234)
        $folio = 'SI-' . date('Ymd') . '-' . rand(1000, 9999);
        
        $insert_query = "INSERT INTO solicitudes_internas 
                        (folio, id_usuario_solicitante, id_alm, cantidad, razon) 
                        VALUES ('$folio', $id_usuario, $id_alm, $cantidad, '$razon')";
        
        if ($conn->query($insert_query)) {
            $_SESSION['success'] = "Solicitud creada con folio: $folio";
            header("Location: req_interna.php");
            exit();
        } else {
            $_SESSION['error'] = "Error al crear la solicitud: " . $conn->error;
        }
    } else {
        $_SESSION['error'] = "No hay suficiente stock disponible para esta solicitud";
    }
}

// Obtener categorías para el select
$categorias = $conn->query("SELECT * FROM categorias_almacen WHERE 1")->fetch_all(MYSQLI_ASSOC);

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
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .product-select {
            display: none;
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
                <a href="panel_almacen.php" class="btn btn-warning chompa">Solicitudes</a>
                <a href="devolucion_prestamo.php" class="btn btn-warning chompa">Prestámos</a>
                <a href="req_interna.php" class="btn btn-info chompa" style="border: 3px solid gray;">Nueva Requisición</a>
                <a href="prestamo_almacen.php" class="btn btn-info chompa">Nuevo prestámo</a>
                <a href="/ERP/all_projects.php" class="btn btn-secondary chompa">Regresar</a>
            </div>
        </div>
    </div>
</div>

<div class="form-container">
    <h2 class="text-center mb-4">Solicitud Interna de Almacén</h2>
    
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>
    
    <form method="POST" action="req_interna.php">
        <div class="form-group">
            <label for="categoria">Categoría:</label>
            <select class="form-control" id="categoria" name="categoria" required>
                <option value="">Seleccione una categoría</option>
                <?php foreach ($categorias as $cat): ?>
                    <option value="<?php echo $cat['id_cat_alm']; ?>"><?php echo htmlspecialchars($cat['categoria']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label for="producto">Producto:</label>
            <select class="form-control" id="producto" name="id_alm" required disabled>
                <option value="">Primero seleccione una categoría</option>
            </select>
            <div id="stock-info" class="text-muted mt-2"></div>
        </div>
        
        <div class="form-group">
            <label for="cantidad">Cantidad:</label>
            <input type="number" class="form-control" id="cantidad" name="cantidad" min="1" required>
        </div>
        
        <div class="form-group">
            <label for="razon">Razón/Notas:</label>
            <textarea class="form-control" id="razon" name="razon" rows="3" required></textarea>
        </div>
        
        <button type="submit" class="btn btn-primary btn-block">Generar Solicitud</button>
    </form>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script>
$(document).ready(function() {
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
                            $('#producto').append('<option value="' + producto.id_alm + '" data-stock="' + producto.existencia + '">' + 
                                producto.codigo + ' - ' + producto.descripcion + '</option>');
                        });
                        $('#producto').prop('disabled', false);
                    } else {
                        $('#producto').append('<option value="">No hay productos en esta categoría</option>');
                        $('#producto').prop('disabled', true);
                    }
                    $('#stock-info').text('');
                }
            });
        } else {
            $('#producto').empty().append('<option value="">Primero seleccione una categoría</option>');
            $('#producto').prop('disabled', true);
            $('#stock-info').text('');
        }
    });
    
    // Mostrar información de stock cuando se selecciona un producto
    $('#producto').change(function() {
        var stock = $(this).find(':selected').data('stock');
        if (stock !== undefined) {
            $('#stock-info').text('Stock disponible: ' + stock);
            $('#cantidad').attr('max', stock);
        } else {
            $('#stock-info').text('');
        }
    });
});
</script>
</body>
</html>