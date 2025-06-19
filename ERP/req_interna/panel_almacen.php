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
        return in_array($user['role'], ['admin', 'admin']); // Ajusta según tus roles
    }
    return false;
}

// Procesar entrega de solicitud
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['folio'])) {
    $folio = $conn->real_escape_string($_POST['folio']);
    $id_usuario_almacen = $_SESSION['user_id'];
    
    // Buscar solicitud pendiente
    $query = "SELECT * FROM solicitudes_internas 
              WHERE folio = '$folio' AND estatus = 'pendiente'";
    $result = $conn->query($query);
    
    if ($result->num_rows > 0) {
        $solicitud = $result->fetch_assoc();
        
        // Verificar stock nuevamente (por si acaso cambió)
        $stock_query = "SELECT existencia FROM inventario_almacen WHERE id_alm = " . $solicitud['id_alm'];
        $stock_result = $conn->query($stock_query);
        $stock_data = $stock_result->fetch_assoc();
        
        if ($stock_data['existencia'] >= $solicitud['cantidad']) {
            // Iniciar transacción
            $conn->begin_transaction();
            
            try {
                // 1. Actualizar solicitud como entregada
                $update_solicitud = "UPDATE solicitudes_internas 
                                     SET estatus = 'entregada', 
                                         id_usuario_almacen = $id_usuario_almacen,
                                         fecha_entrega = NOW()
                                     WHERE id_solicitud = " . $solicitud['id_solicitud'];
                $conn->query($update_solicitud);
                
                // 2. Registrar movimiento de salida
                $insert_movimiento = "INSERT INTO movimientos_almacen 
                                     (id_alm, tipo_mov, cantidad, id_usuario, notas)
                                     VALUES 
                                     (" . $solicitud['id_alm'] . ", 'salida', " . $solicitud['cantidad'] . ", 
                                     $id_usuario_almacen, 'Salida por solicitud interna: " . $solicitud['folio'] . "')";
                $conn->query($insert_movimiento);
                
                // 3. Actualizar inventario
                $update_inventario = "UPDATE inventario_almacen 
                                     SET existencia = existencia - " . $solicitud['cantidad'] . "
                                     WHERE id_alm = " . $solicitud['id_alm'];
                $conn->query($update_inventario);
                
                // Confirmar transacción
                $conn->commit();
                $_SESSION['success'] = "Solicitud entregada correctamente";
            } catch (Exception $e) {
                $conn->rollback();
                $_SESSION['error'] = "Error al procesar la entrega: " . $e->getMessage();
            }
        } else {
            $_SESSION['error'] = "No hay suficiente stock para entregar esta solicitud";
        }
    } else {
        $_SESSION['error'] = "No se encontró una solicitud pendiente con ese folio";
    }
    
    header("Location: panel_almacen.php");
    exit();
}

// Obtener solicitudes pendientes
$query = "SELECT si.*, ia.codigo, ia.descripcion, u.nombre as solicitante_nombre
          FROM solicitudes_internas si
          JOIN inventario_almacen ia ON si.id_alm = ia.id_alm
          JOIN users u ON si.id_usuario_solicitante = u.id
          WHERE si.estatus = 'pendiente'
          ORDER BY si.fecha_solicitud DESC";
$solicitudes = $conn->query($query)->fetch_all(MYSQLI_ASSOC);

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
                <a href="panel_almacen.php" class="btn btn-warning chompa" style="border: 3px solid gray;">Solicitudes</a>
                <a href="devolucion_prestamo.php" class="btn btn-warning chompa">Prestámos</a>
                <a href="req_interna.php" class="btn btn-info chompa">Nueva Requisición</a>
                <a href="prestamo_almacen.php" class="btn btn-info chompa">Nuevo prestámo</a>
                <a href="/ERP/all_projects.php" class="btn btn-secondary chompa">Regresar</a>
            </div>
        </div>
    </div>
</div>

<div class="table-container">
    <h2 class="text-center mb-4">Solicitudes Internas Pendientes</h2>
    
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>
    
    <!-- Formulario para buscar por folio -->
    <div class="search-box">
        <form method="POST" class="form-inline">
            <div class="input-group w-100">
                <input type="text" name="folio" class="form-control" placeholder="Ingrese folio para entregar" required>
                <div class="input-group-append">
                    <button type="submit" class="btn btn-primary">Entregar</button>
                </div>
            </div>
        </form>
    </div>
    
    <table class="table table-striped table-bordered">
        <thead>
            <tr>
                <th>Folio</th>
                <th>Fecha Solicitud</th>
                <th>Solicitante</th>
                <th>Producto</th>
                <th>Cantidad</th>
                <th>Razón</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($solicitudes)): ?>
                <?php foreach ($solicitudes as $sol): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($sol['folio']); ?></td>
                        <td><?php echo htmlspecialchars($sol['fecha_solicitud']); ?></td>
                        <td><?php echo htmlspecialchars($sol['solicitante_nombre']); ?></td>
                        <td><?php echo htmlspecialchars($sol['codigo'] . ' - ' . $sol['descripcion']); ?></td>
                        <td><?php echo htmlspecialchars($sol['cantidad']); ?></td>
                        <td><?php echo htmlspecialchars($sol['razon']); ?></td>
                        <td>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="folio" value="<?php echo htmlspecialchars($sol['folio']); ?>">
                                <button type="submit" class="btn btn-success btn-sm">Entregar</button>
                            </form>
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

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>