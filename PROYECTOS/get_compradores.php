<?php
require 'C:/xampp/htdocs/db_connect.php';

if (isset($_POST['id_cliente'])) {
    $id_cliente = $_POST['id_cliente'];

    // Obtener compradores del cliente seleccionado
    $sql_compradores = "SELECT id_comprador, nombre FROM compradores WHERE id_cliente = ?";
    $stmt_compradores = $conn->prepare($sql_compradores);
    $stmt_compradores->bind_param("i", $id_cliente);
    $stmt_compradores->execute();
    $result_compradores = $stmt_compradores->get_result();

    $options = '<option value="">Seleccionar comprador</option>';
    if ($result_compradores->num_rows > 0) {
        while ($row = $result_compradores->fetch_assoc()) {
            $options .= '<option value="' . $row['id_comprador'] . '">' . htmlspecialchars($row['nombre']) . '</option>';
        }
    }
    echo $options;
}
?>