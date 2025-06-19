<?php
require 'C:/xampp/htdocs/db_connect.php';

header('Content-Type: application/json');

if (isset($_GET['id_cat'])) {
    $id_cat = intval($_GET['id_cat']);
    
    $query = "SELECT id_alm, codigo, descripcion, existencia 
              FROM inventario_almacen 
              WHERE id_cat_alm = $id_cat AND activo = TRUE
              ORDER BY codigo";
    
    $result = $conn->query($query);
    $productos = $result->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode($productos);
} else {
    echo json_encode([]);
}

$conn->close();
?>