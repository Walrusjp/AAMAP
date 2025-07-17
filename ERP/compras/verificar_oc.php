<?php
session_start();
if (!isset($_SESSION['username']) || !isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit();
}

require 'C:/xampp/htdocs/db_connect.php';
require 'C:/xampp/htdocs/role.php';
require 'C:/xampp/htdocs/ERP/send_email_oc.php';

$id_oc = $_GET['id'] ?? null;
if (!$id_oc) {
    die("<script>alert('ID de orden no proporcionado'); window.history.back();</script>");
}

// Obtener información de la orden
$oc = $conn->query("
    SELECT oc.*, 
           p.empresa AS proveedor_nombre,
           pr.nombre AS proyecto_nombre,
           pr.cod_fab,
           of.id_fab,
           u.nombre AS solicitante_nombre
    FROM ordenes_compra oc
    LEFT JOIN proveedores p ON oc.id_pr = p.id_pr
    LEFT JOIN orden_fab of ON oc.id_fab = of.id_fab
    LEFT JOIN proyectos pr ON of.id_proyecto = pr.cod_fab
    LEFT JOIN users u ON oc.solicitante = u.id
    WHERE oc.id_oc = $id_oc
")->fetch_assoc();
// Determinar texto para Destino/OF
$destino_of = 'N/A';
if (!empty($oc['id_fab'])) {
    $destino_of = 'OF-' . $oc['id_fab'];
    if (!empty($oc['proyecto_nombre'])) {
        $destino_of .= ' (' . $oc['proyecto_nombre'] . ')';
    }
} elseif (!empty($oc['descripcion_destino'])) {
    $destino_of = $oc['descripcion_destino'];
}

// Obtener detalles de la orden
$detalles = $conn->query("
    SELECT doc.*, ia.codigo, ia.descripcion, ia.existencia 
    FROM detalle_orden_compra doc
    JOIN inventario_almacen ia ON doc.id_alm = ia.id_alm
    WHERE doc.id_oc = $id_oc AND doc.activo = 1
")->fetch_all(MYSQLI_ASSOC);

// Procesar el formulario de verificación
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_usuario = $_SESSION['user_id'];
    $accion = $_POST['accion'] ?? null;
    $observaciones = $_POST['observaciones'] ?? '';
    $items_aprobados = $_POST['items_aprobados'] ?? [];
    
    // Validaciones (se mantienen igual)
    if (!$accion) {
        die("<script>alert('Acción no especificada'); window.history.back();</script>");
    }
    
    if ($accion == 'rechazar' && empty(trim($observaciones))) {
        die("<script>alert('Las observaciones son obligatorias para rechazos'); window.history.back();</script>");
    }
    
    // Iniciar transacción
    $conn->begin_transaction();
    
    try {
        // Actualizar estado de los items (marcar como inactivos los no aprobados)
        foreach ($detalles as $detalle) {
            $activo = in_array($detalle['id_detalle'], $items_aprobados) ? 1 : 0;
            $conn->query("UPDATE detalle_orden_compra SET activo = $activo WHERE id_detalle = {$detalle['id_detalle']}");
        }
        
        // Registrar el cambio de estatus
        $nuevo_estatus = ($accion == 'aprobar') ? 'autorizado' : 'rechazado';
        $stmt = $conn->prepare("INSERT INTO logs_estatus_oc (id_oc, estatus, id_usuario, observaciones, fecha_cambio) VALUES (?, ?, ?, ?, NOW())");
        $stmt->bind_param("isis", $id_oc, $nuevo_estatus, $id_usuario, $observaciones);
        $stmt->execute();
        
        // --- BLOQUE DE ENVÍO DE CORREO (SOLO PARA APROBACIONES) ---
        if ($accion == 'aprobar') {
            
            // Obtener información completa de la orden con items activos
            $oc_info = $conn->query("
                SELECT oc.*, p.empresa AS proveedor_nombre, 
                       pr.nombre AS proyecto_nombre, u.nombre AS solicitante_nombre
                FROM ordenes_compra oc
                LEFT JOIN proveedores p ON oc.id_pr = p.id_pr
                LEFT JOIN proyectos pr ON oc.id_fab = pr.cod_fab
                LEFT JOIN users u ON oc.solicitante = u.id
                WHERE oc.id_oc = $id_oc
            ")->fetch_assoc();
            
            // Obtener solo los items aprobados (activos)
            $items_aprobados = $conn->query("
                SELECT doc.*, ia.descripcion, ia.codigo
                FROM detalle_orden_compra doc
                JOIN inventario_almacen ia ON doc.id_alm = ia.id_alm
                WHERE doc.id_oc = $id_oc AND doc.activo = 1
            ")->fetch_all(MYSQLI_ASSOC);
            
            // Calcular totales basados en items aprobados
            $subtotal = array_reduce($items_aprobados, function($total, $item) {
                return $total + ($item['cantidad'] * $item['precio_unitario']);
            }, 0);
            
            $iva = $subtotal * 0.16;
            $total = $subtotal + $iva;
            
            // Determinar OF/Destino
            $of_destino = '';
            if (!empty($oc_info['id_fab'])) {
                $of_destino = 'OF-' . $oc_info['id_fab'];
                if (!empty($oc_info['proyecto_nombre'])) {
                    $of_destino .= ' (' . $oc_info['proyecto_nombre'] . ')';
                }
            } elseif (!empty($oc_info['descripcion_destino'])) {
                $of_destino = $oc_info['descripcion_destino'];
            }
            
            // Construir cuerpo del correo
            $subject = "Orden de Compra APROBADA: " . $oc_info['folio'];
            
            $body = "<h3>Orden de Compra Aprobada: {$oc_info['folio']}</h3>";
            $body .= "<p><strong>Proveedor:</strong> {$oc_info['proveedor_nombre']}</p>";
            if (!empty($of_destino)) {
                $body .= "<p><strong>OF/Destino:</strong> {$of_destino}</p>";
            }
            $body .= "<p><strong>Fecha requerida:</strong> {$oc_info['fecha_requerida']}</p>";
            $body .= "<p><strong>Solicitante:</strong> {$oc_info['solicitante_nombre']}</p>";
            $body .= "<p><strong>Aprobada por:</strong> {$_SESSION['username']}</p>";
            
            if (!empty($items_aprobados)) {
                $body .= "<h4>Artículos aprobados:</h4>";
                $body .= "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
                $body .= "<tr><th>#</th><th>Código</th><th>Descripción</th><th>Cantidad</th><th>P. Unitario</th><th>Subtotal</th></tr>";
                
                foreach ($items_aprobados as $i => $item) {
                    $subtotal_item = $item['cantidad'] * $item['precio_unitario'];
                    $body .= "<tr>
                                <td>".($i+1)."</td>
                                <td>{$item['codigo']}</td>
                                <td>{$item['descripcion']}</td>
                                <td>{$item['cantidad']}</td>
                                <td>$".number_format($item['precio_unitario'], 2)."</td>
                                <td>$".number_format($subtotal_item, 2)."</td>
                              </tr>";
                }
                
                $body .= "</table>";
            }
            
            $body .= "<div style='margin-top: 15px; padding: 10px; background-color: #f5f5f5; border: 1px solid #ddd;'>
                        <p><strong>Subtotal:</strong> $".number_format($subtotal, 2)."</p>
                        <p><strong>IVA (16%):</strong> $".number_format($iva, 2)."</p>
                        <p><strong>Total:</strong> $".number_format($total, 2)."</p>
                      </div>";
            
            if (!empty($observaciones)) {
                $body .= "<p><strong>Observaciones:</strong> {$observaciones}</p>";
            }
            
            $body .= "<p>Favor de proceder con los siguientes pasos del proceso.</p>";
            
            // Enviar correo
            send_email_order('valdito212002@gmail.com', $subject, $body);
        }
        // --- FIN DEL BLOQUE DE ENVÍO DE CORREO ---
        
        $conn->commit();
        
        echo "<script>
                alert('Orden marcada como $nuevo_estatus');
                window.location.href = 'ver_ordenes_compra.php?msg=success';
              </script>";
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        die("<script>alert('Error al procesar la orden: " . addslashes($e->getMessage()) . "'); window.history.back();</script>");
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Verificar Orden de Compra <?php echo $oc['folio']; ?></title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="/ERP/stprojects.css">
    <link rel="icon" href="/assets/logo.ico">
</head>
<body style="background-color: rgba(211, 211, 211, 0.4) !important;">
<div class="container mt-4">
    <h1>Verificar Orden de Compra: <?php echo htmlspecialchars($oc['folio']); ?></h1>
    <a href="ver_ordenes_compra.php" class="btn btn-secondary mb-3">Regresar</a>
    
    <div class="card mb-4">
        <div class="card-header">
            <h4>Información de la Orden</h4>
        </div>
        <div class="card-body">
            <p><strong>Proveedor:</strong> <?php echo !empty($oc['proveedor_nombre']) ? htmlspecialchars($oc['proveedor_nombre']) : 'N/A'; ?></p>
            <p><strong>Destino/OF:</strong> <?php echo htmlspecialchars($destino_of); ?></p>
            <p><strong>Fecha requerida:</strong> <?php echo htmlspecialchars($oc['fecha_requerida']); ?></p>
            <p><strong>Solicitante:</strong> <?php echo !empty($oc['solicitante_nombre']) ? htmlspecialchars($oc['solicitante_nombre']) : 'N/A'; ?></p>
            <p><strong>Total:</strong> $<?php echo number_format($oc['total'], 2); ?></p>
        </div>
    </div>
    
    <form method="POST" action="verificar_oc.php?id=<?php echo $id_oc; ?>">
        <div class="card">
            <div class="card-header">
                <h4>Artículos de la Orden</h4>
                <p class="mb-0">Marque los artículos que desea aprobar:</p>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Descripción</th>
                                <th>Cantidad</th>
                                <?php if($role == 'admin'): ?>
                                    <th>Existencia</th>
                                <?php endif; ?>
                                <th>Precio Unitario</th>
                                <th>Subtotal</th>
                                <th>Aprobar</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($detalles as $detalle): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($detalle['codigo']); ?></td>
                                    <td><?php echo htmlspecialchars($detalle['descripcion']); ?></td>
                                    <td><?php echo $detalle['cantidad']; ?></td>
                                    <?php if($role == 'admin'): ?>
                                        <td><?php echo $detalle['existencia']; ?></td>
                                    <?php endif; ?>
                                    <td>$<?php echo number_format($detalle['precio_unitario'], 2); ?></td>
                                    <td>$<?php echo number_format($detalle['cantidad'] * $detalle['precio_unitario'], 2); ?></td>
                                    <td class="text-center">
                                        <input type="checkbox" name="items_aprobados[]" 
                                            value="<?php echo $detalle['id_detalle']; ?>" checked>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header">
                <h4>Acciones</h4>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label for="observaciones">Observaciones:</label>
                    <textarea class="form-control" id="observaciones" name="observaciones" rows="3"></textarea>
                </div>
                
                <div class="text-right">
                    <button type="submit" name="accion" value="rechazar" class="btn btn-danger mr-2">
                        Rechazar Orden
                    </button>
                    <button type="submit" name="accion" value="aprobar" class="btn btn-success">
                        Autorizar Orden
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>