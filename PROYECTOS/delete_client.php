<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

include 'C:/xampp/htdocs/PAPELERIA/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $clientId = $_GET['id'];

    try {
        // Eliminar cliente
        $sql = "DELETE FROM clientes_p WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $clientId);

        if ($stmt->execute()) {
            echo "<script>alert('Cliente eliminado exitosamente.'); window.location.href = 'ver_clientes.php';</script>"; 
        } else {
            echo "<script>alert('Error al eliminar el cliente: " . $stmt->error . "');</script>";
        }

    } catch (Exception $e) {
        echo "<script>alert('Error al eliminar el cliente: " . $e->getMessage() . "');</script>";
    }
} else {
    echo "<script>alert('ID de cliente no válido.'); window.location.href = 'ver_clientes.php';</script>";
}
?>