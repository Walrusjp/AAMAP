<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: /login.php");
    exit();
}

require 'C:/xampp/htdocs/db_connect.php';
require 'send_email.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mandar_facturar_entrega'])) {
    $proyecto_id = $_POST['proyecto_id'];
    $cod_fab = $_POST['cod_fab'];
    $user_id = $_SESSION['user_id'];
    $cantidades = $_POST['cantidad'];
    $observaciones = $_POST['observaciones'];
    
    // Iniciar transacción
    $conn->begin_transaction();
    
    try {
        // 1. Registrar la entrega
        $sqlEntrega = "INSERT INTO entregas_parciales (id_proyecto, id_usuario) VALUES (?, ?)";
        $stmt = $conn->prepare($sqlEntrega);
        $stmt->bind_param("si", $cod_fab, $user_id);
        $stmt->execute();
        $id_entrega = $conn->insert_id;
        $stmt->close();
        
        // 2. Registrar las partidas entregadas y actualizar cantidades
        foreach ($cantidades as $partida_id => $cantidad) {
            if ($cantidad > 0) {
                // Registrar partida entregada
                $sqlPartida = "INSERT INTO entregas_partidas (id_entrega, id_partida, cantidad_entregada) VALUES (?, ?, ?)";
                $stmt = $conn->prepare($sqlPartida);
                $stmt->bind_param("iii", $id_entrega, $partida_id, $cantidad);
                $stmt->execute();
                $stmt->close();
                
                // Actualizar cantidad en partida original
                /*$sqlUpdate = "UPDATE partidas SET cantidad = cantidad - ? WHERE id = ?";
                $stmt = $conn->prepare($sqlUpdate);
                $stmt->bind_param("ii", $cantidad, $partida_id);
                $stmt->execute();
                $stmt->close();*/
            }
        }
        
        // 3. Obtener datos para el correo
        $sqlProyecto = "SELECT p.*, c.nombre_comercial 
                       FROM proyectos p 
                       INNER JOIN clientes_p c ON p.id_cliente = c.id
                       WHERE p.cod_fab = ?";
        $stmt = $conn->prepare($sqlProyecto);
        $stmt->bind_param("s", $cod_fab);
        $stmt->execute();
        $proyecto_data = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        // 4. Obtener detalles de la entrega
        $sqlDetalles = "SELECT pa.descripcion, ep.cantidad_entregada, pa.unidad_medida, pa.precio_unitario
                       FROM entregas_partidas ep
                       INNER JOIN partidas pa ON ep.id_partida = pa.id
                       WHERE ep.id_entrega = ?";
        $stmt = $conn->prepare($sqlDetalles);
        $stmt->bind_param("i", $id_entrega);
        $stmt->execute();
        $detalles = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        // 5. Calcular totales
        $subtotal = 0;
        foreach ($detalles as $detalle) {
            $subtotal += $detalle['cantidad_entregada'] * $detalle['precio_unitario'];
        }
        $iva = $subtotal * 0.16;
        $total = $subtotal + $iva;
        
        // 6. Construir cuerpo del correo
        $body = "<h2>Entrega Parcial para facturar - Proyecto: {$proyecto_data['nombre']}</h2>";
        $body .= "<p><strong>Orden de Fabricación:</strong> $proyecto_id</p>";
        $body .= "<p><strong>Código de Fábrica:</strong> $cod_fab</p>";
        $body .= "<p><strong>Cliente:</strong> {$proyecto_data['nombre_comercial']}</p>";
        $body .= "<p><strong>Observaciones:</strong> $observaciones</p>";
        
        $body .= "<h3>Partidas entregadas:</h3>";
        $body .= "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
        $body .= "<tr>
                    <th>#</th>
                    <th>Descripción</th>
                    <th>Cantidad</th>
                    <th>Unidad</th>
                    <th>P. Unitario</th>
                    <th>Subtotal</th>
                  </tr>";

        foreach ($detalles as $i => $detalle) {
            $subtotal_partida = $detalle['cantidad_entregada'] * $detalle['precio_unitario'];
            $body .= "<tr>
                        <td>".($i+1)."</td>
                        <td>{$detalle['descripcion']}</td>
                        <td>{$detalle['cantidad_entregada']}</td>
                        <td>{$detalle['unidad_medida']}</td>
                        <td>$".number_format($detalle['precio_unitario'], 2)."</td>
                        <td>$".number_format($subtotal_partida, 2)."</td>
                      </tr>";
        }

        $body .= "</table>";
        $body .= "<h3>Totales:</h3>";
        $body .= "<p><strong>Subtotal:</strong> $".number_format($subtotal, 2)."</p>";
        $body .= "<p><strong>IVA (16%):</strong> $".number_format($iva, 2)."</p>";
        $body .= "<p><strong>TOTAL:</strong> $".number_format($total, 2)."</p>";

        $body .= "<p>Favor de proceder con la facturación correspondiente.</p>";

        // 7. Enviar correo
        //$to = 'sistemas@aamap.net';
        //$cc_list = ['valdolvera@gmail.com'];
        $to = 'cuentasxpxc@aamap.net';
        $cc_list = ['contabilidad@aamap.net', 'h.galicia@aamap.net'];
        send_email_order($to, $cc_list, "Entrega Parcial para facturar: $cod_fab", $body);
        
        // Confirmar transacción
        $conn->commit();
        
        echo "<script>
                alert('La entrega parcial se registró correctamente y se envió el correo con los detalles.');
                window.location.href = 'ver_proyecto.php?id=$proyecto_id';
              </script>";
    } catch (Exception $e) {
        $conn->rollback();
        echo "<script>
                alert('Error al procesar la entrega: " . addslashes($e->getMessage()) . "');
                window.location.href = 'ver_proyecto.php?id=$proyecto_id';
              </script>";
    }
} else {
    header("Location: all_projects.php");
    exit();
}
?>