<?php
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

require 'C:/xampp/htdocs/db_connect.php';
require 'C:/xampp/htdocs/role.php';

// Array de clientes permitidos (IDs)
$clientes_permitidos = [114, 113];

// Obtener mensajes de sesión y limpiarlos inmediatamente
$success = "";
$error = "";

if (isset($_SESSION['form_success'])) {
    $success = $_SESSION['form_success'];
    unset($_SESSION['form_success']);
}

if (isset($_SESSION['form_error'])) {
    $error = $_SESSION['form_error'];
    unset($_SESSION['form_error']);
}

// Procesar el formulario al enviar
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $codigo = $_POST['codigo'];
    $descripcion = $_POST['descripcion'];
    $um = $_POST['um'];
    $proceso = $_POST['proceso'];
    $precio_unitario = $_POST['precio_unitario'];
    $id_cliente = $_POST['id_cliente'];

    // Validar que el cliente esté en la lista permitida
    if (!in_array($id_cliente, $clientes_permitidos)) {
        $_SESSION['form_error'] = "Cliente no permitido para registrar productos.";
    } else {
        // Insertar el producto en la base de datos
        $query = "INSERT INTO productos_p_directas (codigo, descripcion, um, proceso, precio_unitario, id_cliente) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssssdi", $codigo, $descripcion, $um, $proceso, $precio_unitario, $id_cliente);
        
        if ($stmt->execute()) {
            $_SESSION['form_success'] = "Producto registrado exitosamente.";
        } else {
            $_SESSION['form_error'] = "Error al registrar el producto: " . $conn->error;
        }
        $stmt->close();
    }

    // Redirección PRG (Post-Redirect-Get)
    header("Location: ".$_SERVER['PHP_SELF']);
    exit();
}

// Obtener lista de clientes permitidos para el select
$query_clientes = "SELECT id, nombre_comercial FROM clientes_p WHERE id IN (" . implode(',', $clientes_permitidos) . ")";
$result_clientes = $conn->query($query_clientes);
$clientes = $result_clientes->fetch_all(MYSQLI_ASSOC);
$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Registro de Productos Directos</title>
    <link rel="stylesheet" type="text/css" href="styles.css">
    <link rel="icon" href="/assets/logo.png" type="image/png">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script>
        // Eliminar el estado POST del historial
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }

        // Prevenir recarga con F5 o Ctrl+R (opcional)
        document.addEventListener('keydown', function(e) {
            if (e.key === 'F5' || (e.ctrlKey && e.key === 'r')) {
                e.preventDefault();
                setTimeout(function() {
                    window.location.href = window.location.href;
                }, 50);
            }
        });
    </script>
</head>
<body>
    <div class="login-container">
        <h2>Registrar Producto Directo</h2>
        <form method="POST" action="">
            <div class="input-group">
                <label>Código:</label>
                <input type="text" name="codigo" >
            </div>
            <div class="input-group">
                <label>Descripción:</label>
                <textarea name="descripcion" required></textarea>
            </div>
            <div class="input-group">
                <label>Unidad de Medida (UM):</label>
                <input type="text" name="um" required>
            </div>
            <div class="input-group">
                <label>Proceso:</label>
                <select name="proceso" required>
                    <option value="maq">Maquila</option>
                    <option value="man">Manufacturado</option>
                    <option value="com">Comercial</option>
                </select>
            </div>
            <div class="input-group">
                <label>Precio Unitario:</label>
                <input type="number" name="precio_unitario" step="0.01" >
            </div>
            <div class="input-group">
                <label>Cliente:</label>
                <select name="id_cliente" required>
                    <?php foreach ($clientes as $cliente): ?>
                        <option value="<?php echo $cliente['id']; ?>">
                            <?php echo htmlspecialchars($cliente['nombre_comercial']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <a href="ver_prod_directos.php" class="btn btn-secondary chompa">Regresar</a>
            <button type="submit" name="registrar">Guardar Producto</button>
        </form>

        <!-- Alertas de éxito o error -->
        <?php if (!empty($success)): ?>
            <script>
                setTimeout(function() {
                    alert("<?php echo addslashes($success); ?>");
                }, 100);
            </script>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <script>
                setTimeout(function() {
                    alert("<?php echo addslashes($error); ?>");
                }, 100);
            </script>
        <?php endif; ?>
    </div>
</body>
</html>
