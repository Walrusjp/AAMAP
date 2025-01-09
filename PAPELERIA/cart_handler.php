<?php
session_start();
include('db_connect.php'); // Asegúrate de que tu conexión a la base de datos esté bien establecida

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Procesar la adición de productos al carrito
if (isset($_POST['action']) && isset($_POST['product_id'])) {
    $productId = $_POST['product_id'];

    // Obtener el stock del producto
    $query = "SELECT stock FROM productos WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $productId);
    $stmt->execute();
    $stmt->bind_result($stock);
    $stmt->fetch();
    $stmt->close();

    // Verificar la cantidad actual en el carrito
    $currentQuantity = $_SESSION['cart'][$productId] ?? 0;

    // Si el producto ya está en el carrito y no ha alcanzado el límite de stock, incrementamos la cantidad
    if ($_POST['action'] == 'add') {
        if (($currentQuantity + 1) <= $stock) {
            $_SESSION['cart'][$productId] = $currentQuantity + 1;
        }
    }

    // Reducir la cantidad del carrito cuando se quita
    if ($_POST['action'] == 'remove') {
        if (isset($_SESSION['cart'][$productId]) && $_SESSION['cart'][$productId] > 0) {
            $_SESSION['cart'][$productId]--;
            if ($_SESSION['cart'][$productId] <= 0) {
                unset($_SESSION['cart'][$productId]);
            }
        }
    }

    // Devolver el carrito actualizado como JSON
    echo json_encode($_SESSION['cart']);
}


?>
