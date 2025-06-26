<?php
session_start();

require 'C:/xampp/htdocs/db_connect.php';
require 'C:/xampp/htdocs/role.php';

// Verificar si el usuario está logueado y tiene permisos de almacén
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
        return in_array($user['role'], ['admin', 'almacen']);
    }
    return false;
}

// Procesar entrega de solicitud
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['folio'])) {
    $folio = $conn->real_escape_string($_POST['folio']);
    $id_usuario_almacen = $_SESSION['user_id'];
    $accion = $_POST['accion'] ?? 'entregar';

    if ($accion === 'rechazar') {
        // Cambiar estatus a rechazado
        $update = "UPDATE solicitudes_internas 
                   SET estatus = 'rechazada', 
                       id_usuario_almacen = $id_usuario_almacen,
                       fecha_entrega = NOW()
                   WHERE folio = '$folio' AND estatus = 'pendiente'";
        
        if ($conn->query($update)) {
            $_SESSION['success'] = "Solicitud rechazada correctamente";
        } else {
            $_SESSION['error'] = "Error al rechazar la solicitud";
        }

        header("Location: panel_almacen.php");
        exit();
    }
    
    // Buscar solicitud pendiente y sus detalles
    $query = "SELECT si.id_solicitud, si.folio, si.id_fab, sd.id_alm, sd.cantidad, ia.codigo
          FROM solicitudes_internas si
          JOIN solicitudes_detalle sd ON si.id_solicitud = sd.id_solicitud
          JOIN inventario_almacen ia ON sd.id_alm = ia.id_alm
          WHERE si.folio = '$folio' AND si.estatus = 'pendiente'";
    $result = $conn->query($query);
    
    if ($result->num_rows > 0) {
        $detalles_solicitud = $result->fetch_all(MYSQLI_ASSOC);
        $id_solicitud = $detalles_solicitud[0]['id_solicitud'];
        
        // Iniciar transacción
        $conn->begin_transaction();
        
        try {
            // 1. Verificar stock para todos los productos
            foreach ($detalles_solicitud as $detalle) {
                $stock_query = "SELECT existencia FROM inventario_almacen WHERE id_alm = " . $detalle['id_alm'];
                $stock_result = $conn->query($stock_query);
                $stock_data = $stock_result->fetch_assoc();
                
                if ($stock_data['existencia'] < $detalle['cantidad']) {
                    throw new Exception("No hay suficiente stock para el producto: " . $detalle['codigo']);
                }
            }
            
            // 2. Actualizar solicitud como entregada
            $update_solicitud = "UPDATE solicitudes_internas 
                                 SET estatus = 'entregada', 
                                     id_usuario_almacen = $id_usuario_almacen,
                                     fecha_entrega = NOW()
                                 WHERE id_solicitud = $id_solicitud";
            $conn->query($update_solicitud);
            
            // 3. Procesar cada producto
            foreach ($detalles_solicitud as $detalle) {
                // Registrar movimiento de salida
                $insert_movimiento = "INSERT INTO movimientos_almacen 
                                    (id_alm, tipo_mov, cantidad, id_fab, id_usuario, notas)
                                    VALUES 
                                    (" . $detalle['id_alm'] . ", 'salida', " . $detalle['cantidad'] . ", " . $detalle['id_fab'] . ",
                                    $id_usuario_almacen, 'Salida por solicitud: " . $detalle['folio'] . "')";
                $conn->query($insert_movimiento);
                
                // Actualizar inventario
                $update_inventario = "UPDATE inventario_almacen 
                                     SET existencia = existencia - " . $detalle['cantidad'] . "
                                     WHERE id_alm = " . $detalle['id_alm'];
                $conn->query($update_inventario);
                
                // Actualizar cantidad entregada en el detalle
                $update_detalle = "UPDATE solicitudes_detalle 
                                  SET cantidad_entregada = " . $detalle['cantidad'] . "
                                  WHERE id_solicitud = $id_solicitud AND id_alm = " . $detalle['id_alm'];
                $conn->query($update_detalle);
            }
            
            // Confirmar transacción
            $conn->commit();
            $_SESSION['success'] = "Solicitud entregada correctamente";
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['error'] = "Error al procesar la entrega: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "No se encontró una solicitud pendiente con ese folio";
    }
    
    header("Location: panel_almacen.php");
    exit();
}

// Obtener solicitudes pendientes con sus detalles
$query = "SELECT 
            si.id_solicitud, si.folio, si.fecha_solicitud, si.razon, si.estatus,
            u.nombre as solicitante_nombre,
            COUNT(sd.id_detalle) as total_productos,
            SUM(sd.cantidad) as total_cantidad,
            si.id_fab,
            of.plano_ref,
            p.nombre as proyecto_nombre
          FROM solicitudes_internas si
          JOIN users u ON si.id_usuario_solicitante = u.id
          JOIN solicitudes_detalle sd ON si.id_solicitud = sd.id_solicitud
          LEFT JOIN orden_fab of ON si.id_fab = of.id_fab
          LEFT JOIN proyectos p ON of.id_proyecto = p.cod_fab
          -- WHERE si.estatus = 'pendiente'
          GROUP BY si.id_solicitud
          ORDER BY si.fecha_solicitud DESC";
$solicitudes = $conn->query($query)->fetch_all(MYSQLI_ASSOC);

// Obtener detalles de productos para cada solicitud
foreach ($solicitudes as &$solicitud) {
    $detalle_query = "SELECT 
                        sd.id_alm, sd.cantidad, 
                        ia.codigo, ia.descripcion,
                        cat.categoria
                      FROM solicitudes_detalle sd
                      JOIN inventario_almacen ia ON sd.id_alm = ia.id_alm
                      JOIN categorias_almacen cat ON ia.id_cat_alm = cat.id_cat_alm
                      WHERE sd.id_solicitud = " . $solicitud['id_solicitud'];
    $solicitud['detalles'] = $conn->query($detalle_query)->fetch_all(MYSQLI_ASSOC);
}
unset($solicitud); // Romper la referencia

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel de Almacén - Solicitudes Internas</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="/ERP/stprojects.css">
    <link rel="icon" href="/assets/logo.ico">
    <style>
        .table-container {
            margin: 20px auto;
            width: 95%;
            overflow-x: auto;
        }
        .search-box {
            max-width: 400px;
            margin-bottom: 20px;
        }
        .product-details {
            background-color: #f8f9fa;
            padding: 10px;
            margin-top: 5px;
            border-radius: 5px;
        }
        .product-row {
            margin-bottom: 5px;
            padding-bottom: 5px;
            border-bottom: 1px solid #dee2e6;
        }
        .product-row:last-child {
            border-bottom: none;
        }
        .accordion-toggle {
            cursor: pointer;
        }
        .hidden-row {
            display: none;
        }
        /* Estilo para la celda clickeable */
        td:nth-child(4) {
            cursor: pointer;
            transition: background-color 0.2s;
        }

        td:nth-child(4):hover {
            background-color: #f0f0f0;
        }

        /* Estilo para la tabla en el modal */
        .modal-body table {
            width: 100%;
        }

        .modal-body th {
            background-color: #f8f9fa;
        }
        /* Estilo para la celda clickeable */
        .td-productos {
            transition: background-color 0.2s;
            color: #007bff;
            text-decoration: underline;
        }

        .td-productos:hover {
            background-color: #f0f0f0;
            color: #0056b3;
        }
        /* Estilo para el badge de rechazado */
        .badge-danger {
            background-color: #dc3545;
            color: white;
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
                <a href="panel_almacen.php" class="btn btn-warning chompa" style="border: 3px solid gray;">Requicisión Interna</a>
                <a href="devolucion_prestamo.php" class="btn btn-warning chompa">Prestámos</a>
                <a href="req_interna.php" class="btn btn-info chompa">Nueva Requisición</a>
                <a href="prestamo_almacen.php" class="btn btn-info chompa">Nuevo prestámo</a>
                <a href="/ERP/all_projects.php" class="btn btn-secondary chompa">Regresar</a>
            </div>
        </div>
    </div>
</div>

<div class="table-container">
    <h2 class="text-center mb-4">Gestión de Requisiciones Internas</h2>
    
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>
    
    <table class="table table-striped table-bordered">
        <thead>
            <tr>
                <th>Folio</th>
                <th>Fecha Solicitud</th>
                <th>Solicitante</th>
                <th>OF</th>
                <th>Comentarios</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($solicitudes)): ?>
                <?php foreach ($solicitudes as $sol): ?>
                    <tr class="accordion-toggle" data-id="<?php echo $sol['id_solicitud']; ?>">
                        <td class="td-productos" ><?php echo htmlspecialchars($sol['folio']); ?></td>
                        <td><?php echo htmlspecialchars($sol['fecha_solicitud']); ?></td>
                        <td><?php echo htmlspecialchars($sol['solicitante_nombre']); ?></td>
                        <td>
                            <?php if ($sol['id_fab']): ?>
                                <small class="text-muted">Orden #<?php echo $sol['id_fab']; ?></small><br>
                                <?php echo htmlspecialchars($sol['proyecto_nombre'] ?? 'Sin proyecto'); ?><br>
                                <small><?php echo htmlspecialchars($sol['plano_ref'] ?? ''); ?></small>
                            <?php else: ?>
                                <span class="text-muted">N/A</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($sol['razon']); ?></td>
                        <td>
                            <?php if ($sol['estatus'] === 'pendiente'): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="folio" value="<?php echo htmlspecialchars($sol['folio']); ?>">
                                    <button type="submit" class="btn btn-primary btn-sm btn-entregar">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                            <path d="M8.5 6.5a.5.5 0 0 0-1 0v3.793L6.354 9.146a.5.5 0 1 0-.708.708l2 2a.5.5 0 0 0 .708 0l2-2a.5.5 0 0 0-.708-.708L8.5 10.293V6.5z"/>
                                        </svg>
                                        Entregar
                                    </button>
                                </form>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="folio" value="<?php echo htmlspecialchars($sol['folio']); ?>">
                                    <input type="hidden" name="accion" value="rechazar">
                                    <button type="submit" class="btn btn-danger btn-sm btn-rechazar">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                            <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/>
                                        </svg>
                                        Rechazar
                                    </button>
                                </form>
                            <?php elseif ($sol['estatus'] === 'entregada'): ?>
                                <p style="color: green;">entregado</p>
                            <?php elseif ($sol['estatus'] === 'rechazada'): ?>
                                <p style="color: darkred;">rechazado</p>
                            <?php endif; ?>

                        </td>
                    </tr>
                    <tr class="hidden-row">
                        <td colspan="7" class="p-0">
                            <div id="detalle-<?php echo $sol['id_solicitud']; ?>" class="collapse">
                                <div class="product-details">
                                    <?php foreach ($sol['detalles'] as $detalle): ?>
                                        <div class="product-row">
                                            <strong><?php echo htmlspecialchars($detalle['codigo']); ?></strong> - 
                                            <?php echo htmlspecialchars($detalle['descripcion']); ?>
                                            <span class="badge badge-primary"><?php echo htmlspecialchars($detalle['categoria']); ?></span>
                                            <span class="badge badge-secondary float-right">Cantidad: <?php echo htmlspecialchars($detalle['cantidad']); ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" class="text-center">No hay solicitudes pendientes</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal para detalles de productos -->
<div class="modal fade" id="productosModal" tabindex="-1" role="dialog" aria-labelledby="productosModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="productosModalLabel">Detalle de productos</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Descripción</th>
                            <th>Categoría</th>
                            <th>Cantidad</th>
                        </tr>
                    </thead>
                    <tbody id="modalProductosBody">
                        <!-- Aquí se cargarán los productos dinámicamente -->
                    </tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script>
$(document).ready(function() {
    // Alternar visibilidad de detalles al hacer clic en la fila
    $('.accordion-toggle').click(function() {
        $(this).next('.hidden-row').find('.collapse').collapse('toggle');
    });
});

$(document).ready(function() {
    // Manejar clic en la celda de productos
    $('td.td-productos').click(function() {

        const row = $(this).closest('tr');
        const detalles = row.data('detalles');
        
        if (detalles && detalles.length > 0) {
            // Limpiar el cuerpo del modal
            $('#modalProductosBody').empty();
            
            // Llenar con los detalles
            detalles.forEach(producto => {
                $('#modalProductosBody').append(`
                    <tr>
                        <td>${producto.codigo}</td>
                        <td>${producto.descripcion}</td>
                        <td>${producto.categoria}</td>
                        <td>${producto.cantidad}</td>
                    </tr>
                `);
            });
            
            // Mostrar el modal
            $('#productosModal').modal('show');
        }
    });

    // Al cargar la página, guardar los detalles en data attributes
    <?php foreach ($solicitudes as $sol): ?>
        $('tr[data-id="<?php echo $sol['id_solicitud']; ?>"]').data('detalles', <?php echo json_encode($sol['detalles']); ?>);
    <?php endforeach; ?>
});


document.addEventListener('DOMContentLoaded', function () {
    const botonesEntrega = document.querySelectorAll('.btn-entregar');
    const botonesRechazar = document.querySelectorAll('.btn-rechazar');

    botonesEntrega.forEach(function (boton) {
        boton.addEventListener('click', function (e) {
            const confirmar = confirm("¿Estás seguro de que deseas marcar esta solicitud como entregada?");
            if (!confirmar) {
                e.preventDefault();
            }
        });
    });

    botonesRechazar.forEach(function (boton) {
        boton.addEventListener('click', function (e) {
            const confirmar = confirm("¿Estás seguro de que deseas rechazar esta solicitud?");
            if (!confirmar) {
                e.preventDefault();
            }
        });
    });
});

</script>
</body>
</html>