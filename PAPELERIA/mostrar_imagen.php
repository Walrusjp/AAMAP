<?php
require 'db_connect.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $query = "SELECT foto FROM empleados WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($foto);
    $stmt->fetch();
    $stmt->close();

    header("Content-type: image/jpg"); // Cambia el tipo MIME según el formato de tu imagen (image/png, image/gif, etc.)
    echo $foto;
}
?>
