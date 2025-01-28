<?php
include 'C:/xampp/htdocs/PAPELERIA/db_connect.php';

if (isset($_POST['id']) && isset($_POST['estatus'])) {
    $partidaId = $_POST['id'];
    $nuevoEstatus = $_POST['estatus'];

    // Insertar nuevo registro en la tabla registro_estatus
    $sqlLog = "INSERT INTO registro_estatus (id_partida, estatus_log) VALUES (?, ?)";
    $stmtLog = $conn->prepare($sqlLog);
    $stmtLog->bind_param("is", $partidaId, $nuevoEstatus);

    if ($stmtLog->execute()) {
        // Obtener la fecha del último registro
        $ultimaFecha = date("Y-m-d H:i:s");
        echo $ultimaFecha; // Devolver la fecha para actualizar la tabla
    } else {
        echo "Error al actualizar el registro: " . $stmtLog->error;
    }

    $stmtLog->close();
}

$conn->close();
?>