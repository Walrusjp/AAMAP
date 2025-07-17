<?php
session_start();
if (!isset($_SESSION['username']) || !isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit();
}

require 'C:/xampp/htdocs/db_connect.php';

// Manejar la solicitud POST (envío desde el prompt)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_oc = $_POST['id_oc'] ?? null;
    $id_usuario = $_SESSION['user_id'];
    $observaciones = $_POST['observaciones'] ?? '';

    // Validaciones básicas
    if (!$id_oc) {
        die(json_encode(['error' => 'ID de orden no válido']));
    }

    // Verificar si ya está en almacén
    $stmt_check = $conn->prepare("SELECT 1 FROM logs_estatus_oc WHERE id_oc = ? AND estatus = 'almacen'");
    $stmt_check->bind_param("i", $id_oc);
    $stmt_check->execute();
    $stmt_check->store_result();

    if ($stmt_check->num_rows > 0) {
        die(json_encode(['error' => 'Esta orden ya fue marcada como recibida en almacén']));
    }
    $stmt_check->close();

    // Obtener información de la OC para el destino (OF o descripción)
    $stmt_oc_info = $conn->prepare("SELECT id_fab, descripcion_destino FROM ordenes_compra WHERE id_oc = ?");
    $stmt_oc_info->bind_param("i", $id_oc);
    $stmt_oc_info->execute();
    $oc_info = $stmt_oc_info->get_result()->fetch_assoc();
    $stmt_oc_info->close();

    // Iniciar transacción
    $conn->begin_transaction();

    try {
        // 1. Obtener todos los items activos de la orden
        $stmt_items = $conn->prepare("
            SELECT id_alm, cantidad, recibido 
            FROM detalle_orden_compra 
            WHERE id_oc = ? AND activo = 1
        ");
        $stmt_items->bind_param("i", $id_oc);
        $stmt_items->execute();
        $items = $stmt_items->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt_items->close();

        // 2. Actualizar todos los items activos de la orden como completamente recibidos
        $stmt_update = $conn->prepare("
            UPDATE detalle_orden_compra 
            SET recibido = cantidad 
            WHERE id_oc = ? AND activo = 1
        ");
        $stmt_update->bind_param("i", $id_oc);
        $stmt_update->execute();
        $stmt_update->close();

        // 3. Registrar movimientos de almacén para cada artículo
        $stmt_movimiento = $conn->prepare("
            INSERT INTO movimientos_almacen (
                id_alm, 
                tipo_mov, 
                cantidad, 
                fecha_mov, 
                id_oc, 
                id_fab, 
                no_of, 
                id_usuario, 
                notas,
                id_pr
            ) VALUES (?, 'entrada', ?, NOW(), ?, ?, ?, ?, ?, ?)
        ");

        // Obtener el proveedor de la OC
        $stmt_prov = $conn->prepare("SELECT id_pr FROM ordenes_compra WHERE id_oc = ?");
        $stmt_prov->bind_param("i", $id_oc);
        $stmt_prov->execute();
        $id_pr = $stmt_prov->get_result()->fetch_assoc()['id_pr'];
        $stmt_prov->close();

        foreach ($items as $item) {
            $cantidad_recibida = $item['cantidad'] - ($item['recibido'] ?? 0);
            if ($cantidad_recibida <= 0) continue; // No registrar movimientos para cantidades 0 o negativas

            $stmt_movimiento->bind_param(
                "isiisssi",
                $item['id_alm'],                  // id_alm
                $cantidad_recibida,               // cantidad
                $id_oc,                           // id_oc
                $oc_info['id_fab'],               // id_fab (puede ser null)
                $oc_info['descripcion_destino'], // no_of (usa descripcion_destino si no hay OF)
                $id_usuario,                     // id_usuario
                $observaciones,                  // notas
                $id_pr                           // id_pr (proveedor)
            );
            $stmt_movimiento->execute();
        }
        $stmt_movimiento->close();

        // 4. Registrar el cambio de estatus
        $stmt = $conn->prepare("
            INSERT INTO logs_estatus_oc (
                id_oc, 
                estatus, 
                id_usuario, 
                observaciones, 
                fecha_cambio
            ) VALUES (?, 'almacen', ?, ?, NOW())
        ");
        $stmt->bind_param("iss", $id_oc, $id_usuario, $observaciones);
        $stmt->execute();
        $stmt->close();

        $conn->commit();
        echo json_encode(['success' => true, 'estatus' => 'almacen']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['error' => 'Error al completar la recepción: ' . $e->getMessage()]);
    }
    exit();
}

// Manejar la solicitud GET (mostrar prompt)
$id_oc = intval($_GET['id'] ?? 0);
if ($id_oc <= 0) {
    die("<script>alert('ID de orden no válido'); window.history.back();</script>");
}

// Verificar permisos (solo admin y almacén)
if (!in_array($_SESSION['role'], ['admin', 'almacen'])) {
    die("<script>alert('No tienes permisos para completar recepciones'); window.history.back();</script>");
}
?>
<script>
// Mostrar prompt y procesar
var observaciones = prompt('Ingrese observaciones de la recepción (opcional):');
    
if (observaciones === null) {
    // Usuario canceló
    window.history.back();
} else {
    // Enviar datos al servidor
    fetch('completar_recepcion.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'id_oc=<?php echo $id_oc; ?>&observaciones=' + encodeURIComponent(observaciones)
    })
    .then(response => response.json())
    .then(data => {
        if (data.error) {
            alert(data.error);
            window.history.back();
        } else {
            alert('Orden marcada como RECIBIDA EN ALMACÉN');
            window.location.href = 'ver_ordenes_compra.php?msg=success';
        }
    })
    .catch(error => {
        alert('Error en la comunicación con el servidor');
        console.error(error);
        window.history.back();
    });
}
</script>
<?php
$conn->close();
?>