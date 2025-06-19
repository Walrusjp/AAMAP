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
        return in_array($user['role'], ['admin', 'admin']); // Ajusta según tus roles
    }
    return false;
}

// Procesar devolución (ahora via AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'devolver') {
    header('Content-Type: application/json');
    
    $folio = $conn->real_escape_string($_POST['folio']);
    $notas = $conn->real_escape_string($_POST['notas'] ?? '');
    $id_usuario = $_SESSION['user_id'];
    
    $response = ['success' => false, 'message' => ''];
    
    // Obtener préstamo
    $prestamo_query = "SELECT * FROM prestamos_almacen WHERE folio = '$folio' AND estatus != 'devuelto'";
    $prestamo_result = $conn->query($prestamo_query);
    
    if ($prestamo_result->num_rows > 0) {
        $prestamo = $prestamo_result->fetch_assoc();
        $cantidad_a_devolver = $prestamo['cantidad'] - $prestamo['cantidad_devuelta'];
        
        // Iniciar transacción
        $conn->begin_transaction();
        
        try {
            // 1. Actualizar préstamo como devuelto completamente
            $update_query = "UPDATE prestamos_almacen 
                           SET cantidad_devuelta = cantidad,
                               estatus = 'devuelto',
                               fecha_devolucion = NOW(),
                               observaciones = '$notas',
                               id_usuario_almacen = $id_usuario
                           WHERE id_prestamo = " . $prestamo['id_prestamo'];
            $conn->query($update_query);
            
            // 2. Registrar movimiento de entrada
            $movimiento_query = "INSERT INTO movimientos_almacen 
                               (id_alm, tipo_mov, cantidad, id_usuario, notas)
                               VALUES 
                               (" . $prestamo['id_alm'] . ", 'entrada', $cantidad_a_devolver, 
                               $id_usuario, 'Devolución completa préstamo: $folio')";
            $conn->query($movimiento_query);
            
            // 3. Actualizar inventario
            $inventario_query = "UPDATE inventario_almacen 
                               SET existencia = existencia + $cantidad_a_devolver
                               WHERE id_alm = " . $prestamo['id_alm'];
            $conn->query($inventario_query);
            
            $conn->commit();
            $response['success'] = true;
            $response['message'] = "Devolución completa registrada correctamente";
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

// Obtener préstamos activos
$query = "SELECT p.*, ia.codigo, ia.descripcion, u.nombre as solicitante 
          FROM prestamos_almacen p
          JOIN inventario_almacen ia ON p.id_alm = ia.id_alm
          JOIN users u ON p.id_usuario_solicitante = u.id
          WHERE p.estatus != 'devuelto'
          ORDER BY p.fecha_prestamo DESC";
$prestamos = $conn->query($query)->fetch_all(MYSQLI_ASSOC);

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
        .alert-info p {
            margin-bottom: 0.5rem;
        }

        .alert-info strong {
            min-width: 120px;
            display: inline-block;
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
                <a href="devolucion_prestamo.php" class="btn btn-warning chompa" style="border: 3px solid gray;">Prestámos</a>
                <a href="req_interna.php" class="btn btn-info chompa">Nueva Requisición</a>
                <a href="prestamo_almacen.php" class="btn btn-info chompa">Nuevo prestámo</a>
                <a href="/ERP/all_projects.php" class="btn btn-secondary chompa">Regresar</a>
            </div>
        </div>
    </div>
</div>

<div class="table-container">
    <h2 class="text-center mb-4">Gestión de Devoluciones</h2>
    
    <div id="alert-container"></div>
    
    <h4>Préstamos Activos</h4>
    <table class="table table-striped table-bordered" id="tabla-prestamos">
        <thead>
            <tr>
                <th>Folio</th>
                <th>Fecha Préstamo</th>
                <th>Solicitante</th>
                <th>Producto</th>
                <th>Cantidad Prestada</th>
                <th>Devuelto</th>
                <th>Pendiente</th>
                <th>Estatus</th>
                <th>Fecha Estimada</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($prestamos)): ?>
                <?php foreach ($prestamos as $p): 
                    $pendiente = $p['cantidad'] - $p['cantidad_devuelta'];
                ?>
                    <tr data-folio="<?php echo htmlspecialchars($p['folio']); ?>">
                        <td><?php echo htmlspecialchars($p['folio']); ?></td>
                        <td><?php echo htmlspecialchars($p['fecha_prestamo']); ?></td>
                        <td><?php echo htmlspecialchars($p['solicitante']); ?></td>
                        <td><?php echo htmlspecialchars($p['codigo'] . ' - ' . $p['descripcion']); ?></td>
                        <td><?php echo htmlspecialchars($p['cantidad']); ?></td>
                        <td><?php echo htmlspecialchars($p['cantidad_devuelta']); ?></td>
                        <td><?php echo $pendiente; ?></td>
                        <td>
                            <span class="badge badge-<?php echo str_replace('_', '', $p['estatus']); ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $p['estatus'])); ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($p['fecha_devolucion_estimada']); ?></td>
                        <td>
                            <button class="btn btn-primary btn-sm btn-devolver" data-toggle="modal" data-target="#modalDevolucion" 
                                    data-folio="<?php echo htmlspecialchars($p['folio']); ?>">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                    <path d="M1.5 1.5A.5.5 0 0 0 1 2v4.8a2.5 2.5 0 0 0 2.5 2.5h9.793l-3.347 3.346a.5.5 0 0 0 .708.708l4.2-4.2a.5.5 0 0 0 0-.708l-4-4a.5.5 0 0 0-.708.708L13.293 8.3H3.5A1.5 1.5 0 0 1 2 6.8V2a.5.5 0 0 0-.5-.5z"/>
                                </svg>
                                Registrar Devolución
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="10" class="text-center">No hay préstamos activos</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal para devolución -->
<div class="modal fade" id="modalDevolucion" tabindex="-1" role="dialog" aria-labelledby="modalDevolucionLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalDevolucionLabel">Confirmar Devolución Completa</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="formDevolucion">
                    <input type="hidden" name="accion" value="devolver">
                    <input type="hidden" name="folio" id="modalFolio">
                    
                    <div class="alert alert-info">
                        Folio: <strong><span id="modalFolioInfo"></span></strong><br>
                        Cantidad: <strong><span id="modalCantidadInfo"></span></strong> unidades.
                    </div>
                    
                    <div class="form-group">
                        <label for="modalNotas">Notas/Observaciones:</label>
                        <textarea class="form-control" id="modalNotas" name="notas" rows="3" placeholder="Ej: Producto en buen estado..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnConfirmarDevolucion">Confirmar Devolución</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function() {
    // Configurar modal cuando se hace clic en el botón Devolver
    $('#modalDevolucion').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var folio = button.data('folio');
        var row = button.closest('tr');
        var cantidadPendiente = row.find('td:nth-child(7)').text();
        
        var modal = $(this);
        modal.find('#modalFolio').val(folio);
        modal.find('#modalFolioInfo').text(folio); 
        modal.find('#modalCantidadInfo').text(cantidadPendiente);
        modal.find('#modalNotas').val('');
    });
    
    // Procesar devolución
    $('#btnConfirmarDevolucion').click(function() {
        var formData = $('#formDevolucion').serialize();
        
        $.ajax({
            url: 'devolucion_prestamo.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Mostrar mensaje de éxito
                    showAlert('success', response.message);
                    
                    // Actualizar fila en la tabla
                    var row = $('tr[data-folio="' + response.folio + '"]');
                    
                    // Actualizar valores
                    var cantidadTotal = row.find('td:nth-child(5)').text();
                    row.find('td:nth-child(6)').text(cantidadTotal);
                    row.find('td:nth-child(7)').text('0');
                    
                    // Actualizar estatus
                    row.find('td:nth-child(8)').html('<span class="badge badge-devuelto">Devuelto</span>');
                    
                    // Ocultar botón de devolución
                    row.find('.btn-devolver').hide();
                    
                    // Ocultar modal
                    $('#modalDevolucion').modal('hide');
                } else {
                    showAlert('danger', response.message);
                }
            },
            error: function() {
                showAlert('danger', 'Error al comunicarse con el servidor');
            }
        });
    });
    
    function showAlert(type, message) {
        var alertHtml = '<div class="alert alert-' + type + ' alert-dismissible fade show" role="alert">' +
                        message +
                        '<button type="button" class="close" data-dismiss="alert" aria-label="Close">' +
                        '<span aria-hidden="true">&times;</span>' +
                        '</button></div>';
        
        $('#alert-container').html(alertHtml);
        
        // Auto cerrar después de 5 segundos
        setTimeout(function() {
            $('.alert').alert('close');
        }, 10000);
    }
});
</script>
</body>
</html>