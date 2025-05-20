<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: /login.php");
    exit();
}

require 'C:/xampp/htdocs/db_connect.php';
require 'send_email.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $id_fab = $_GET['id'];

    if (!isset($_GET['confirmado'])) {
        echo "<script>
                var confirmacion = confirm('¿Estás seguro de que deseas mandar este proyecto a facturación?\\n\\nSe registrará automáticamente una entrega con las cantidades pendientes.');
                if (confirmacion) {
                    window.location.href = 'mandar_facturacion.php?id=$id_fab&confirmado=true';
                } else {
                    window.location.href = 'ver_proyecto.php?id=$id_fab';
                }
              </script>";
    } else {
        try {
            // Obtener datos completos del proyecto
            $sql = "SELECT p.*, c.nombre_comercial, co.nombre as comprador_nombre, 
                    co.telefono as comprador_telefono, co.correo as comprador_email,
                    dv.vigencia, dv.precios, dv.moneda, dv.condicion_pago, dv.lab, dv.tipo_entr
                    FROM proyectos p 
                    INNER JOIN clientes_p c ON p.id_cliente = c.id
                    INNER JOIN compradores co ON p.id_comprador = co.id_comprador
                    LEFT JOIN datos_vigencia dv ON p.cod_fab = dv.cod_fab
                    INNER JOIN orden_fab o ON p.cod_fab = o.id_proyecto 
                    WHERE o.id_fab = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id_fab);
            $stmt->execute();
            $proyecto_data = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($proyecto_data) {
                $cod_fab = $proyecto_data['cod_fab'];
                $proyecto_nombre = $proyecto_data['nombre'];

                // Iniciar transacción
                $conn->begin_transaction();

                // 1. Registrar entrega automática de cantidades pendientes
                $sqlPendientes = "SELECT 
                                    p.id,
                                    p.descripcion, 
                                    (p.cantidad - IFNULL(
                                        (SELECT SUM(ep.cantidad_entregada) 
                                         FROM entregas_partidas ep
                                         INNER JOIN entregas_parciales e ON ep.id_entrega = e.id
                                         WHERE ep.id_partida = p.id AND e.id_proyecto = p.cod_fab), 0
                                    )) AS cantidad_pendiente,
                                    p.unidad_medida, 
                                    p.precio_unitario
                                  FROM partidas p 
                                  WHERE p.cod_fab = ?
                                  HAVING cantidad_pendiente > 0";
                $stmt = $conn->prepare($sqlPendientes);
                $stmt->bind_param("s", $cod_fab);
                $stmt->execute();
                $partidas_pendientes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                $stmt->close();

                // Registrar la entrega automática
                $sqlEntrega = "INSERT INTO entregas_parciales (id_proyecto, id_usuario, automatica) VALUES (?, ?, 1)";
                $stmt = $conn->prepare($sqlEntrega);
                $stmt->bind_param("si", $cod_fab, $_SESSION['user_id']);
                $stmt->execute();
                $id_entrega = $conn->insert_id;
                $stmt->close();

                // Registrar cada partida pendiente en la entrega
                foreach ($partidas_pendientes as $partida) {
                    $sqlPartida = "INSERT INTO entregas_partidas (id_entrega, id_partida, cantidad_entregada) 
                                  VALUES (?, ?, ?)";
                    $stmt = $conn->prepare($sqlPartida);
                    $stmt->bind_param("iii", $id_entrega, $partida['id'], $partida['cantidad_pendiente']);
                    $stmt->execute();
                    $stmt->close();
                    
                    // Registrar en logs
                    $sqlLog = "INSERT INTO registro_estatus (id_proyecto, id_partida, estatus_log, id_usuario, id_fab)
                               VALUES (?, ?, 'PROYECTO FACTURADO', ?, ?)";
                    $stmt = $conn->prepare($sqlLog);
                    $stmt->bind_param("siii", $cod_fab, $partida['id'], $_SESSION['user_id'], $id_fab);
                    $stmt->execute();
                    $stmt->close();
                }

                // 2. Actualizar estado del proyecto
                $sql = "UPDATE proyectos SET etapa = 'facturacion' WHERE cod_fab = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("s", $cod_fab);
                $stmt->execute();
                $stmt->close();

                // 3. Obtener partidas para el correo (usando las que acabamos de registrar como entregadas)
                $sql_partidas = "SELECT 
                                p.descripcion, 
                                ep.cantidad_entregada AS cantidad,
                                p.unidad_medida, 
                                p.precio_unitario, 
                                (ep.cantidad_entregada * p.precio_unitario) as subtotal
                                FROM entregas_partidas ep
                                INNER JOIN partidas p ON ep.id_partida = p.id
                                INNER JOIN entregas_parciales e ON ep.id_entrega = e.id
                                WHERE e.id_proyecto = ? AND e.id = ?";
                $stmt = $conn->prepare($sql_partidas);
                $stmt->bind_param("si", $cod_fab, $id_entrega);
                $stmt->execute();
                $partidas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                $stmt->close();

                // Calcular totales
                $subtotal = 0;
                foreach ($partidas as $partida) {
                    $subtotal += $partida['subtotal'];
                }
                $iva = $subtotal * 0.16; // Asumiendo 16% de IVA
                $total = $subtotal + $iva;

                // Construir cuerpo del correo
                $body = "<h2>Facturación de cierre de: $proyecto_nombre</h2>";
                $body .= "<p><strong>Orden de Fabricación:</strong> $id_fab</p>";
                $body .= "<p><strong>Basado en cotización:</strong> $cod_fab</p>";
                $body .= "<p><strong>Cliente:</strong> {$proyecto_data['nombre_comercial']}</p>";
                $fecha_entrega = date('d/m/Y', strtotime($proyecto_data['fecha_entrega']));
                $body .= "<p><strong>Fecha de Entrega:</strong> $fecha_entrega</p>";

                $body .= "<div style='background-color: #f8f9fa; border-left: 4px solid #d9534f; padding: 10px; margin-bottom: 15px;'>
                         <h4 style='color: #d9534f;'>¡Atención!</h4>
                         <p>Se registró automáticamente una entrega con las cantidades pendientes y se procede con su facturación.</p>
                         </div>";

                $body .= "<h3>Datos del comprador:</h3>";
                $body .= "<p><strong>Nombre:</strong> {$proyecto_data['comprador_nombre']}</p>";
                $body .= "<p><strong>Teléfono:</strong> {$proyecto_data['comprador_telefono']}</p>";
                $body .= "<p><strong>Email:</strong> {$proyecto_data['comprador_email']}</p>";
                
                $body .= "<h3>Datos de vigencia:</h3>";
                $body .= "<p><strong>Vigencia:</strong> {$proyecto_data['vigencia']}</p>";
                $body .= "<p><strong>Precios:</strong> {$proyecto_data['precios']}</p>";
                $body .= "<p><strong>Moneda:</strong> {$proyecto_data['moneda']}</p>";
                $body .= "<p><strong>Condiciones de pago:</strong> {$proyecto_data['condicion_pago']}</p>";
                $body .= "<p><strong>Lugar de entrega:</strong> {$proyecto_data['lab']}</p>";
                $body .= "<p><strong>Tipo de entrega:</strong> {$proyecto_data['tipo_entr']}</p>";

                $body .= "<h3>Partidas facturadas:</h3>";
                $body .= "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
                $body .= "<tr>
                            <th>#</th>
                            <th>Descripción</th>
                            <th>Cantidad</th>
                            <th>Unidad</th>
                            <th>P. Unitario</th>
                            <th>Subtotal</th>
                          </tr>";

                foreach ($partidas as $i => $partida) {
                    $body .= "<tr>
                                <td>".($i+1)."</td>
                                <td>{$partida['descripcion']}</td>
                                <td>{$partida['cantidad']} (auto)</td>
                                <td>{$partida['unidad_medida']}</td>
                                <td>$".number_format($partida['precio_unitario'], 2)."</td>
                                <td>$".number_format($partida['subtotal'], 2)."</td>
                              </tr>";
                }

                $body .= "</table>";

                $body .= "<h3>Totales:</h3>";
                $body .= "<p><strong>Subtotal:</strong> $".number_format($subtotal, 2)."</p>";
                $body .= "<p><strong>IVA (16%):</strong> $".number_format($iva, 2)."</p>";
                $body .= "<p><strong>TOTAL:</strong> $".number_format($total, 2)."</p>";

                $body .= "<p>Favor de proceder con la facturación correspondiente.</p>";

                // Confirmar transacción
                $conn->commit();

                // Enviar correo
                //$to = 'sistemas@aamap.net';
                //$cc_list = ['valdolvera@gmail.com'];
                $to = 'cuentasxpxc@aamap.net';
                $cc_list = ['contabilidad@aamap.net', 'h.galicia@aamap.net'];
                send_email_order($to, $cc_list, "Facturación de pendientes: $cod_fab", $body);

                echo "<script>
                        alert('Se registró entrega automática de cantidades pendientes y se envió a facturación.');
                        setTimeout(function() {
                            window.location.href = 'all_projects.php';
                        }, 500);
                      </script>";
            } else {
                echo "<script>alert('No se encontró el proyecto relacionado.'); window.location.href = 'all_projects.php';</script>";
            }
        } catch (Exception $e) {
            $conn->rollback();
            echo "<script>alert('Error: " . addslashes($e->getMessage()) . "'); window.location.href = 'ver_proyecto.php?id=$id_fab';</script>";
        }
    }
} else {
    echo "<script>alert('ID no válido.'); window.location.href = 'all_projects.php';</script>";
}
?>