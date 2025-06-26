<?php
require 'C:/xampp/htdocs/db_connect.php';

header('Content-Type: application/json');

if (isset($_GET['id_cat'])) {
    $id_cat = intval($_GET['id_cat']);
    
    $query = "SELECT ia.id_alm, ia.codigo, ia.descripcion, ia.existencia, ca.categoria 
             FROM inventario_almacen ia
             JOIN categorias_almacen ca ON ia.id_cat_alm = ca.id_cat_alm
             WHERE ia.id_cat_alm = $id_cat AND ia.activo = TRUE
             ORDER BY ia.codigo";
    
    $result = $conn->query($query);
    $productos = $result->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode($productos);
} else {
    echo json_encode([]);
}

$conn->close();