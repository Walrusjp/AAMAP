<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}
require 'C:\xampp\htdocs\db_connect.php';
require 'C:\xampp\htdocs\role.php';

// Configurar locale para español de México
setlocale(LC_TIME, 'es_MX.utf8', 'es_MX', 'spanish', 'es_ES', 'es');

// Obtener el nombre del mes en español
$mes_actual = strftime('%B');

// Array de productos excluidos
$productos_excluidos = ['PROD000'];

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
    p.producto_id
FROM 
    pedidos p
JOIN 
    productos pr ON p.producto_id = pr.id
WHERE 
    p.tipo IN ('solicitud', 'personalizado')";

if ($fecha_inicio && $fecha_fin) {
    $query .= " AND p.fecha >= '" . $conn->real_escape_string($fecha_inicio . " 00:00:00") . "' 
                AND p.fecha <= '" . $conn->real_escape_string($fecha_fin . " 23:59:59") . "'";
}

if (!empty($productos_excluidos)) {
    $placeholders = implode(',', array_fill(0, count($productos_excluidos), '?'));
    $query .= " AND p.producto_id NOT IN ($placeholders)";
}

$query .= " GROUP BY pr.imagen, pr.descripcion, pr.presentacion, p.producto_id";

$stmt = $conn->prepare($query);

if (!empty($productos_excluidos)) {
    $types = str_repeat('s', count($productos_excluidos));
    $stmt->bind_param($types, ...$productos_excluidos);
}

$stmt->execute();
$result = $stmt->get_result();
$pedidos_filtrados = $result->fetch_all(MYSQLI_ASSOC);
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
            /* Oculta elementos al imprimir */
    @media print {
        /* Oculta el botón de regresar */
        #back {
            display: none !important;
        }
        
        /* Oculta el formulario de filtros */
        form.mb-4 {
            display: none !important;
        }
        
        /* Opcional: Ajusta márgenes para mejor impresión */
        body {
            padding: 0;
            margin: 0;
        }
        
        /* Opcional: Evita que las imágenes se corten en varias páginas */
        img {
            max-width: 100%;
            page-break-inside: avoid;
        }
        
        /* Opcional: Evita que las filas de la tabla se dividan en páginas */
        table {
            page-break-inside: auto;
        }
        tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }
    }
    </style>
    <title>Resumen de Pedidos</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php if ($role === 'admin'): ?>
    <a href="papeleria.php" class="btn btn-danger" id="back">REGRESAR</a>
<div class="container mt-5">
    <h1 class="text-center mb-4">Requerimientos de papelería <?php echo strftime('%B'); ?></h1>

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
