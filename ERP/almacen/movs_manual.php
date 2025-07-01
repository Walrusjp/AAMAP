<?php
session_start();
require 'C:/xampp/htdocs/db_connect.php';
require 'C:/xampp/htdocs/role.php';

if (!isset($_SESSION['username']) || !tienePermisoAlmacen($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit();
}

function tienePermisoAlmacen($user_id) {
    global $conn;
    $query = "SELECT role FROM users WHERE id = $user_id";
    $result = $conn->query($query);
    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        return in_array($user['role'], ['admin', 'operador']);
    }
    return false;
}

// Procesar el formulario de movimiento
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['agregar_producto'])) {
        // Agregar producto a la tabla temporal
        $_SESSION['movimiento_temp'][] = [
            'id_alm' => $_POST['producto'],
            'cantidad' => $_POST['cantidad'],
            'tipo_mov' => 'salida',
            'id_fab' => $_POST['id_fab']
        ];
    } elseif (isset($_POST['confirmar_movimiento'])) {
        // Confirmar todo el movimiento
        $conn->begin_transaction();
        try {
            $id_usuario = $_SESSION['user_id'];
            $notas = $conn->real_escape_string($_POST['notas'] ?? '');
            
            foreach ($_SESSION['movimiento_temp'] as $item) {
                // Insertar movimiento
                $query = "INSERT INTO movimientos_almacen 
                        (id_alm, tipo_mov, cantidad, id_fab, id_usuario, notas)
                        VALUES 
                        (?, 'salida', ?, ?, ?, ?)";

                $stmt = $conn->prepare($query);
                $stmt->bind_param(
                    "iiiss",
                    $item['id_alm'],
                    $item['cantidad'],
                    $item['id_fab'],
                    $id_usuario,
                    $notas
                );
                $stmt->execute();
                
                // Actualizar inventario
                $update = "UPDATE inventario_almacen 
                           SET existencia = existencia - {$item['cantidad']}
                           WHERE id_alm = {$item['id_alm']}";
                $conn->query($update);
            }
            
            $conn->commit();
            unset($_SESSION['movimiento_temp']);
            $mensaje = ['success' => true, 'message' => 'Salida registrada correctamente'];
        } catch (Exception $e) {
            $conn->rollback();
            $mensaje = ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    } elseif (isset($_POST['eliminar_item'])) {
        // Eliminar un item específico de la tabla temporal
        $index = $_POST['index'];
        if (isset($_SESSION['movimiento_temp'][$index])) {
            unset($_SESSION['movimiento_temp'][$index]);
            $_SESSION['movimiento_temp'] = array_values($_SESSION['movimiento_temp']);
        }
    } elseif (isset($_POST['limpiar'])) {
        // Limpiar toda la tabla temporal
        unset($_SESSION['movimiento_temp']);
    }
}

// Obtener órdenes de fabricación para el select con nombre de proyecto
$of_query = "SELECT of.id_fab, p.nombre, p.etapa as proyecto_nombre 
             FROM orden_fab of
             LEFT JOIN proyectos p ON of.id_proyecto = p.cod_fab
             WHERE of.id_proyecto IS NOT NULL
             AND p.etapa IN ('en proceso', 'directo')
             ORDER BY of.id_fab DESC";
$of_result = $conn->query($of_query);

// Obtener categorías para el select
$categorias_query = "SELECT id_cat_alm, categoria FROM categorias_almacen 
WHERE id_cat_alm IN (1,2,6,7) ORDER BY categoria";
$categorias_result = $conn->query($categorias_query);

// Inicializar array temporal si no existe
if (!isset($_SESSION['movimiento_temp'])) {
    $_SESSION['movimiento_temp'] = [];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Movimientos Manuales de Almacén</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="/ERP/stprojects.css">
    <link rel="icon" href="/assets/logo.ico">
    <style>
        .table-container {
            margin: 20px auto;
            width: 95%;
            overflow-x: auto;
        }
        .form-section {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .productos-table {
            margin-top: 20px;
        }
        .badge-entrada {
            background-color: #28a745;
            color: white;
        }
        .badge-salida {
            background-color: #dc3545;
            color: white;
        }
        .hidden {
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
            <!-- Buscador y botones -->
            <div style="display: flex; align-items: center; gap: 10px; flex-wrap: nowrap;">
                <!-- Buscador -->
                <form method="GET" action="ver_almacen.php" class="form-inline" style="margin-right: 10px;">
                    <div class="input-group">
                        <?php if(isset($_GET['search']) && !empty($_GET['search'])): ?>
                            <a href="ver_almacen.php" class="input-group-prepend" title="Cancelar búsqueda" style="display: flex; align-items: center; padding: 0 5px;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" style="margin-right: 5px;">
                                    <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                                    <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/>
                                </svg>
                            </a>
                        <?php endif; ?>
                        <input type="text" name="search" class="form-control" id="psearch" 
                            placeholder="Buscar artículos..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" style="width: 200px;">
                        <div class="input-group-append">
                            <button type="submit" class="btn btn-outline-secondary" title="Buscar">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                    <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </form>
                
                <!-- Botones -->
                <a href="reg_articulo_alm.php" class="btn btn-success chompa">Nuevo Artículo</a>
                <?php if($role === 'admin'): ?>
                    <a href="historico_movs_alm.php" class="btn btn-info chompa">Ver Movimientos</a>
                <?php endif; ?>
                <a href="movs_manual.php" class="btn btn-info chompa">Registro Manual</a>
                <a href="ver_almacen.php" class="btn btn-secondary chompa">Regresar</a>
            </div>
        </div>
    </div>
</div>

<div class="container mt-4">
    <h2 class="text-center mb-4">Salidas Manuales de Almacén</h2>
    
    <?php if (isset($mensaje)): ?>
        <div class="alert alert-<?php echo $mensaje['success'] ? 'success' : 'danger'; ?>">
            <?php echo $mensaje['message']; ?>
        </div>
    <?php endif; ?>
    
    <div class="form-section">
        <form method="post" id="movimientoForm">
            <div class="row">
                
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="id_fab">Orden de Fabricación</label>
                        <select class="form-control" id="id_fab" name="id_fab" required>
                            <option value="">Seleccionar...</option>
                            <?php while ($of = $of_result->fetch_assoc()): ?>
                                <option value="<?php echo $of['id_fab']; ?>">
                                    #<?php echo $of['id_fab']; ?> - <?php echo htmlspecialchars($of['nombre']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="categoria">Categoría</label>
                        <select class="form-control" id="categoria" name="categoria" required>
                            <option value="">Seleccionar...</option>
                            <?php while ($cat = $categorias_result->fetch_assoc()): ?>
                                <option value="<?php echo $cat['id_cat_alm']; ?>">
                                    <?php echo htmlspecialchars($cat['categoria']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="producto">Producto</label>
                        <select class="form-control" id="producto" name="producto" disabled required>
                            <option value="">Primero seleccione una categoría</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="cantidad">Cantidad</label>
                        <input type="number" class="form-control" id="cantidad" name="cantidad" min="1" required>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="notas">Notas</label>
                        <input type="text" class="form-control" id="notas" name="notas" placeholder="Observaciones del movimiento">
                    </div>
                </div>
                
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" name="agregar_producto" class="btn btn-primary">
                        Agregar Producto
                    </button>
                </div>
            </div>
        </form>
    </div>
    
    <?php if (!empty($_SESSION['movimiento_temp'])): ?>
        <div class="table-responsive productos-table">
            <table class="table table-bordered">
                <thead class="thead-light">
                    <tr>
                        <th>OF</th>
                        <th>Código</th>
                        <th>Descripción</th>
                        <th>Categoría</th>
                        <th>Cantidad</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($_SESSION['movimiento_temp'] as $index => $item): 
                        // Obtener detalles del producto
                        $prod_query = "SELECT ia.codigo, ia.descripcion, ia.existencia, ca.categoria 
                                      FROM inventario_almacen ia
                                      JOIN categorias_almacen ca ON ia.id_cat_alm = ca.id_cat_alm
                                      WHERE ia.id_alm = {$item['id_alm']}";
                        $producto = $conn->query($prod_query)->fetch_assoc();
                        
                        // Obtener detalles de la OF
                        $of_query = "SELECT plano_ref FROM orden_fab WHERE id_fab = {$item['id_fab']}";
                        $of = $conn->query($of_query)->fetch_assoc();
                    ?>
                        <tr>
                            <td>#<?php echo $item['id_fab']; ?></td>
                            <td><?php echo htmlspecialchars($producto['codigo']); ?></td>
                            <td><?php echo htmlspecialchars($producto['descripcion']); ?></td>
                            <td><?php echo htmlspecialchars($producto['categoria']); ?></td>
                            <td><?php echo $item['cantidad']; ?></td>
                            <td>
                                <form method="post" style="display: inline;">
                                    <input type="hidden" name="index" value="<?php echo $index; ?>">
                                    <button type="submit" name="eliminar_item" class="btn btn-sm btn-danger">
                                        Eliminar
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div class="text-right mt-3">
                <form method="post" style="display: inline-block; margin-right: 10px;">
                    <button type="submit" name="limpiar" class="btn btn-secondary">
                        Limpiar Todo
                    </button>
                </form>
                
                <form method="post" style="display: inline-block;">
                    <input type="hidden" name="notas" value="<?php echo $_POST['notas'] ?? ''; ?>">
                    <button type="submit" name="confirmar_movimiento" class="btn btn-success">
                        Confirmar Movimiento
                    </button>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function() {
    // Cargar productos según categoría seleccionada
    $('#categoria').change(function() {
        var categoriaId = $(this).val();
        if (categoriaId) {
            $('#producto').prop('disabled', false);
            
            $.ajax({
                url: 'get_productos.php?categoria_id=' + categoriaId + '&existencia_min=1',
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    $('#producto').empty();
                    $('#producto').append('<option value="">Seleccionar producto...</option>');
                    
                    $.each(data, function(key, value) {
                        $('#producto').append('<option value="'+value.id_alm+'" data-existencia="'+value.existencia+'">'
                            + value.codigo + ' - ' + value.descripcion 
                            + ' (Existencia: ' + value.existencia + ')'
                            + '</option>');
                    });
                },
                error: function() {
                    alert('Error al cargar productos');
                }
            });
        } else {
            $('#producto').prop('disabled', true);
            $('#producto').empty();
            $('#producto').append('<option value="">Primero seleccione una categoría</option>');
        }
    });
    
    // Validación del formulario para salidas
    $('#movimientoForm').submit(function(e) {
        if ($('#producto').val() === '') {
            e.preventDefault();
            alert('Por favor seleccione un producto');
            return false;
        }
        
        var cantidad = parseInt($('#cantidad').val());
        var existencia = parseInt($('#producto option:selected').data('existencia'));
        
        if (cantidad > existencia) {
            e.preventDefault();
            alert('No hay suficiente existencia. Existencia actual: ' + existencia);
            return false;
        }
        
        return true;
    });
});
</script>
</body>
</html>