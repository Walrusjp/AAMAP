<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: /login.php");
    exit();
}

require 'C:/xampp/htdocs/db_connect.php';
require 'send_email.php'; // Asegúrate de incluir el archivo que contiene la función send_email_order

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $id_fab = $_GET['id']; // Obtener el id_fab de la orden de fabricación

    // Verificar si ya se ha confirmado la acción
    if (!isset($_GET['confirmado'])) {
        // Mostrar un cuadro de confirmación antes de proceder
        echo "<script>
                var confirmacion = confirm('¿Estás seguro de que deseas mandar este proyecto a facturación?');
                if (confirmacion) {
                    // Si el usuario confirma, redirigir a la misma página con un parámetro adicional
                    window.location.href = 'mandar_facturacion.php?id=$id_fab&confirmado=true';
                } else {
                    // Si el usuario cancela, redirigir de vuelta a la lista de proyectos
                    window.location.href = 'ver_proyecto.php?id=$id_fab';
                }
              </script>";
    } else {
        // Si ya se ha confirmado, proceder con la acción
        try {
            // Obtener el cod_fab y el nombre del proyecto relacionado con la orden de fabricación
            $sql = "SELECT p.cod_fab, p.nombre FROM proyectos p 
                    INNER JOIN orden_fab o ON p.cod_fab = o.id_proyecto 
                    WHERE o.id_fab = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id_fab);
            $stmt->execute();
            $result = $stmt->get_result();
            $proyecto_data = $result->fetch_assoc();
            $stmt->close();

            if ($proyecto_data) {
                $cod_fab = $proyecto_data['cod_fab'];
                $proyecto_nombre = $proyecto_data['nombre'];

                // Actualizar el estado del proyecto a 'facturacion'
                $sql = "UPDATE proyectos SET etapa = 'facturacion' WHERE cod_fab = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("s", $cod_fab);

                if ($stmt->execute()) {
                    // Configuración del correo
                    $subject = "Proyecto para facturar " . $proyecto_nombre;
                    $body = "<p>El proyecto $proyecto_nombre (<strong>$id_fab</strong>) está listo para facturar</p>
                             <p>Favor de notificar al área correspondiente.</p>";

                    $to = 'sistemas@aamap.net'; // Destinatario principal
                    $cc_list = ['h.galicia@aamap.net', 'contabilidad@aamap.net']; // Correos en copia

                    // Llamar a la función para enviar el correo
                    send_email_order($to, $cc_list, $subject, $body);

                    // Mensaje de éxito y redirección
                    echo "<script>
                            alert('El proyecto se mandó a facturación y se envió el correo de aviso.');
                            setTimeout(function() {
                                window.location.href = 'all_projects.php';
                            }, 500); // Redirigir después de 500 ms
                          </script>";
                } else {
                    // Mensaje de error
                    echo "<script>alert('Error al mandar a facturación: " . addslashes($stmt->error) . "');</script>";
                }
            } else {
                // Mensaje de error si no se encuentra el proyecto relacionado
                echo "<script>alert('No se encontró el proyecto relacionado con la orden de fabricación.'); window.location.href = 'all_projects.php';</script>";
            }
        } catch (Exception $e) {
            // Mensaje de error
            echo "<script>alert('Error al mandar a facturación: " . addslashes($e->getMessage()) . "');</script>";
        }
    }
} else {
    // Mensaje de error si no se proporciona un ID válido
    echo "<script>alert('ID de orden de fabricación no válido.'); window.location.href = 'all_projects.php';</script>";
}
?>