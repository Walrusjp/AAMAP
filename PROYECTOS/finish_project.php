<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

include 'C:/xampp/htdocs/PAPELERIA/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $proyectoId = $_GET['id'];

    try {
        // Actualizar el estado del proyecto a 'finalizado'
        $sql = "UPDATE proyectos SET etapa = 'finalizado' WHERE cod_fab = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $proyectoId);

        if ($stmt->execute()) {
            echo "<script>alert('Proyecto finalizado exitosamente.'); window.location.href = 'all_projects.php';</script>";
        } else {
            echo "<script>alert('Error al finalizar el proyecto: " . $stmt->error . "');</script>";
        }
    } catch (Exception $e) {
        echo "<script>alert('Error al finalizar el proyecto: " . $e->getMessage() . "');</script>";
    }
} else {
    echo "<script>alert('ID de proyecto no válido.'); window.location.href = 'all_projects.php';</script>";
}
?>