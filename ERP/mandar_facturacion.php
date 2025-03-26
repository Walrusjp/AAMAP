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
                var confirmacion = confirm('¿Estás seguro de que deseas mandar este proyecto a facturación?');
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

                // Actualizar estado
                $sql = "UPDATE proyectos SET etapa = 'facturacion' WHERE cod_fab = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("s", $cod_fab);
                $stmt->execute();
                $stmt->close();

                // Obtener partidas con cálculos
                $sql_partidas = "SELECT descripcion, cantidad, unidad_medida, 
                                precio_unitario, (cantidad * precio_unitario) as subtotal
                                FROM partidas WHERE cod_fab = ?";
                $stmt = $conn->prepare($sql_partidas);
                $stmt->bind_param("s", $cod_fab);
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
                $body = "<h2>Proyecto para facturar: $proyecto_nombre</h2>";
                $body .= "<p><strong>Orden de Fabricación:</strong> $id_fab</p>";
                $body .= "<p><strong>Basado en cotización:</strong> $cod_fab</p>";
                $body .= "<p><strong>Cliente:</strong> {$proyecto_data['nombre_comercial']}</p>";
                $fecha_entrega = date('d/m/Y', strtotime($proyecto_data['fecha_entrega']));
                $body .= "<p><strong>Fecha de Entrega:</strong> $fecha_entrega</p>";
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

                $body .= "<h3>Partidas:</h3>";
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
                                <td>{$partida['cantidad']}</td>
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

                // Enviar correo
                $to = 'cuentasxpxc@aamap.net';
                $cc_list = ['contabilidad@aamap.net', 'h.galicia@aamap.net'];
                send_email_order($to, $cc_list, "Proyecto para facturar: $cod_fab", $body);

                echo "<script>
                        alert('El proyecto se mandó a facturación y se envió el correo con todos los detalles.');
                        setTimeout(function() {
                            window.location.href = 'all_projects.php';
                        }, 500);
                      </script>";
            } else {
                echo "<script>alert('No se encontró el proyecto relacionado.'); window.location.href = 'all_projects.php';</script>";
            }
        } catch (Exception $e) {
            echo "<script>alert('Error: " . addslashes($e->getMessage()) . "');</script>";
        }
    }
} else {
    echo "<script>alert('ID no válido.'); window.location.href = 'all_projects.php';</script>";
}
?>