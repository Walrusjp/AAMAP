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
            'tipo_mov' => 'entrada',
            'id_pr' => $_POST['id_pr'],
            'uuid' => uniqid() // Generamos un UUID único para cada item
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
                        (id_alm, tipo_mov, cantidad, id_pr, id_usuario, notas, uuid)
                        VALUES 
                        (?, 'entrada', ?, ?, ?, ?, ?)";

                $stmt = $conn->prepare($query);
                $stmt->bind_param(
                    "iiisss",
                    $item['id_alm'],
                    $item['cantidad'],
                    $item['id_pr'],
                    $id_usuario,
                    $notas,
                    $item['uuid']
                );
                $stmt->execute();
                
                // Actualizar inventario (sumamos en lugar de restar)
                $update = "UPDATE inventario_almacen 
                           SET existencia = existencia + {$item['cantidad']}
                           WHERE id_alm = {$item['id_alm']}";
                $conn->query($update);
            }
            
            $conn->commit();
            unset($_SESSION['movimiento_temp']);
            $mensaje = ['success' => true, 'message' => 'Entrada registrada correctamente'];
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

// Obtener proveedores para el select
$proveedores_query = "SELECT id_pr, empresa FROM proveedores WHERE activo = 1 ORDER BY empresa";
$proveedores_result = $conn->query($proveedores_query);

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
    <title>Entradas Manuales de Almacén</title>
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
        <!-- Logo y menú (igual que en tu original) -->
    </div>

<div class="container mt-4">
    <h2 class="text-center mb-4">Entradas Manuales de Almacén</h2>
    
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
                        <label for="id_pr">Proveedor</label>
                        <select class="form-control" id="id_pr" name="id_pr" required>
                            <option value="">Seleccionar...</option>
                            <?php while ($prov = $proveedores_result->fetch_assoc()): ?>
                                <option value="<?php echo $prov['id_pr']; ?>">
                                    <?php echo htmlspecialchars($prov['empresa']); ?>
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
                        <th>Proveedor</th>
                        <th>Código</th>
                        <th>Descripción</th>
                        <th>Categoría</th>
                        <th>Cantidad</th>
                        <th>UUID</th>
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
                        
                        // Obtener detalles del proveedor
                        $prov_query = "SELECT nombre FROM proveedores WHERE id_pr = {$item['id_pr']}";
                        $proveedor = $conn->query($prov_query)->fetch_assoc();
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($proveedor['nombre']); ?></td>
                            <td><?php echo htmlspecialchars($producto['codigo']); ?></td>
                            <td><?php echo htmlspecialchars($producto['descripcion']); ?></td>
                            <td><?php echo htmlspecialchars($producto['categoria']); ?></td>
                            <td><?php echo $item['cantidad']; ?></td>
                            <td><small><?php echo $item['uuid']; ?></small></td>
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
                        Confirmar Entrada
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
                url: 'get_productos.php?categoria_id=' + categoriaId,
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
    
    // Validación del formulario para entradas
    $('#movimientoForm').submit(function(e) {
        if ($('#producto').val() === '') {
            e.preventDefault();
            alert('Por favor seleccione un producto');
            return false;
        }
        
        return true;
    });
});
</script>
</body>
</html>