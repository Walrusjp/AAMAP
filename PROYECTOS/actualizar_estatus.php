<?php
session_start();
include 'C:/xampp/htdocs/PAPELERIA/db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $partidaId = $_POST['id'];
    $nuevoEstatus = $_POST['estatus'];
    $id_proyecto = $_POST['id_proyecto'];
    $id_usuario = $_POST['id_usuario'];

    // Actualizar el estatus
    $sqlUpdate = "UPDATE registro_estatus SET estatus_log =? WHERE id_partida =?";
    $stmtUpdate = $conn->prepare($sqlUpdate);
    $stmtUpdate->bind_param("si", $nuevoEstatus, $partidaId);

    if ($stmtUpdate->execute()) {
        // Registrar el log (sin especificar fecha_log)
        $sqlLog = "INSERT INTO registro_estatus (id_proyecto, estatus_log, id_usuario, id_partida) VALUES (?,?,?,?)";
        $stmtLog = $conn->prepare($sqlLog);
        $stmtLog->bind_param("ssii", $id_proyecto, $nuevoEstatus, $id_usuario, $partidaId); // Corregido: 4 parámetros

        if ($stmtLog->execute()) {
            // Obtener la fecha y hora del log recién insertado
            $log_id = $stmtLog->insert_id;

            $sqlFecha = "SELECT fecha_log FROM registro_estatus WHERE id =?";
            $stmtFecha = $conn->prepare($sqlFecha);
            $stmtFecha->bind_param('i', $log_id);
            $stmtFecha->execute();
            $resultFecha = $stmtFecha->get_result();

            if ($resultFecha->num_rows > 0){
                $rowFecha = $resultFecha->fetch_assoc();
                $fecha_log = $rowFecha['fecha_log'];
                echo $fecha_log;
            } else {
                echo "Error al obtener la fecha del log";
            }
        } else {
            echo "Error al registrar el log: ". $stmtLog->error;
        }

        $stmtLog->close();
        $stmtFecha->close(); // Cerrar la consulta de fecha
    } else {
        echo "Error al actualizar el estatus: ". $stmtUpdate->error;
    }

    $stmtUpdate->close();
    $conn->close();
}?>