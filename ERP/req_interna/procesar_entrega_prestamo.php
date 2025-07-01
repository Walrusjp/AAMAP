<?php
session_start();

require 'C:/xampp/htdocs/db_connect.php';
require 'C:/xampp/htdocs/role.php';

// Verificar autenticación y permisos
if (!isset($_SESSION['username'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado']);
    exit();
}


// Configurar cabecera para respuesta JSON
header('Content-Type: application/json');

// Verificar método POST y datos requeridos
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['folio'])) {
    echo json_encode(['success' => false, 'message' => 'Solicitud inválida']);
    exit();
}

$folio = $conn->real_escape_string($_POST['folio']);
$id_usuario = $_SESSION['user_id'];

// Inicializar respuesta
$response = ['success' => false, 'message' => ''];

// Iniciar transacción
$conn->begin_transaction();

// Verificar si se trata de una acción de rechazo
$accion = $_POST['accion'] ?? 'entregar';

if ($accion === 'rechazar') {
    try {
        $conn->begin_transaction(); // Iniciar transacción
        
        $check_query = "SELECT id_prestamo FROM prestamos_almacen 
                       WHERE folio = '$folio' AND estatus = 'solicitado' 
                       FOR UPDATE";
        $check_result = $conn->query($check_query);

        if ($check_result->num_rows === 0) {
            throw new Exception('El préstamo no existe o no está en estado solicitado');
        }

        $prestamo = $check_result->fetch_assoc();
        $id_prestamo = $prestamo['id_prestamo'];

        $rechazar_query = "UPDATE prestamos_almacen 
                          SET estatus = 'rechazado',
                              id_usuario_almacen = $id_usuario
                          WHERE id_prestamo = $id_prestamo";
        
        if (!$conn->query($rechazar_query)) {
            throw new Exception('Error al actualizar el estado del préstamo');
        }

        // ¡FALTABA ESTA LÍNEA CRÍTICA!
        $conn->commit(); // Confirmar la transacción

        echo json_encode([
            'success' => true, 
            'message' => 'Préstamo rechazado correctamente',
            'folio' => $folio
        ]);
    } catch (Exception $e) {
        $conn->rollback(); // Revertir en caso de error
        echo json_encode([
            'success' => false, 
            'message' => $e->getMessage()
        ]);
    }
    exit();
}


try {
    // 1. Verificar que el préstamo existe y está en estado "solicitado"
    $check_query = "SELECT id_prestamo, id_fab FROM prestamos_almacen 
                WHERE folio = '$folio' AND estatus = 'solicitado' 
                FOR UPDATE";
    $check_result = $conn->query($check_query);
    
    if ($check_result->num_rows === 0) {
        throw new Exception("No se encontró el préstamo o no está en estado 'solicitado'");
    }
    
    $prestamo = $check_result->fetch_assoc();
    $id_prestamo = $prestamo['id_prestamo'];
    $id_fab = $prestamo['id_fab'];
    
    // 2. Obtener detalles del préstamo para verificar stock
    $detalles_query = "SELECT sd.id_alm, sd.cantidad, ia.codigo, ia.descripcion, ia.existencia
                      FROM solicitudes_detalle sd
                      JOIN inventario_almacen ia ON sd.id_alm = ia.id_alm
                      WHERE sd.id_prestamo = $id_prestamo";
    $detalles_result = $conn->query($detalles_query);
    $detalles = $detalles_result->fetch_all(MYSQLI_ASSOC);
    
    // 3. Verificar stock para todos los productos
    foreach ($detalles as $detalle) {
        if ($detalle['existencia'] < $detalle['cantidad']) {
            throw new Exception("No hay suficiente stock para el producto: " . 
                              $detalle['codigo'] . " - " . $detalle['descripcion'] . 
                              " (Stock: " . $detalle['existencia'] . ", Requerido: " . $detalle['cantidad'] . ")");
        }
    }
    
    // 4. Actualizar estado del préstamo a "prestado"
    $update_prestamo = "UPDATE prestamos_almacen 
                       SET estatus = 'prestado',
                           fecha_entrega = NOW(),
                           id_usuario_almacen = $id_usuario
                       WHERE id_prestamo = $id_prestamo";
    $conn->query($update_prestamo);
    
    // 5. Registrar movimientos de salida y actualizar inventario
    foreach ($detalles as $detalle) {
        // Registrar movimiento de salida
        $movimiento_query = "INSERT INTO movimientos_almacen 
                    (id_alm, tipo_mov, cantidad, id_fab, id_usuario, notas)
                    VALUES 
                    (" . $detalle['id_alm'] . ", 'salida', " . $detalle['cantidad'] . ", 
                    " . ($prestamo['id_fab'] ?: 'NULL') . ", 
                    $id_usuario, 'Préstamo entregado: $folio')";
        $conn->query($movimiento_query);
        
        // Actualizar inventario
        $inventario_query = "UPDATE inventario_almacen 
                            SET existencia = existencia - " . $detalle['cantidad'] . "
                            WHERE id_alm = " . $detalle['id_alm'];
        $conn->query($inventario_query);
        
        // Actualizar cantidad entregada en el detalle
        $update_detalle = "UPDATE solicitudes_detalle 
                          SET cantidad_entregada = " . $detalle['cantidad'] . "
                          WHERE id_prestamo = $id_prestamo AND id_alm = " . $detalle['id_alm'];
        $conn->query($update_detalle);
    }
    
    // Confirmar transacción
    $conn->commit();
    
    $response['success'] = true;
    $response['message'] = "Préstamo entregado correctamente";
    $response['folio'] = $folio;
} catch (Exception $e) {
    // Revertir transacción en caso de error
    $conn->rollback();
    $response['message'] = "Error al procesar la entrega: " . $e->getMessage();
}

// Cerrar conexión y devolver respuesta
$conn->close();
echo json_encode($response);
?>