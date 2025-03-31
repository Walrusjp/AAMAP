<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: /login.php");
    exit();
}

require 'C:\xampp\htdocs\db_connect.php';
require 'C:\xampp\htdocs\role.php';
require 'send_email.php';
//include 'search_producto.php';

// Inicializar el carrito si no está creado
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Procesar la adici�n de productos al carrito
if (isset($_POST['add_to_cart'])) {
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

    // Verificar si hay stock suficiente antes de agregar
    if (($currentQuantity + 1) <= $stock) {
        $_SESSION['cart'][$productId] = $currentQuantity + 1;
    } else {
        echo "<script>
            alert('No hay suficiente stock para este producto.');
            window.location.href='papeleria.php';
        </script>";
        exit;
    }
}

// Procesar la reducci�n del contador
if (isset($_POST['remove_from_cart'])) {
    $productId = $_POST['product_id'];
    if (isset($_SESSION['cart'][$productId]) && $_SESSION['cart'][$productId] > 0) {
        $_SESSION['cart'][$productId]--;
        if ($_SESSION['cart'][$productId] <= 0) {
            unset($_SESSION['cart'][$productId]);
        }
    }
    // Redirigir después de modificar el carrito
    echo "<script>
        window.location.href='papeleria.php';
    </script>";
    exit;
}

if (isset($_POST['save_order'])) {
    $userId = $_SESSION['user_id'];
    $username = $_SESSION['username'];

    if (!empty($_SESSION['cart'])) {
        // Variable para almacenar el cuerpo del correo
        $emailBody = "Usuario #$userId, $username solicitó:\n\n";
        $whatsappMessage = "Usuario: $username\nPedido:\n"; // Mensaje para WhatsApp
        $productsInfo = []; // Array para almacenar los productos con detalles

        foreach ($_SESSION['cart'] as $productId => $quantity) {
            // Obtener detalles del producto (descripci�n, imagen)
            $query = "SELECT id, imagen, descripcion, stock, created_at FROM productos WHERE activo = 1 AND (id LIKE ? OR descripcion LIKE ?) ORDER BY created_at ASC";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ss", $productId, $productId); // Pasar $productId dos veces
            $stmt->execute();
            $stmt->bind_result($id, $imagen, $descripcion, $stock, $created_at); // Asegúrate de que todas las columnas estén en bind_result
            $stmt->fetch();
            $stmt->close();

            // Agregar detalles al cuerpo del correo
            $emailBody .= "Producto: $productId\nDescripción: $descripcion\nCantidad: $quantity\n\n";

            // Agregar detalles para WhatsApp
            $whatsappMessage .= "$productId: $descripcion - Cantidad: $quantity\n";

            // Guardar los detalles del producto con la URL de la imagen
            $productsInfo[] = [
                'id' => $productId,
                'descripcion' => $descripcion,
                'cantidad' => $quantity,
                'imagen' => $imagen // URL de la imagen
            ];

            // Actualizar el stock del producto
            $updateStockQuery = "UPDATE productos SET stock = stock - ? WHERE id = ?";
            $updateStockStmt = $conn->prepare($updateStockQuery);
            $updateStockStmt->bind_param("is", $quantity, $productId);
            $updateStockStmt->execute();
            $updateStockStmt->close();
        }

        // Guardar el pedido en la base de datos
        $query = "INSERT INTO pedidos (usuario_id, producto_id, cantidad, tipo) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        foreach ($_SESSION['cart'] as $productId => $quantity) {
            $tipo = 'salida';
            $stmt->bind_param("ssss", $userId, $productId, $quantity, $tipo);
            $stmt->execute();
        }
        $stmt->close();

        // Generar el cuerpo del correo con im�genes
        $emailBodyHTML = "Pedido de $username:\n\n"; // Cuerpo para correo en HTML
        foreach ($productsInfo as $product) {
            $emailBodyHTML .= "<strong>Producto:</strong> {$product['descripcion']} - {$product['id']}<br>";
            $emailBodyHTML .= "<strong>Cantidad:</strong> {$product['cantidad']}<br>";
            if (!empty($product['imagen'])) {
                $emailBodyHTML .= "<img src='{$product['imagen']}' alt='{$product['descripcion']}' width='100'><br><br>"; // Incluir imagen
            }
            $emailBodyHTML .= "<hr>";
        }

        // Enviar el correo con el cuerpo en formato HTML
        $to = "papeleria.aamap@gmail.com";
        $subject = "PAPELERIA AAMAP - Pedido de stock";
        send_email_order($to, $subject, $emailBodyHTML); // Se pasa el cuerpo en HTML

        // Limpiar el carrito
        $_SESSION['cart'] = [];
        echo "<script>window.location.href='papeleria.php';</script>";
        exit;
    } else {
        echo "<script>
            alert('El carrito está vacío. No se puede guardar el pedido.');
            window.location.href='papeleria.php';
        </script>";
        exit;
    }
}

// Procesar la accion de solicitud de producto
if (isset($_POST['request_product'])) {
    $userId = $_SESSION['user_id'];
    $productId = $_POST['product_id'];

    // Verificar si el producto existe en la tabla productos
    $checkQuery = "SELECT COUNT(*) FROM productos WHERE id = ?";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bind_param("s", $productId);
    $checkStmt->execute();
    $checkStmt->bind_result($productExists);
    $checkStmt->fetch();
    $checkStmt->close();

    if ($productExists > 0) {
        // Insertar la solicitud con cantidad 1
        $query = "INSERT INTO pedidos (usuario_id, producto_id, cantidad, tipo) VALUES (?, ?, 1, 'solicitud')";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ss", $userId, $productId);
        $stmt->execute();
        $stmt->close();

        echo "<script>
            alert('Solicitud realizada con &eacute;xito.');
            window.location.href='papeleria.php';
        </script>";
    } else {
        echo "<script>
            alert('El producto seleccionado no existe. Por favor, selecciona un producto v&aacute;lido.');
            window.location.href='papeleria.php';
        </script>";
    }
    exit;
}

//ocultar productos a op
$productos_ocultos = ['PROD000', 'PROD001', 'PROD002', 'PROD003'];

// Buscar productos y consultar productos para mostrar
$search = "";
if (isset($_GET['search'])) {
    $search = $_GET['search'];
}

$query = "SELECT id, imagen, descripcion, stock FROM productos WHERE activo = 1 AND (id LIKE ? OR descripcion LIKE ?)";
$search_param = "%" . $search . "%";
$stmt = $conn->prepare($query);
$stmt->bind_param("ss", $search_param, $search_param);
$stmt->execute();
$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Productos</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="styles2.css">
    <link rel="icon" href="assets/logo.ico" >
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
<div class="sticky-header">
    <img src="/assets/grupo_aamap.webp" style="width: 17%; position: absolute; top: 0px; left: 0px;">
    <div class="container flex sopas">
    <div class="search-box">
        <p>&emsp;&emsp;&emsp;&emsp;&emsp;&emsp;&emsp;&emsp;</p>
        <form method="GET" action="papeleria.php" class="search-form">
            <?php if(isset($_GET['search']) && !empty($_GET['search'])): ?>
                <a href="papeleria.php" class="clear-search" title="Cancelar búsqueda">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                        <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/>
                    </svg>
                </a>
            <?php endif; ?>
            <input type="text" name="search" class="form-control" id="psearch" 
                placeholder="Buscar..." value="<?php echo htmlspecialchars($search ?? ''); ?>">
            <button type="submit" class="search-button" title="Buscar">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/>
                </svg>
            </button>
        </form>
        <div id="searchResults"></div>
    </div>
        <div class="bpanel">
            <form method="POST" action="">
                <button type="submit" name="save_order" class="btn btn-success push">Enviar pedido</button>
            </form>
            <!--<a href="solicitar_producto.php" class="btn btn-warning jiji">Solicitar</a>-->
            <a href="agregar_producto.php" class="btn btn-info jiji">Agregar Nuevo Producto</a>
            <?php if($role === 'admin'): ?>
                <a href="pedidos.php" class="btn btn-secondary sec jiji">Pedidos</a>
                <a href="cotizacion.php" class="btn btn-secondary sec jiji">Cotizaci&oacute;n</a>
            <?php endif; ?>
            <form method="POST" action="/launch.php">
                <button type="submit" name="salir" class="logout-button push btn" id="logout">Regresar</button>
            </form>
        </div>
        </form>
    </div>
</div>

<div class="container mt-5">
    <div class="row">
        <?php while ($row = $result->fetch_assoc()): ?>
            <?php if ($role === 'operador' && in_array($row['id'], $productos_ocultos)): ?>
                <!-- Si el rol es operador y el producto está en la lista de productos ocultos, se omite -->
                <?php continue; ?>
            <?php endif; ?>

            <!-- Verificar si hay una solicitud específica para este producto -->
            <?php
            // Comprobamos si ya existe una solicitud de tipo "solicitud" para este producto
            $querySolicitud = "SELECT * FROM pedidos WHERE usuario_id = ? AND producto_id = ? AND tipo = 'solicitud' AND fecha > NOW() - INTERVAL 20 DAY";
            $stmtSolicitud = $conn->prepare($querySolicitud);
            $stmtSolicitud->bind_param("ss", $_SESSION['user_id'], $row['id']);
            $stmtSolicitud->execute();
            $solicitudExistente = $stmtSolicitud->get_result()->fetch_assoc();

            // Comprobamos si ya existe una solicitud de tipo "personalizado" para este producto
            $queryPersonalizado = "SELECT * FROM pedidos WHERE usuario_id = ? AND producto_id = ? AND tipo = 'personalizado' AND fecha > NOW() - INTERVAL 20 DAY";
            $stmtPersonalizado = $conn->prepare($queryPersonalizado);
            $stmtPersonalizado->bind_param("ss", $_SESSION['user_id'], $row['id']);
            $stmtPersonalizado->execute();
            $personalizadoExistente = $stmtPersonalizado->get_result()->fetch_assoc();

            // Si el formulario de solicitud se envió
            if (isset($_POST['request_product'])) {
                // Realizamos la solicitud para el producto
                $query = "INSERT INTO pedidos (usuario_id, producto_id, tipo, fecha) VALUES (?, ?, 'solicitud', NOW())";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("ss", $_SESSION['user_id'], $_POST['product_id']);
                $stmt->execute();
                
                // Ahora cambiamos la solicitud a tipo personalizado
                // Mostramos el botón de "Solicitud Personalizada"
                $solicitudExistente = true;
            }
            ?>

            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <img src="<?php echo htmlspecialchars($row['imagen']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($row['descripcion']); ?>" style="height:200px; object-fit:cover;">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($row['descripcion']); ?></h5>
                        <p class="card-text">
                            <strong>ID:</strong> <?php echo htmlspecialchars($row['id']); ?><br>
                            <strong>Stock:</strong> <?php echo htmlspecialchars($row['stock']); ?>
                        </p>
                        <div class="d-flex justify-content-between align-items-center">
                            <!-- Botones de a�adir y quitar con AJAX -->
                            <div style="display:inline-flex;">
                                <button type="button" class="btn btn-success add-to-cart btncard" 
                                    data-id="<?php echo $row['id']; ?>"
                                    <?php echo ($row['stock'] <= 0) ? 'disabled' : ''; ?>>+</button>

                                <button type="button" class="btn btn-danger remove-from-cart btncard" 
                                    data-id="<?php echo $row['id']; ?>">-</button>


                                <?php if ($row['stock'] == 0): ?>
                                    <?php if ($solicitudExistente && !$personalizadoExistente): ?>
                                        <!-- Si ya existe un pedido de tipo "solicitud" pero no la "personalizada", mostramos "Solicitud Personalizada" -->
                                        <a href="solicitar_producto.php?product_id=<?php echo $row['id']; ?>&product_desc=<?php echo urlencode($row['descripcion']); ?>" class="btn btn-secondary btncard">Solicitud Personalizada</a>
                                    <?php elseif ($personalizadoExistente): ?>
                                        <!-- Si ya existe una solicitud de tipo "personalizado", mostramos "Producto Solicitado" -->
                                        <button class="btn btn-secondary btncard" disabled>Producto Solicitado</button>
                                    <?php else: ?>
                                        <!-- Si no existe ninguna solicitud, mostramos "Solicitar" -->
                                        <form method="post" action="" onsubmit="return confirm('&iquest;Est&aacute;s seguro de que deseas solicitar este producto?');">
                                            <input type="hidden" name="product_id" value="<?php echo $row['id']; ?>">
                                            <button type="submit" name="request_product" class="btn btn-warning btncard">Solicitar</button>
                                        </form>
                                    <?php endif; ?>
                                <?php endif; ?>


                            </div>
                            <!-- Mostrar cantidad en el carrito -->
                            <span class="badge badge-secondary" id="cart-quantity-<?php echo $row['id']; ?>">
                                <?php echo isset($_SESSION['cart'][$row['id']]) ? $_SESSION['cart'][$row['id']] : 0; ?>
                                    
                            </span>


                        </div>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</div>
</body>
<script src="script_pape.js" type="text/javascript"></script>
</html>
