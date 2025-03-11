<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}
require 'C:\xampp\htdocs\db_connect.php';
require 'C:\xampp\htdocs\role.php';

// Array de productos excluidos
$productos_excluidos = ['PROD000']; // Aquí van los ID de los productos que quieres excluir

// Recuperar las fechas del formulario
$fecha_inicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : '';
$fecha_fin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : '';

// Consulta para obtener todos los pedidos
$query = "
SELECT 
    pr.imagen,
    pr.descripcion,
    pr.presentacion,
    SUM(p.cantidad) AS total_cantidad,
    p.tipo,
    p.producto_id
FROM 
    pedidos p
JOIN 
    productos pr ON p.producto_id = pr.id
WHERE 
    p.tipo = 'solicitud'
GROUP BY 
    pr.imagen, pr.descripcion, pr.presentacion, p.producto_id, p.tipo
";

$result = $conn->query($query);

// Array de pedidos filtrados
$pedidos_filtrados = [];

// Filtrar los resultados en PHP
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Verificar condiciones: tipo solicitud, rango de fechas, exclusión de productos
        $fecha_valida = true;
        if ($fecha_inicio && $fecha_fin) {
            $fecha_valida = $row['fecha'] >= $fecha_inicio . " 00:00:00" && $row['fecha'] <= $fecha_fin . " 23:59:59";
        }

        // Verificar si el producto está en el array de excluidos
        if (!in_array($row['producto_id'], $productos_excluidos) && $row['tipo'] === 'solicitud' && $fecha_valida) {
            $pedidos_filtrados[] = $row;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
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
    <title>Resumen de Pedidos</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php if ($role === 'admin'): ?>
    <a href="papeleria.php" class="btn btn-danger" id="back">REGRESAR</a>
<div class="container mt-5">
    <h1 class="text-center mb-4">Requerimientos de papelería Enero</h1>

    <!-- Formulario para seleccionar rango de fechas -->
    <form method="get" class="mb-4">
        <div class="form-row">
            <div class="col">
                <label for="fecha_inicio">Fecha inicio:</label>
                <input type="date" id="fecha_inicio" name="fecha_inicio" class="form-control" value="<?php echo htmlspecialchars($fecha_inicio); ?>">
            </div>
            <div class="col">
                <label for="fecha_fin">Fecha fin:</label>
                <input type="date" id="fecha_fin" name="fecha_fin" class="form-control" value="<?php echo htmlspecialchars($fecha_fin); ?>">
            </div>
            <div class="col-auto align-self-end">
                <button type="submit" class="btn btn-primary">Filtrar</button>
            </div>
        </div>
    </form>

    <?php if (!empty($pedidos_filtrados)): ?>
        <table class="table table-bordered table-hover">
            <thead class="thead-light">
                <tr>
                    <th>Imagen</th>
                    <th>Descripción</th>
                    <th>Presentación</th>
                    <th>Cantidad Total</th>
                    <!--<th>Fecha</th>-->
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pedidos_filtrados as $pedido): ?>
                    <tr>
                        <td>
                            <img src="<?php echo htmlspecialchars($pedido['imagen']); ?>" alt="<?php echo htmlspecialchars($pedido['descripcion']); ?>" style="width:100px; height:auto;">
                        </td>
                        <td><?php echo htmlspecialchars($pedido['descripcion']); ?></td>
                        <td><?php echo htmlspecialchars($pedido['presentacion']); ?></td>
                        <td><?php echo htmlspecialchars($pedido['total_cantidad']); ?></td>
                        <!--<td><?php echo htmlspecialchars($pedido['fecha']); ?></td>-->
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="alert alert-warning" role="alert">
            No se encontraron resultados.
        </div>
    <?php endif; ?>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<?php 
else:
    echo "NO TIENE PERMISOS PARA VER ESTA PÁGINA";
    echo '<br><a href= papeleria.php>regresar</a>';
endif;
?>
</body>
</html>

<?php
$conn->close();
?>
