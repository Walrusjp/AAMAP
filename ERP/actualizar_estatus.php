<?php
session_start();
require 'C:/xampp/htdocs/db_connect.php';

// Establecer la zona horaria de México
date_default_timezone_set('America/Mexico_City');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Obtener los datos enviados por AJAX
    $partidaId = $_POST['id'];
    $nuevoEstatus = $_POST['estatus'];
    $id_fab = $_POST['id_fab']; // Ahora usamos id_fab en lugar de id_proyecto
    $id_usuario = $_POST['id_usuario'];

    // Insertar el nuevo estatus en registro_estatus
    $sql = "INSERT INTO registro_estatus (id_partida, estatus_log, id_fab, id_usuario, fecha_log) 
            VALUES (?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Error en la preparación de la consulta: " . $conn->error);
    }
    $stmt->bind_param("issi", $partidaId, $nuevoEstatus, $id_fab, $id_usuario);

    if ($stmt->execute()) {
        // Devolver la fecha de actualización para mostrarla en la tabla
        echo date("Y-m-d H:i:s");
    } else {
        // Si hay un error, devolver un mensaje de error
        echo "Error al actualizar el estatus: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>