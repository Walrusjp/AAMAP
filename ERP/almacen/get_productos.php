<?php
session_start();
require 'C:/xampp/htdocs/db_connect.php';

header('Content-Type: application/json');

if (isset($_GET['categoria_id'])) {
    $categoria_id = (int)$_GET['categoria_id'];
    
    $query = "SELECT id_alm, codigo, descripcion, existencia 
              FROM inventario_almacen 
              WHERE id_cat_alm = $categoria_id AND activo = 1 AND existencia > 0
              ORDER BY codigo";
    $result = $conn->query($query);
    
    $productos = [];
    while ($row = $result->fetch_assoc()) {
        $productos[] = $row;
    }
    
    echo json_encode($productos);
} else {
    echo json_encode([]);
}

$conn->close();
?>