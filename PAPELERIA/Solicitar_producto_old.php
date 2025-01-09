<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}
include 'db_connect.php'; // Asegúrate de tener la conexión a la base de datos
include 'search_producto.php';

$error = ""; // Variable para almacenar errores
$addedProducts = isset($_SESSION['added_products']) ? $_SESSION['added_products'] : []; // Almacena los productos añadidos
$productExists = null; // Variable para comprobar si el producto existe
$quantityError = null; // Variable para error de cantidad

// Verificar si el formulario fue enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['add_to_table'])) {
        // Obtener el código de producto y la cantidad
        $productCode = $_POST['product_code'];
        $quantity = $_POST['quantity'];

        // Verificar si el código de producto existe en la base de datos
        $query = "SELECT id, descripcion, stock FROM productos WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $productCode);  // El código de producto es de tipo varchar
        $stmt->execute();
        $result = $stmt->get_result();
        $product = $result->fetch_assoc();
        
        if ($product) {
            $productExists = true; // El producto existe
            if ($quantity > 0) {
                // Agregar el producto a la lista de productos añadidos
                $addedProducts[] = [
                    'id' => $product['id'],
                    'codigo' => $productCode,
                    'descripcion' => $product['descripcion'],
                    'cantidad' => $quantity
                ];
                // Guardar los productos en la sesión
                $_SESSION['added_products'] = $addedProducts;
                // Limpiar los inputs
                unset($_POST['product_code']);
                unset($_POST['quantity']);
            } else {
                $quantityError = "La cantidad debe ser mayor a 0.";
            }
        } else {
            $productExists = false; // El producto no existe
        }

        $stmt->close();
    }

    if (isset($_POST['submit_request'])) {
        // Guardar los productos en la base de datos como "solicitud"
        if (!empty($addedProducts)) {
            $userId = $_SESSION['user_id']; // ID del usuario desde la sesión
            $query = "INSERT INTO pedidos (usuario_id, producto_id, cantidad, tipo) VALUES (?, ?, ?, 'solicitud')";
            $stmt = $conn->prepare($query);

            foreach ($addedProducts as $product) {
                // Cambiar el tipo de 'producto_id' a 's' ya que es un varchar en la base de datos
                $stmt->bind_param("sss", $userId, $product['codigo'], $product['cantidad']);
                $stmt->execute();
            }

            $stmt->close();
            // Limpiar la lista de productos añadidos
            unset($_SESSION['added_products']);
            echo "<script>alert('Solicitud guardada correctamente.');</script>";
            echo "<script>window.location.href = 'papeleria.php'</script>";
        } else {
            $error = "No se han añadido productos para la solicitud.";
        }
    }

    // Borrar un producto de la lista
    if (isset($_POST['remove_product'])) {
        $productCodeToRemove = $_POST['product_code_to_remove'];
        // Filtrar la lista para eliminar el producto seleccionado
        $addedProducts = array_filter($addedProducts, function($product) use ($productCodeToRemove) {
            return $product['codigo'] !== $productCodeToRemove;
        });

        // Reindexar el array para asegurar que el índice esté limpio (esto es importante para no tener índices vacíos)
        $addedProducts = array_values($addedProducts);

        // Guardar los cambios en la sesión
        $_SESSION['added_products'] = $addedProducts;
    }
}

?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitar Productos</title>
    <link rel="icon" href="assets/logo.ico" >
    <link rel="stylesheet" type="text/css" href="styles.css">
    <link rel="stylesheet" type="text/css" href="solstyles.css">
    <script src="solprod.js"></script>
</head>
<body>
    <div class="container">
        <h2>Formulario de Solicitud de Productos</h2>

        <!-- Formulario para solicitar producto -->
        <form method="POST" action="">
            <div class="input-group">
                <label>Código de Producto:</label>
                <input type="text"
                id="product_code"
                name="product_code" 
                required
                onkeyup="searchProduct(this.value)"
                autocomplete="off"
                value="<?php echo isset($_POST['product_code']) ? $_POST['product_code'] : ''; ?>">
                <div id="suggestions" style="border: 1px solid #ccc; max-height: 150px; overflow-y: auto; display: none; background-color: white;"></div>
            </div>

            <!-- Mensaje de error si el producto no existe -->
            <?php if ($productExists === false): ?>
                <p style="color: red;">El producto no existe.</p>
            <?php endif; ?>

            <div class="input-group">
                <label>Cantidad:</label>
                <input type="number" name="quantity" required min="1" value="<?php echo isset($_POST['quantity']) ? $_POST['quantity'] : ''; ?>">
            </div>

            <!-- Mensaje de error si la cantidad no es válida -->
            <?php if ($quantityError): ?>
                <p style="color: red;"><?php echo $quantityError; ?></p>
            <?php endif; ?>

            <button type="submit" name="add_to_table" class="btn btn-success">Agregar</button>
        </form>

        <h3>Productos añadidos para solicitud:</h3>
<table>
    <thead>
        <tr>
            <th>Código</th>
            <th>Descripción</th>
            <th>Cantidad</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody id="product_table_body">
        <?php if (!empty($addedProducts)): ?>
            <?php foreach ($addedProducts as $product): ?>
                <tr>
                    <td><?php echo $product['codigo']; ?></td>
                    <td><?php echo $product['descripcion']; ?></td>
                    <td><?php echo $product['cantidad']; ?></td>
                    <td>
                        <!-- Crear un formulario dentro del botón de borrar para enviar el código del producto -->
                        <form method="POST" action="">
                            <input type="hidden" name="product_code_to_remove" value="<?php echo $product['codigo']; ?>">
                            <button type="submit" name="remove_product" class="btn btn-danger">Borrar</button>
                        </form>
                    </td>

                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="4">No se ha añadido ningún producto.</td></tr>
        <?php endif; ?>
    </tbody>
</table>


        <!-- Botón para enviar la solicitud -->
        <form method="POST" action="">
            <button type="submit" name="submit_request" class="btn btn-primary">Solicitar</button>
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
