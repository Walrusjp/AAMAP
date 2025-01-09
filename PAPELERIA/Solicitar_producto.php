<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

include 'db_connect.php';

$error = ""; // Variable para almacenar errores
$productExists = null; // Variable para comprobar si el producto existe

// Obtener el ID y la descripción del producto desde la URL
if (isset($_GET['product_id']) && isset($_GET['product_desc'])) {
    $productCode = $_GET['product_id'];
    $productDesc = urldecode($_GET['product_desc']); // Decodificar la descripción

    // Verificar si el producto existe
    include 'db_connect.php'; // Asegúrate de tener la conexión a la base de datos
    $query = "SELECT id, descripcion FROM productos WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $productCode);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();

    // Si el producto existe, mostrarlo en el formulario
    if ($product) {
        $productExists = true;
    } else {
        $productExists = false;
    }

    $stmt->close();
}

// Verificar si el formulario fue enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['submit_request'])) {
        // Obtener la cantidad y la justificación
        $quantity = $_POST['quantity'];
        $justification = $_POST['justification'];

        // Validar la cantidad
        if ($quantity > 0 && !empty($justification)) {
            // Guardar la solicitud personalizada en la base de datos como "personalizado"
            $userId = $_SESSION['user_id']; // ID del usuario desde la sesión
            $query = "INSERT INTO pedidos (usuario_id, producto_id, cantidad, tipo, justificacion, fecha) VALUES (?, ?, ?, 'personalizado', ?, NOW())";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ssss", $userId, $product['id'], $quantity, $justification);
            $stmt->execute();
            $stmt->close();
            echo "<script>alert('Solicitud personalizada guardada correctamente.');</script>";
            echo "<script>window.location.href = 'papeleria.php?t=' + Date.now();</script>";
        } else {
            $error = "La cantidad debe ser mayor a 0 y la justificación no puede estar vacía.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitar Producto Personalizado</title>
    <link rel="icon" href="assets/logo.ico" >
    <link rel="stylesheet" type="text/css" href="styles.css">
    <link rel="stylesheet" type="text/css" href="solstyles.css">
</head>
<body>
    <div class="container">
        <h2>Formulario de Solicitud Personalizada</h2>

        <?php if ($productExists): ?>
            <p>Solicitud personalizada para el producto: <strong><?php echo $product['id'] . " - " . $product['descripcion']; ?></strong></p>
        <?php else: ?>
            <p style="color: red;">El producto no existe.</p>
        <?php endif; ?>

        <!-- Formulario para solicitar producto personalizado -->
        <form method="POST" action="">
            <div class="input-group">
                <label>Cantidad:</label>
                <input type="number" name="quantity" required min="1">
            </div>

            <div class="input-group">
                <label>Justificación:</label>
                <textarea name="justification" required></textarea>
            </div>

            <button type="submit" name="submit_request" class="btn btn-success">Solicitar</button>
        </form>

        <!-- Botón para regresar a papeleria.php -->
        <form method="GET" action="papeleria.php">
            <button type="submit" class="btn btn-secondary" id="reback">Regresar</button>
        </form>

        <!-- Mostrar el alert si hay un error -->
        <?php if (!empty($error)): ?>
            <script>
                alert("<?php echo $error; ?>");
            </script>
        <?php endif; ?>

    </div>
</body>
</html>
