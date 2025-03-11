<?php
session_start();
require 'C:/xampp/htdocs/db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $proyectoId = $_POST['id'];

    $sql = "UPDATE proyectos SET etapa = 'facturacion' WHERE cod_fab = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $proyectoId);

    if ($stmt->execute()) {
        echo 'success';
    } else {
        echo 'error';
    }

    $stmt->close();
    $conn->close();
}
?>