<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Conectar a la base de datos
require 'C:\xampp\htdocs\db_connect.php';
require 'C:\xampp\htdocs\role.php';

// Inicializar filtro
$filtro_tipo = isset($_GET['tipo']) ? $_GET['tipo'] : 'todos';

// Construir la consulta según el filtro seleccionado
$query = "SELECT 
            p.id AS pedido_id,
            u.username,
            pr.id AS producto_id,
            pr.descripcion,
            p.cantidad,
            p.fecha
          FROM pedidos p
          JOIN users u ON p.usuario_id = u.id
          JOIN productos pr ON p.producto_id = pr.id";

if ($filtro_tipo === 'salidas') {
    $query .= " WHERE p.tipo = 'salida'";
} elseif ($filtro_tipo === 'solicitudes') {
    $query .= " WHERE p.tipo = 'solicitud'";
}

$query .= " ORDER BY p.fecha DESC";

$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedidos</title>
    <style type="text/css">
        #back {
            padding: 5px 10px;
            position: absolute;
            top: 40px;
            left: 80px;
            border-radius: 4px;
            border: none;
            cursor: pointer;
        }
    </style>
    <!-- Incluir Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php if ($role === 'admin'): ?>
    <div class="container mt-5">
        <h1>Lista de Pedidos</h1>

        <!-- Botones de filtro -->
        <div class="mb-3">
            <a href="papeleria.php" class="btn btn-danger" id="">REGRESAR</a>
            <a href="?tipo=todos" class="btn btn-primary <?php echo $filtro_tipo === 'todos' ? 'active' : ''; ?>">Todos</a>
            <a href="?tipo=salidas" class="btn btn-secondary <?php echo $filtro_tipo === 'salidas' ? 'active' : ''; ?>">Salidas</a>
            <a href="?tipo=solicitudes" class="btn btn-secondary <?php echo $filtro_tipo === 'solicitudes' ? 'active' : ''; ?>">Solicitudes</a>
        </div>

        <!-- Tabla de pedidos -->
        <?php if ($result->num_rows > 0): ?>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID del Pedido</th>
                        <th>Usuario</th>
                        <th>ID del Producto</th>
                        <th>Descripción</th>
                        <th>Cantidad</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['pedido_id']); ?></td>
                            <td><?php echo htmlspecialchars($row['username']); ?></td>
                            <td><?php echo htmlspecialchars($row['producto_id']); ?></td>
                            <td><?php echo htmlspecialchars($row['descripcion']); ?></td>
                            <td><?php echo htmlspecialchars($row['cantidad']); ?></td>
                            <td><?php echo htmlspecialchars($row['fecha']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="alert alert-warning" role="alert">
                No hay pedidos registrados.
            </div>
        <?php endif; ?>
    </div>

    <!-- Incluir Bootstrap JS y dependencias (opcional) -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <?php 
    //endif;
    else:
        echo "NO TIENE PERMISOS PARA VER ESTA PÁGINA";
        echo '<br><a href= papeleria.php>regresar</a>';
    endif;
    ?>
</body>
</html>

<?php
// Cerrar la conexión
$conn->close();
?>
