<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: /login.php");
    exit();
}

require 'C:/xampp/htdocs/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $id_fab = $_GET['id']; // Obtener el id_fab de la orden de fabricación

    // Verificar si ya se ha confirmado la acción
    if (!isset($_GET['confirmado'])) {
        // Mostrar un cuadro de confirmación antes de proceder
        echo "<script>
                var confirmacion = confirm('¿Estás seguro de que deseas finalizar este proyecto?');
                if (confirmacion) {
                    // Si el usuario confirma, redirigir a la misma página con un parámetro adicional
                    window.location.href = 'finish_project.php?id=$id_fab&confirmado=true';
                } else {
                    // Si el usuario cancela, redirigir de vuelta a la lista de proyectos
                    window.location.href = 'ver_proyecto.php?id=$id_fab';
                }
              </script>";
    } else {
        // Si ya se ha confirmado, proceder con la acción
        try {
            // Obtener el cod_fab del proyecto relacionado con la orden de fabricación
            $sql = "SELECT id_proyecto FROM orden_fab WHERE id_fab = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id_fab);
            $stmt->execute();
            $result = $stmt->get_result();
            $proyecto_data = $result->fetch_assoc();
            $stmt->close();

            if ($proyecto_data) {
                $cod_fab = $proyecto_data['id_proyecto'];

                // Actualizar el estado del proyecto a 'finalizado'
                $sql = "UPDATE proyectos SET etapa = 'finalizado' WHERE cod_fab = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("s", $cod_fab);

                if ($stmt->execute()) {
                    // Mensaje de éxito y redirección
                    echo "<script>
                            alert('El proyecto ha finalizado.');
                            setTimeout(function() {
                                window.location.href = 'all_projects.php';
                            }, 500); // Redirigir después de 300 ms
                          </script>";
                } else {
                    // Mensaje de error
                    echo "<script>alert('Error al finalizar el proyecto: " . addslashes($stmt->error) . "');</script>";
                }
            } else {
                // Mensaje de error si no se encuentra el proyecto relacionado
                echo "<script>alert('No se encontró el proyecto relacionado con la orden de fabricación.'); window.location.href = 'all_projects.php';</script>";
            }
        } catch (Exception $e) {
            // Mensaje de error
            echo "<script>alert('Error al finalizar: " . addslashes($e->getMessage()) . "');</script>";
        }
    }
} else {
    // Mensaje de error si no se proporciona un ID válido
    echo "<script>alert('ID de orden de fabricación no válido.'); window.location.href = 'all_projects.php';</script>";
}
?>