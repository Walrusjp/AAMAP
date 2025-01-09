// Inicializar el carrito si no está creado
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Procesar la adición de productos al carrito
if (isset($_POST['add_to_cart'])) {
    $productId = $_POST['product_id'];
    
    // Incrementar el contador del producto en el carrito
    if (isset($_SESSION['cart'][$productId])) {
        $_SESSION['cart'][$productId]++;  
    } else {
        $_SESSION['cart'][$productId] = 1; 
    }
}

// Procesar la reducción del contador
if (isset($_POST['remove_from_cart'])) {
    $productId = $_POST['product_id'];
    
    // Decrementar el contador del producto en el carrito
    if (isset($_SESSION['cart'][$productId]) && $_SESSION['cart'][$productId] > 0) {
        $_SESSION['cart'][$productId]--;  
        if ($_SESSION['cart'][$productId] <= 0) {
            unset($_SESSION['cart'][$productId]); // Eliminar producto si la cantidad es 0
        }
    }
}

// Procesar la acción de guardar el pedido
if (isset($_POST['save_order'])) {
    $userId = $_SESSION['user_id']; 
    
    if (!empty($_SESSION['cart'])) {
        // Preparar la consulta de inserción
        $query = "INSERT INTO pedidos (usuario_id, producto_id, cantidad) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($query);

        if ($stmt === false) {
            die("Error en la preparación de la consulta: " . $conn->error);
        }

        // Verificar que los productos existan en la base de datos antes de guardarlos
        foreach ($_SESSION['cart'] as $productId => $quantity) {
            // Verificar si el producto existe
            $productCheckQuery = "SELECT COUNT(*) FROM productos WHERE id = ?";
            $productCheckStmt = $conn->prepare($productCheckQuery);
            $productCheckStmt->bind_param("s", $productId);  // Usa "s" para VARCHAR
            $productCheckStmt->execute();
            $productCheckStmt->bind_result($productExists);
            $productCheckStmt->fetch();
            $productCheckStmt->close(); 

            if ($productExists > 0) {
                // Si el producto existe, proceder a guardar el pedido
                $stmt->bind_param("sss", $userId, $productId, $quantity);
                $stmt->execute();
            } else {
                echo "<script>alert('Error: El producto con ID $productId no existe en la base de datos.');</script>";
            }
        }

        $stmt->close(); 

        // Limpiar el carrito después de guardar el pedido
        $_SESSION['cart'] = [];
        echo "<script>alert('Pedido guardado correctamente.');</script>";
    } else {
        echo "<script>alert('El carrito est&aacute; vacío. No se puede guardar el pedido.');</script>";
    }
}