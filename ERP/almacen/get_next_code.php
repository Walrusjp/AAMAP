<?php
session_start();
require 'C:/xampp/htdocs/db_connect.php';

header('Content-Type: text/plain');

if (!isset($_SESSION['username']) || !isset($_POST['prefix'])) {
    die("Acceso no autorizado");
}

$prefix = trim($_POST['prefix']);

if (empty($prefix)) {
    die("Prefijo no válido");
}

// Función para obtener el siguiente código disponible
function getNextCode($conn, $prefix) {
    $query = "SELECT codigo FROM inventario_almacen 
              WHERE codigo LIKE ? 
              ORDER BY codigo DESC LIMIT 1";
    $stmt = $conn->prepare($query);
    $like_prefix = $prefix . '-%';
    $stmt->bind_param("s", $like_prefix);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $last_code = $result->fetch_assoc()['codigo'];
        $parts = explode('-', $last_code);
        $last_number = isset($parts[1]) ? intval($parts[1]) : 0;
        $new_number = $last_number + 1;
    } else {
        $new_number = 1;
    }
    
    return sprintf("%s-%03d", $prefix, $new_number);
}

echo getNextCode($conn, $prefix);
?>