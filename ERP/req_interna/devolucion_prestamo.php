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
        return in_array($user['role'], ['admin', 'almacen']);
    }
    return false;
}


// Procesar devolución rápida (sin observaciones)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'devolucion_rapida') {
    header('Content-Type: application/json');
    
    $folio = $conn->real_escape_string($_POST['folio']);
    $id_usuario = $_SESSION['user_id'];
    
    $response = ['success' => false, 'message' => ''];
    
    // Obtener préstamo y sus detalles
    $prestamo_query = "SELECT p.* FROM prestamos_almacen p WHERE p.folio = '$folio' AND p.estatus != 'devuelto'";
    $prestamo_result = $conn->query($prestamo_query);
    
    if ($prestamo_result->num_rows > 0) {
        $prestamo = $prestamo_result->fetch_assoc();
        $id_prestamo = $prestamo['id_prestamo'];
        
        // Obtener detalles del préstamo
        $detalles_query = "SELECT * FROM solicitudes_detalle WHERE id_prestamo = $id_prestamo";
        $detalles_result = $conn->query($detalles_query);
        $detalles = $detalles_result->fetch_all(MYSQLI_ASSOC);
        
        // Iniciar transacción
        $conn->begin_transaction();
        
        try {
            // 1. Actualizar préstamo como devuelto
            $update_prestamo = "UPDATE prestamos_almacen 
                               SET estatus = 'devuelto',
                                   fecha_devolucion = NOW(),
                                   id_usuario_almacen = $id_usuario
                               WHERE id_prestamo = $id_prestamo";
            $conn->query($update_prestamo);

            error_log("Entrando a la transacción. Prestamo ID: $id_prestamo");
            error_log("Detalles encontrados: " . count($detalles));
            
            // 2. Procesar cada producto
            foreach ($detalles as $detalle) {
                $cantidad_a_devolver = $detalle['cantidad'] ;
                
                error_log("Detalle ID: {$detalle['id_detalle']} | Cantidad: {$detalle['cantidad']} | Entregada: {$detalle['cantidad_entregada']} | A devolver: $cantidad_a_devolver");
                if ($cantidad_a_devolver > 0) {
                    // Actualizar detalle con cantidad devuelta
                    $update_detalle = "UPDATE solicitudes_detalle 
                                      SET cantidad_entregada = cantidad
                                      WHERE id_detalle = " . $detalle['id_detalle'];
                    $conn->query($update_detalle);
                    
                    // Registrar movimiento de entrada
                    $movimiento_query = "INSERT INTO movimientos_almacen 
                                    (id_alm, tipo_mov, cantidad, id_fab, id_usuario, notas)
                                    VALUES 
                                    (" . $detalle['id_alm'] . ", 'entrada', $cantidad_a_devolver, " . $prestamo['id_fab'] . ", 
                                    $id_usuario, 'Devolución rápida préstamo: $folio')";
                    $conn->query($movimiento_query);
                    
                    // Actualizar inventario
                    $inventario_query = "UPDATE inventario_almacen 
                                       SET existencia = existencia + $cantidad_a_devolver
                                       WHERE id_alm = " . $detalle['id_alm'];
                    $conn->query($inventario_query);
                }
            }
            
            $conn->commit();
            $response['success'] = true;
            $response['message'] = "Devolución rápida registrada correctamente";
            $response['folio'] = $folio;
        } catch (Exception $e) {
            $conn->rollback();
            $response['message'] = "Error al procesar devolución: " . $e->getMessage();
        }
    } else {
        $response['message'] = "No se encontró el préstamo con ese folio o ya fue devuelto";
    }
    
    echo json_encode($response);
    exit();
}

// Procesar devolución con observaciones
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'devolucion_completa') {
    header('Content-Type: application/json');
    
    $folio = $conn->real_escape_string($_POST['folio']);
    $notas = $conn->real_escape_string($_POST['notas'] ?? '');
    $id_usuario = $_SESSION['user_id'];
    
    $response = ['success' => false, 'message' => ''];
    
    // Obtener préstamo y sus detalles
    $prestamo_query = "SELECT p.* FROM prestamos_almacen p WHERE p.folio = '$folio' AND p.estatus != 'devuelto'";
    $prestamo_result = $conn->query($prestamo_query);
    
    if ($prestamo_result->num_rows > 0) {
        $prestamo = $prestamo_result->fetch_assoc();
        $id_prestamo = $prestamo['id_prestamo'];
        
        // Obtener detalles del préstamo
        $detalles_query = "SELECT * FROM solicitudes_detalle WHERE id_prestamo = $id_prestamo";
        $detalles_result = $conn->query($detalles_query);
        $detalles = $detalles_result->fetch_all(MYSQLI_ASSOC);
        
        // Iniciar transacción
        $conn->begin_transaction();
        
        try {
            // 1. Actualizar préstamo como devuelto
            $update_prestamo = "UPDATE prestamos_almacen 
                               SET estatus = 'devuelto',
                                   fecha_devolucion = NOW(),
                                   observaciones = '$notas',
                                   id_usuario_almacen = $id_usuario
                               WHERE id_prestamo = $id_prestamo";
            $conn->query($update_prestamo);
            
            // 2. Procesar cada producto
            foreach ($detalles as $detalle) {
                $cantidad_a_devolver = $detalle['cantidad'];
                
                if ($cantidad_a_devolver > 0) {
                    // Actualizar detalle con cantidad devuelta
                    $update_detalle = "UPDATE solicitudes_detalle 
                                      SET cantidad_entregada = cantidad
                                      WHERE id_detalle = " . $detalle['id_detalle'];
                    $conn->query($update_detalle);
                    
                    // Registrar movimiento de entrada
                    $movimiento_query = "INSERT INTO movimientos_almacen 
                                    (id_alm, tipo_mov, cantidad, id_fab, id_usuario, notas)
                                    VALUES 
                                    (" . $detalle['id_alm'] . ", 'entrada', $cantidad_a_devolver, " . $prestamo['id_fab'] . ", 
                                    $id_usuario, 'Devolución completa préstamo: $folio. Notas: $notas')";
                    $conn->query($movimiento_query);
                    
                    // Actualizar inventario
                    $inventario_query = "UPDATE inventario_almacen 
                                       SET existencia = existencia + $cantidad_a_devolver
                                       WHERE id_alm = " . $detalle['id_alm'];
                    $conn->query($inventario_query);
                }
            }
            
            $conn->commit();
            $response['success'] = true;
            $response['message'] = "Devolución registrada correctamente";
            $response['folio'] = $folio;
        } catch (Exception $e) {
            $conn->rollback();
            $response['message'] = "Error al procesar devolución: " . $e->getMessage();
        }
    } else {
        $response['message'] = "No se encontró el préstamo con ese folio o ya fue devuelto";
    }
    
    echo json_encode($response);
    exit();
}

// Obtener préstamos activos con sus detalles
$query = "SELECT 
            p.id_prestamo, p.folio, p.fecha_prestamo, p.estatus, p.observaciones,
            u.nombre as solicitante,
            COUNT(sd.id_detalle) as total_productos,
            SUM(sd.cantidad) as cantidad_total,
            SUM(sd.cantidad_entregada) as cantidad_devuelta,
            p.id_fab,
            of.plano_ref,
            pr.nombre as proyecto_nombre
          FROM prestamos_almacen p
          JOIN users u ON p.id_usuario_solicitante = u.id
          JOIN solicitudes_detalle sd ON p.id_prestamo = sd.id_prestamo
          LEFT JOIN orden_fab of ON p.id_fab = of.id_fab
          LEFT JOIN proyectos pr ON of.id_proyecto = pr.cod_fab
          WHERE p.estatus != 'devuelto'
          GROUP BY p.id_prestamo
          ORDER BY p.fecha_prestamo DESC";
$prestamos = $conn->query($query)->fetch_all(MYSQLI_ASSOC);

// Obtener detalles de productos para cada préstamo
foreach ($prestamos as &$prestamo) {
    $detalle_query = "SELECT 
                        sd.id_alm, sd.cantidad, sd.cantidad_entregada,
                        ia.codigo, ia.descripcion,
                        cat.categoria
                      FROM solicitudes_detalle sd
                      JOIN inventario_almacen ia ON sd.id_alm = ia.id_alm
                      JOIN categorias_almacen cat ON ia.id_cat_alm = cat.id_cat_alm
                      WHERE sd.id_prestamo = " . $prestamo['id_prestamo'];
    $prestamo['detalles'] = $conn->query($detalle_query)->fetch_all(MYSQLI_ASSOC);
}
unset($prestamo); // Romper la referencia

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Devolución de Préstamos de Almacén</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="/ERP/stprojects.css">
    <link rel="icon" href="/assets/logo.ico">
    <style>
        .table-container {
            margin: 20px auto;
            width: 95%;
            overflow-x: auto;
        }
        .badge-solicitado { background-color:rgb(64, 148, 223); }
        .badge-prestado { background-color: #ffc107; color: #212529; }
        .badge-parcialmente_devuelto { background-color: #17a2b8; }
        .badge-devuelto { background-color: #28a745; }
        .btn-devolver {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .btn-devolver svg {
            margin-right: 5px;
        }
        .btn-rapido {
            background-color: #28a745;
            border-color: #28a745;
        }
        .btn-detalle {
            background-color: #17a2b8;
            border-color: #17a2b8;
        }
        .btn-accion {
            margin-bottom: 5px;
            width: 100%;
        }
        .alert-info p {
            margin-bottom: 0.5rem;
        }
        .alert-info strong {
            min-width: 120px;
            display: inline-block;
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
        .proyecto-info {
            display: inline-block;
            max-width: 200px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            vertical-align: middle;
        }
        /* Estilo para el spinner de carga */
        .spinner-border {
            vertical-align: middle;
            margin-right: 5px;
        }

        /* Estilo para botón deshabilitado */
        .btn-accion:disabled {
            opacity: 0.65;
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
                <a href="panel_almacen.php" class="btn btn-warning chompa">Requicisión Interna</a>
                <a href="devolucion_prestamo.php" class="btn btn-warning chompa" style="border: 3px solid gray;">Prestámos</a>
                <a href="req_interna.php" class="btn btn-info chompa">Nueva Requisición</a>
                <a href="prestamo_almacen.php" class="btn btn-info chompa">Nuevo prestámo</a>
                <a href="/ERP/all_projects.php" class="btn btn-secondary chompa">Regresar</a>
            </div>
        </div>
    </div>
</div>

<div class="table-container">
    <h2 class="text-center mb-4">Gestión de Herramientas</h2>
    
    <div id="alert-container"></div>
    
    <h4>Préstamos Activos</h4>
    <table class="table table-striped table-bordered" id="tabla-prestamos">
        <thead>
            <tr>
                <th>Folio</th>
                <th>Fecha Préstamo</th>
                <th>Solicitante</th>
                <th>OF</th>
                <th>Productos</th>
                <th>Total Prestado</th>
                <th>Devuelto</th>
                <th>Pendiente</th>
                <th>Estatus</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($prestamos)): ?>
                <?php foreach ($prestamos as $p): 
                    $pendiente = $p['cantidad_total'] - $p['cantidad_devuelta'];
                ?>
                    <tr data-folio="<?php echo htmlspecialchars($p['folio']); ?>">
                        <td><?php echo htmlspecialchars($p['folio']); ?></td>
                        <td><?php echo htmlspecialchars($p['fecha_prestamo']); ?></td>
                        <td><?php echo htmlspecialchars($p['solicitante']); ?></td>
                        <td>
                            <?php if ($p['id_fab']): ?>
                                <small class="text-muted">Orden #<?php echo $p['id_fab']; ?></small><br>
                                <?php echo htmlspecialchars($p['proyecto_nombre'] ?? 'Sin proyecto'); ?><br>
                                <small><?php echo htmlspecialchars($p['plano_ref'] ?? ''); ?></small>
                            <?php else: ?>
                                <span class="text-muted">N/A</span>
                            <?php endif; ?>
                        </td>
                        <td style="cursor: pointer;" class="td-productos" data-id="<?php echo htmlspecialchars($p['id_prestamo']); ?>">
                            <?php echo count($p['detalles']); ?> producto(s)
                        </td>
                        <td><?php echo htmlspecialchars($p['cantidad_total']); ?></td>
                        <td><?php echo htmlspecialchars($p['cantidad_devuelta']); ?></td>
                        <td><?php echo $pendiente; ?></td>
                        <td id="estatus-<?php echo htmlspecialchars($p['folio']); ?>">
                            <span class="badge badge-<?php echo str_replace('_', '', $p['estatus']); ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $p['estatus'])); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($p['estatus'] === 'solicitado'): ?>
                                <button class="btn btn-primary btn-sm btn-accion btn-entregar" 
                                        data-folio="<?php echo htmlspecialchars($p['folio']); ?>">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                        <path d="M8.5 6.5a.5.5 0 0 0-1 0v3.793L6.354 9.146a.5.5 0 1 0-.708.708l2 2a.5.5 0 0 0 .708 0l2-2a.5.5 0 0 0-.708-.708L8.5 10.293V6.5z"/>
                                    </svg>
                                    Entregar
                                </button>
                                <button class="btn btn-danger btn-sm btn-accion btn-rechazar"
                                        data-folio="<?php echo htmlspecialchars($p['folio']); ?>">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                        <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/>
                                    </svg>
                                    Rechazar
                                </button>
                            <?php elseif ($p['estatus'] === 'prestado'): ?>
                                <button class="btn btn-info btn-sm btn-accion btn-devolver-rapido" 
                                        data-folio="<?php echo htmlspecialchars($p['folio']); ?>">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                        <path d="M1.5 1.5A.5.5 0 0 0 1 2v4.8a2.5 2.5 0 0 0 2.5 2.5h9.793l-3.347 3.346a.5.5 0 0 0 .708.708l4.2-4.2a.5.5 0 0 0 0-.708l-4-4a.5.5 0 0 0-.708.708L13.293 8.3H3.5A1.5 1.5 0 0 1 2 6.8V2a.5.5 0 0 0-.5-.5z"/>
                                    </svg>
                                    Devolución Rápida
                                </button>
                                <button class="btn btn-danger btn-sm btn-accion btn-devolver-detalle" 
                                        data-toggle="modal" data-target="#modalDevolucion" 
                                        data-folio="<?php echo htmlspecialchars($p['folio']); ?>">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                        <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                                        <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/>
                                    </svg>
                                    Devolución con Detalle
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="9" class="text-center">No hay préstamos activos</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal para devolución con detalles -->
<div class="modal fade" id="modalDevolucion" tabindex="-1" role="dialog" aria-labelledby="modalDevolucionLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalDevolucionLabel">Registrar Devolución</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="formDevolucion">
                    <input type="hidden" name="accion" value="devolucion_completa">
                    <input type="hidden" name="folio" id="modalFolio">
                    
                    <div class="alert alert-info">
                        <p><strong>Folio:</strong> <span id="modalFolioInfo"></span></p>
                        <p><strong>Proyecto:</strong> <span id="modalProyectoInfo"></span></p>
                        <p><strong>Orden Fabricación:</strong> <span id="modalOrdenInfo"></span></p>
                    </div>
                    
                    <h5>Productos a devolver:</h5>
                    <div class="product-details" id="modalProductosInfo">
                        <!-- Productos se cargarán aquí -->
                    </div>
                    
                    <div class="form-group mt-3">
                        <label for="modalNotas">Observaciones:</label>
                        <textarea class="form-control" id="modalNotas" name="notas" rows="3" placeholder="Detalla el estado de los productos devueltos"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnConfirmarDevolucion">Registrar Devolución</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para ver detalles de productos -->
<div class="modal fade" id="productosModal" tabindex="-1" role="dialog" aria-labelledby="productosModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="productosModalLabel">Detalle de Productos - Préstamo <span id="modalFolioTitle"></span></h5>
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
                            <th>Cantidad Prestada</th>
                            <th>Cantidad Devuelta</th>
                            <th>Pendiente</th>
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

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function() {
    // Modal de devolver pero con observaciones
    $('#modalDevolucion').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var folio = button.data('folio');
        var row = button.closest('tr');
        var solicitante = row.find('td:nth-child(3)').text();
        var fecha = row.find('td:nth-child(2)').text();
        var pendiente = row.find('td:nth-child(7)').text();
        // Obtener datos de la fila
        var proyectoInfo = row.find('td:nth-child(4)').text().trim();
        var ordenInfo = row.find('td:nth-child(4) small.text-muted').text();
        
        var modal = $(this);
        modal.find('#modalFolio').val(folio);
        modal.find('#modalFolioInfo').text(folio);
        modal.find('#modalProyectoInfo').text(proyectoInfo.replace(ordenInfo, '').trim());
        modal.find('#modalOrdenInfo').text(ordenInfo);
        modal.find('#modalSolicitanteInfo').text(solicitante);
        modal.find('#modalFechaInfo').text(fecha);
        modal.find('#modalNotas').val('');
        
        // Cargar productos (simulado - en producción deberías obtener los datos reales)
        modal.find('#modalProductosInfo').html('<p>Cargando productos...</p>');
        
        // Simular carga de productos (en producción harías una petición AJAX o usarías datos pre-cargados)
        setTimeout(function() {
            var productosHtml = '';
            // Esto es un ejemplo - en producción deberías obtener los datos reales del préstamo
            productosHtml += '<div class="product-row">';
            productosHtml += '<strong>Producto 1</strong> - Descripción del producto';
            productosHtml += '<span class="badge badge-secondary float-right">Cantidad: ' + pendiente + '</span>';
            productosHtml += '</div>';
            
            modal.find('#modalProductosInfo').html(productosHtml);
        }, 500);
    });
    
    // Procesar devolución con detalles
    $('#btnConfirmarDevolucion').click(function() {
        var formData = $('#formDevolucion').serialize();
        
        $.ajax({
            url: 'devolucion_prestamo.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showAlert('success', response.message);
                    // Actualizar la fila en la tabla
                    var row = $('tr[data-folio="' + response.folio + '"]');
                    row.find('td:nth-child(6)').text(row.find('td:nth-child(5)').text()); // Devuelto = Prestado
                    row.find('td:nth-child(7)').text('0'); // Pendiente = 0
                    row.find('td:nth-child(8)').html('<span class="badge badge-devuelto">Devuelto</span>');
                    row.find('td:nth-child(9)').html(''); // Limpiar acciones
                    $('#modalDevolucion').modal('hide');
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    showAlert('danger', response.message);
                }
            },
            error: function() {
                showAlert('danger', 'Error al comunicarse con el servidor');
            }
        });
    });
    
    // Procesar devolución rápida
    $('.btn-devolver-rapido').click(function() {
        var folio = $(this).data('folio');
        var row = $(this).closest('tr');
        
        if (confirm('¿Estás seguro de registrar una devolución rápida sin observaciones para el folio ' + folio + '?')) {
            $.ajax({
                url: 'devolucion_prestamo.php',
                type: 'POST',
                data: {
                    accion: 'devolucion_rapida',
                    folio: folio
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showAlert('success', response.message);
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        showAlert('danger', response.message);
                    }
                },
                error: function() {
                    showAlert('danger', 'Error al comunicarse con el servidor');
                }
            });
        }
    });
    
    // Procesar entrega de préstamo (cambiar de solicitado a prestado)
    $('.btn-entregar').click(function() {
        var folio = $(this).data('folio');
        
        if (confirm('¿Confirmas la entrega del préstamo ' + folio + '?')) {
            $.ajax({
                url: 'procesar_entrega_prestamo.php', // Necesitarás crear este archivo
                type: 'POST',
                data: { folio: folio },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showAlert('success', response.message);
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        showAlert('danger', response.message);
                    }
                },
                error: function() {
                    showAlert('danger', 'Error al comunicarse con el servidor');
                }
            });
        }
    });
    
    function showAlert(type, message) {
        var alertHtml = '<div class="alert alert-' + type + ' alert-dismissible fade show" role="alert">' +
                        message +
                        '<button type="button" class="close" data-dismiss="alert" aria-label="Close">' +
                        '<span aria-hidden="true">&times;</span>' +
                        '</button></div>';
        
        $('#alert-container').html(alertHtml);
        
        setTimeout(function() {
            $('.alert').alert('close');
        }, 10000);
    }
});

// Procesar rechazo de préstamo (cambiar de solicitado a rechazado)
$(document).on('click', '.btn-rechazar', function() {
    var folio = $(this).data('folio');
    var row = $(this).closest('tr');

    if (confirm('¿Confirmas el rechazo del préstamo ' + folio + '?')) {
        $.ajax({
            url: 'procesar_entrega_prestamo.php',
            type: 'POST',
            data: { folio: folio, accion: 'rechazar' },
            dataType: 'json',
            success: function(response) {
                console.log('Respuesta del servidor:', response);
                if (response.success) {
                    row.find('td:nth-child(9)').html('<span class="badge badge-danger">Rechazado</span>');
                    row.find('.btn-accion').hide();
                    showAlert('success', response.message);
                } else {
                    showAlert('danger', response.message);
                }
            },
            error: function() {
                console.error('Error en la petición:', status, error);
                showAlert('danger', 'Error al comunicarse con el servidor');
            }
        });
    }
});

// Mostrar modal con detalles de productos
$(document).on('click', '.td-productos', function() {
    var id_prestamo = $(this).data('id');
    var row = $(this).closest('tr');
    var folio = row.find('td:first').text();
    
    // Configurar título del modal
    $('#modalFolioTitle').text(folio);
    
    // Obtener detalles del préstamo (ya están en la página)
    var detalles = [];
    <?php foreach ($prestamos as $p): ?>
        if (<?php echo $p['id_prestamo']; ?> == id_prestamo) {
            detalles = <?php echo json_encode($p['detalles']); ?>;
        }
    <?php endforeach; ?>
    
    // Limpiar y llenar la tabla de productos
    $('#modalProductosBody').empty();
    
    if (detalles.length > 0) {
        detalles.forEach(function(producto) {
            var pendiente = producto.cantidad - (producto.cantidad_entre || 0);
            $('#modalProductosBody').append(`
                <tr>
                    <td>${producto.codigo}</td>
                    <td>${producto.descripcion}</td>
                    <td>${producto.categoria}</td>
                    <td>${producto.cantidad}</td>
                    <td>${producto.cantidad_entre || 0}</td>
                    <td>${pendiente}</td>
                </tr>
            `);
        });
    } else {
        $('#modalProductosBody').append('<tr><td colspan="6" class="text-center">No se encontraron productos</td></tr>');
    }
    
    // Mostrar el modal
    $('#productosModal').modal('show');
});
</script>
</body>
</html>