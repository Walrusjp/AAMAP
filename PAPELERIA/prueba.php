<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

include 'db_connect.php';
include 'role.php';

// Verificar si se solicitó el cierre de sesión
if (isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header("Location: index.php");
    exit();
}

// Verificar si la ventana emergente ya se mostró
if (!isset($_SESSION['welcome_shown'])) {
    $_SESSION['welcome_shown'] = true;
    $showModal = true;
} else {
    $showModal = false;
}

// Inicializar el carrito si no está creado
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Procesar la adición de productos al carrito
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

// Procesar la reducción del contador
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

// Configuración de Twilio
require_once 'vendor/autoload.php'; // Asegúrate de que el archivo de autoload esté disponible
use Twilio\Rest\Client;

function send_whatsapp_message($to, $message) {
    $sid = 'AC652b1ed2b45515730b77413ee1bd279c';  // Tu SID de cuenta de Twilio
    $token  = "aced3d82be8f8d62f96bea93fb4f288a"; // Tu token de autenticación de Twilio
    $from = 'whatsapp:+14155238886';  // El número de WhatsApp proporcionado por Twilio

    $client = new Client($sid, $token);

    // Enviar el mensaje
    try {
        $client->messages->create(
            "whatsapp:+5217831010939",
            //'whatsapp:' . $to, // El número de destino
            [
                'from' => $from,
                'body' => $message
            ]
        );
        echo "<script>alert('Pedido guardado y mensaje de WhatsApp enviado correctamente.');</script>";
    } catch (Exception $e) {
        echo "<script>alert('Error al enviar mensaje de WhatsApp: " . $e->getMessage() . "');</script>";
    }
}



// Procesar la acción de guardar el pedido
if (isset($_POST['save_order'])) {
    $userId = $_SESSION['user_id'];
    $username = $_SESSION['username'];

    if (!empty($_SESSION['cart'])) {
        // Variable para almacenar el cuerpo del correo
        $emailBody = "Usuario #$userId, $username solicitó:\n\n";
        $whatsappMessage = "Usuario: $username\nPedido:\n"; // Mensaje para WhatsApp

        foreach ($_SESSION['cart'] as $productId => $quantity) {
            // Obtener detalles del producto
            $query = "SELECT descripcion FROM productos WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("s", $productId);
            $stmt->execute();
            $stmt->bind_result($descripcion);
            $stmt->fetch();
            $stmt->close();

            // Agregar al cuerpo del correo
            $emailBody .= "Producto: $productId\nDescripción: $descripcion\nCantidad: $quantity\n\n";

            // Agregar a la variable de mensaje de WhatsApp
            $whatsappMessage .= "$productId: $descripcion - Cantidad: $quantity\n";

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

        // Enviar el correo
        /*$to = "sistemas@aamap.net";
        $subject = "PAPELERÍA AAMAP";
        $headers = "From: no-reply@aamap.net";

        if (mail($to, $subject, $emailBody, $headers)) {
            echo "<script>alert('Pedido guardado y correo enviado correctamente.');</script>";
        } else {
            echo "<script>alert('Pedido guardado, pero ocurrió un error al enviar el correo.');</script>";
        }*/

        // Enviar mensaje de WhatsApp
        $whatsappNumber = '+527831010939'; // El número de WhatsApp al que deseas enviar el mensaje
        send_whatsapp_message($whatsappNumber, $whatsappMessage); // Llamar a la función para enviar el mensaje de WhatsApp

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


// Procesar la acción de solicitud de producto
if (isset($_POST['request_product'])) {
    $userId = $_SESSION['user_id'];
    $productId = $_POST['product_id'];
    //var_dump($productId);
    //exit;

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
            alert('Solicitud realizada con éxito.');
            window.location.href='papeleria.php';
        </script>";
    } else {
        echo "<script>
            alert('El producto seleccionado no existe. Por favor, selecciona un producto válido.');
            window.location.href='papeleria.php';
        </script>";
    }
    exit;
}



//ocultar productos a op
$productos_ocultos = ['PROD000', 'PROD001', 'PROD002', 'PROD003'];

// Buscar productos
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
<?php if ($showModal): ?>
    <div id="welcomeModal" class="modal show">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Bienvenido, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
            </div>
            <div class="modal-body">
                <p>Inicio de sesi&oacute;n exitoso.</p>
            </div>
            <button class="close-btn" onclick="closeModal()">Cerrar</button>
        </div>
    </div>
<?php endif; ?>

<div class="sticky-header">
    <div class="header">
        <h1>USUARIO #<?php echo $_SESSION['user_id'] . ': ' . htmlspecialchars($_SESSION['username']); ?></h1>
    </div>
    <div class="container flex sopas">
        <form method="POST" action="">
            <button type="submit" name="save_order" class="btn btn-success push">Enviar pedido</button>
        </form>
        <!--<a href="solicitar_producto.php" class="btn btn-warning jiji">Solicitar</a>-->
        <a href="agregar_producto.php" class="btn btn-info jiji">Agregar Producto</a>
        <?php if($role === 'admin'): ?>
            <a href="pedidos.php" class="btn btn-secondary sec">Pedidos</a>
            <a href="cotizacion.php" class="btn btn-secondary sec">Cotizaci&oacute;n</a>
        <?php endif; ?>
        <div class="search-box">
            <form method="GET" action="">
                <input type="text" name="search" class="form-control" id="psearch" placeholder="Buscar..." value="<?php echo htmlspecialchars($search); ?>">
            </form>
        </div>
        <button type="submit" class="btn btn-primary mt-2" id="search">Buscar</button>
        <form method="POST" action="">
            <button type="submit" name="logout" class="logout-button push btn" id="logout">Cerrar sesi&oacute;n</button>
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
            // Consulta para verificar la solicitud y si ha pasado el tiempo (24 horas en este caso)
            $querySolicitud = "SELECT * FROM pedidos WHERE usuario_id = ? AND producto_id = ? AND tipo = 'solicitud' AND fecha > NOW() - INTERVAL 1 MINUTE";
            $stmtSolicitud = $conn->prepare($querySolicitud);
            $stmtSolicitud->bind_param("ss", $_SESSION['user_id'], $row['id']);
            $stmtSolicitud->execute();
            $solicitudExistente = $stmtSolicitud->get_result()->fetch_assoc();
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
                            <div>
                                <?php if ($solicitudExistente): ?>
                                    <!-- Botón "Solicitud Personalizada" -->
                                    <a href="solicitar_producto.php?product_id=<?php echo $row['id']; ?>" class="btn btn-secondary btncard">Solicitud Personalizada</a>
                                <?php else: ?>
                                    <!-- Botón "Solicitar" -->
                                    <form method="post" action="" onsubmit="return confirm('¿Estás seguro de que deseas solicitar este producto?');">
                                        <input type="hidden" name="product_id" value="<?php echo $row['id']; ?>">
                                        <button type="submit" name="request_product" class="btn btn-warning btncard">Solicitar</button>
                                    </form>
                                <?php endif; ?>
                            </div>
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
