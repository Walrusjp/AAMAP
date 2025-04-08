<?php
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

require 'C:/xampp/htdocs/db_connect.php';
require 'C:/xampp/htdocs/role.php';

// Obtener el ID del producto a editar
$id_pd = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Array de clientes permitidos (IDs)
$clientes_permitidos = [113, 114];

// Obtener información del producto a editar
$producto = [];
if ($id_pd > 0) {
    $query = "SELECT pd.*, cp.nombre_comercial as cliente_nombre 
              FROM productos_p_directas pd
              JOIN clientes_p cp ON pd.id_cliente = cp.id
              WHERE pd.id_pd = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $id_pd);
    $stmt->execute();
    $result = $stmt->get_result();
    $producto = $result->fetch_assoc();
    $stmt->close();
}

// Si no se encontró el producto, redirigir
if (empty($producto)) {
    header("Location: ver_prod_directos.php");
    exit();
}

// Procesar el formulario de actualización
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $codigo = $_POST['codigo'];
    $descripcion = $_POST['descripcion'];
    $um = $_POST['um'];
    $proceso = $_POST['proceso'];
    $precio_unitario = floatval($_POST['precio_unitario']);
    $id_cliente = intval($_POST['id_cliente']);

    // Validar que el cliente esté en la lista permitida
    if (!in_array($id_cliente, $clientes_permitidos)) {
        $error = "Cliente no permitido para actualizar productos.";
    } else {
        // Actualizar el producto en la base de datos
        $query = "UPDATE productos_p_directas SET 
                  codigo = ?, 
                  descripcion = ?, 
                  um = ?, 
                  proceso = ?, 
                  precio_unitario = ?, 
                  id_cliente = ?
                  WHERE id_pd = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssssdii", $codigo, $descripcion, $um, $proceso, $precio_unitario, $id_cliente, $id_pd);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Producto actualizado exitosamente.";
            header("Location: ver_prod_directos.php");
            exit();
        } else {
            $error = "Error al actualizar el producto: " . $conn->error;
        }
        $stmt->close();
    }
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
    <title>Editar Producto Directo</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="stprojects.css">
    <link rel="icon" href="/assets/logo.ico">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        .form-container {
            max-width: 800px;
            margin: 30px auto;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .form-group label {
            font-weight: bold;
        }
    </style>
</head>
<body>
<div class="navbar" style="display: flex; align-items: center; justify-content: space-between; padding: 0px; background-color: #f8f9fa; position: relative;">
    <!-- Logo -->
    <img src="/assets/grupo_aamap.webp" alt="Logo AAMAP" style="width: 18%; position: absolute; top: 25px; left: 10px;">

    <!-- Contenedor de elementos alineados a la derecha -->
    <div class="sticky-header" style="width: 100%;">
        <div class="container" style="display: flex; justify-content: flex-end; align-items: center;">
            <div style="position: absolute; top: 90px; left: 600px;"><p style="font-size: 2.5em; font-family: 'Verdana';"><b>E R P</b></p></div>
            <!-- Botones -->
            <div style="display: flex; align-items: center; gap: 10px;">
                <a href="ver_prod_directos.php" class="btn btn-secondary chompa">Regresar</a>
            </div>
        </div>
    </div>
</div>

<div class="form-container">
    <h2 class="text-center">Editar Producto Directo</h2>
    
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-group">
            <label for="codigo">Código:</label>
            <input type="text" class="form-control" id="codigo" name="codigo" 
                   value="<?php echo htmlspecialchars($producto['codigo']); ?>" required>
        </div>

        <div class="form-group">
            <label for="descripcion">Descripción:</label>
            <textarea class="form-control" id="descripcion" name="descripcion" 
                      rows="3" required><?php echo htmlspecialchars($producto['descripcion']); ?></textarea>
        </div>

        <div class="form-group">
            <label for="um">Unidad de Medida (UM):</label>
            <input type="text" class="form-control" id="um" name="um" 
                   value="<?php echo htmlspecialchars($producto['um']); ?>" required>
        </div>

        <div class="form-group">
            <label for="proceso">Proceso:</label>
            <select class="form-control" id="proceso" name="proceso" required>
                <option value="maq" <?php echo $producto['proceso'] == 'maq' ? 'selected' : ''; ?>>Maquila</option>
                <option value="man" <?php echo $producto['proceso'] == 'man' ? 'selected' : ''; ?>>Manufacturado</option>
                <option value="com" <?php echo $producto['proceso'] == 'com' ? 'selected' : ''; ?>>Comercial</option>
            </select>
        </div>

        <div class="form-group">
            <label for="precio_unitario">Precio Unitario:</label>
            <input type="number" step="0.01" class="form-control" id="precio_unitario" name="precio_unitario" 
                   value="<?php echo htmlspecialchars($producto['precio_unitario']); ?>" required>
        </div>

        <div class="form-group">
            <label for="id_cliente">Cliente:</label>
            <select class="form-control" id="id_cliente" name="id_cliente" required>
                <?php foreach ($clientes as $cliente): ?>
                    <option value="<?php echo $cliente['id']; ?>" 
                        <?php echo $producto['id_cliente'] == $cliente['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cliente['nombre_comercial']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group text-center">
            <button type="submit" class="btn btn-primary">Actualizar Producto</button>
            <a href="ver_prod_directos.php" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>