<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}
include 'db_connect.php';
include 'role.php';

// Consulta para obtener la información deseada, incluyendo la columna presentación
$query = "
SELECT 
    pr.imagen,
    pr.descripcion,
    pr.presentacion,  -- Agregamos la columna presentación
    SUM(p.cantidad) AS total_cantidad
FROM 
    pedidos p
JOIN 
    productos pr ON p.producto_id = pr.id
GROUP BY 
    p.producto_id, pr.imagen, pr.descripcion, pr.presentacion;  -- Incluimos presentación en el GROUP BY
";

$result = $conn->query($query);

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Resumen de Pedidos</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php if ($role === 'admin'): ?>
<div class="container mt-5">
    <h1 class="text-center mb-4">Resumen de Pedidos</h1>

    <?php if ($result->num_rows > 0): ?>
        <table class="table table-bordered table-hover">
            <thead class="thead-light">
                <tr>
                    <th>Imagen</th>
                    <th>Descripción</th>
                    <th>Presentación</th>  <!-- Nueva columna para presentación -->
                    <th>Cantidad Total</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <img src="<?php echo htmlspecialchars($row['imagen']); ?>" alt="<?php echo htmlspecialchars($row['descripcion']); ?>" style="width:100px; height:auto;">
                        </td>
                        <td><?php echo htmlspecialchars($row['descripcion']); ?></td>
                        <td><?php echo htmlspecialchars($row['presentacion']); ?></td>  <!-- Mostramos la presentación -->
                        <td><?php echo htmlspecialchars($row['total_cantidad']); ?></td>
                    </tr>
                <?php endwhile; ?>
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
//endif;
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
