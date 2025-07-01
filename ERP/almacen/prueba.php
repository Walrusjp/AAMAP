<?php
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['username'])) {
    header("Location: /login.php");
    exit();
}

require 'C:/xampp/htdocs/db_connect.php';
require 'C:/xampp/htdocs/role.php';

$search = $_GET['search'] ?? '';
$categoria_filter = $_GET['categoria'] ?? '';
$proveedor_filter = $_GET['proveedor'] ?? '';

// Consulta base para artículos de almacén
$query = "SELECT ia.*, cp.categoria,
           (SELECT SUM(cantidad) FROM movimientos_almacen WHERE id_alm = ia.id_alm AND tipo_mov = 'entrada') as entradas,
           (SELECT SUM(cantidad) FROM movimientos_almacen WHERE id_alm = ia.id_alm AND tipo_mov = 'salida') as salidas
          FROM inventario_almacen ia
          LEFT JOIN categorias_almacen cp ON ia.id_cat_alm = cp.id_cat_alm
          WHERE ia.activo = TRUE";

// Aplicar filtros
if (!empty($search)) {
    $query .= " AND (ia.codigo LIKE '%" . $conn->real_escape_string($search) . "%' 
                OR ia.descripcion LIKE '%" . $conn->real_escape_string($search) . "%')";
}

if (!empty($categoria_filter)) {
    $query .= " AND ia.id_cat_alm = " . intval($categoria_filter);
}

$result = $conn->query($query);
$articulos = $result->fetch_all(MYSQLI_ASSOC);

// Obtener categorías y proveedores para filtros
$categorias = $conn->query("SELECT * FROM categorias_almacen WHERE 1")->fetch_all(MYSQLI_ASSOC);
$proveedores = $conn->query("SELECT * FROM proveedores WHERE activo = TRUE")->fetch_all(MYSQLI_ASSOC);

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Inventario de Almacén</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="/ERP/stprojects.css">
    <link rel="icon" href="/assets/logo.ico">
    <style>
        .table-container {
            margin: 20px auto;
            width: 95%;
            overflow-x: auto;
        }
        .table th {
            background-color:lightblue;
            color: black;
        }
        .btn-action {
            padding: 5px 10px;
            margin: 0 2px;
        }
        .header-buttons {
            margin: 20px 0;
            display: flex;
            justify-content: space-between;
        }
        .filter-section {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        #code {
            font-family: 'consolas';
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <table class="table table-striped table-bordered">
        <thead>
            <tr>
                <th>Código</th>
                <th>Descripción</th>
                <th>Categoría</th>
                <!--<th>Proveedor</th>-->
                <th>Stock</th>
                <th>Mín</th>
                <th>Máx</th>
                <th>UM</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($articulos)): ?>
                <?php foreach ($articulos as $art): 
                    $stock_disponible = $art['existencia'];
                    $min_stock = $art['min_stock'] ?? 0;
                    
                    if ($stock_disponible <= $min_stock) {
                        $stock_class = 'stock-low';
                    } elseif ($stock_disponible >= ($art['max_stock'] ?? $min_stock * 2)) {
                        $stock_class = 'stock-high';
                    } else {
                        $stock_class = 'stock-ok';
                    }
                ?>
                    <tr class="<?php echo $stock_class; ?>">
                        <td id="code"><?php echo htmlspecialchars($art['codigo']); ?></td>
                        <td><?php echo htmlspecialchars($art['descripcion']); ?></td>
                        <td><?php echo htmlspecialchars($art['categoria'] ?? 'N/A'); ?></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td><?php echo htmlspecialchars($art['unidad_medida']); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="11" class="text-center">No hay artículos registrados en el almacén</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script>
    $(document).ready(function() {
        // Filtros
        $('#categoria, #proveedor').change(function() {
            $(this).closest('form').submit();
        });

        // Ventana de movimientos
        $(document).on('click', '.btn-ver-movimientos', function() {
            const id_alm = $(this).data('id');
            window.location.href = `ver_movs_alm.php?id_alm=${id_alm}`;
        });
    });
</script>
</body>
</html>