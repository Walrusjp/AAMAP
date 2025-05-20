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
                var confirmacion = confirm('¿Estás seguro de que deseas finalizar este proyecto?\\n\\nSe registrará un estatus de cierre.');
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
            // Iniciar transacción
            $conn->begin_transaction();

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

                // 1. Actualizar el estado del proyecto a 'finalizado'
                $sql = "UPDATE proyectos SET etapa = 'finalizado' WHERE cod_fab = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("s", $cod_fab);
                $stmt->execute();
                $stmt->close();

                // 2. Registrar el estatus de cierre para todas las partidas del proyecto
                // Primero obtenemos todas las partidas del proyecto
                $sqlPartidas = "SELECT id FROM partidas WHERE cod_fab = ?";
                $stmt = $conn->prepare($sqlPartidas);
                $stmt->bind_param("s", $cod_fab);
                $stmt->execute();
                $partidas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                $stmt->close();

                // Registrar estatus para cada partida
                foreach ($partidas as $partida) {
                    $sqlEstatus = "INSERT INTO registro_estatus 
                                  (id_proyecto, id_partida, estatus_log, id_usuario, id_fab) 
                                  VALUES (?, ?, 'PROYECTO CERRADO', ?, ?)";
                    $stmt = $conn->prepare($sqlEstatus);
                    $stmt->bind_param("siii", $cod_fab, $partida['id'], $_SESSION['user_id'], $id_fab);
                    $stmt->execute();
                    $stmt->close();
                }

                // 3. Registrar un estatus general para el proyecto (sin partida específica)
                $sqlEstatusProyecto = "INSERT INTO registro_estatus 
                                      (id_proyecto, estatus_log, id_usuario, id_fab) 
                                      VALUES (?, 'PROYECTO CERRADO', ?, ?)";
                $stmt = $conn->prepare($sqlEstatusProyecto);
                $stmt->bind_param("sii", $cod_fab, $_SESSION['user_id'], $id_fab);
                $stmt->execute();
                $stmt->close();

                // Confirmar transacción
                $conn->commit();

                // Mensaje de éxito y redirección
                echo "<script>
                        alert('El proyecto ha finalizado y se registró el estatus de cierre.');
                        setTimeout(function() {
                            window.location.href = 'all_projects.php';
                        }, 500);
                      </script>";
            } else {
                // Mensaje de error si no se encuentra el proyecto relacionado
                echo "<script>alert('No se encontró el proyecto relacionado con la orden de fabricación.'); window.location.href = 'all_projects.php';</script>";
            }
        } catch (Exception $e) {
            // Revertir transacción en caso de error
            $conn->rollback();
            // Mensaje de error
            echo "<script>alert('Error al finalizar: " . addslashes($e->getMessage()) . "'); window.location.href = 'ver_proyecto.php?id=$id_fab';</script>";
        }
    }
} else {
    // Mensaje de error si no se proporciona un ID válido
    echo "<script>alert('ID de orden de fabricación no válido.'); window.location.href = 'all_projects.php';</script>";
}
?>