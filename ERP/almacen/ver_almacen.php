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
          WHERE ia.activo = TRUE and ia.existencia > 0"; //mostrar solo existencia > 0  and ia.existencia > 0

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
            background-color: #343a40;
            color: white;
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
        .stock-low {
            background-color:rgb(250, 255, 116) !important;  /* Rojo claro */
        }
        .stock-null {
            background-color: #ffdddd !important;  /* Rojo claro */
        }
        .stock-low td:nth-child(4) {  /* Celda de stock */
            color:rgb(220, 181, 53);  /* Rojo */
            font-weight: bold;
        }
         .stock-null td:nth-child(4) {  /* Celda de stock */
            color: #dc3545;  /* Rojo */
            font-weight: bold;
        }
        .stock-ok {
            background-color: #f8f9fa;  /* Gris claro por defecto */
        }
        .stock-high {
            background-color: #ddffdd !important;  /* Verde claro */
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
<div class="navbar" style="display: flex; align-items: center; justify-content: space-between; padding: 0px; background-color: #f8f9fa; position: relative;">
    <!-- Logo -->
    <img src="/assets/grupo_aamap.webp" alt="Logo AAMAP" style="width: 18%; position: absolute; top: 25px; left: 10px;">

    <!-- Contenedor de elementos alineados a la derecha -->
    <div class="sticky-header" style="width: 100%;">
        <div class="container" style="display: flex; justify-content: flex-end; align-items: center;">
            <div style="position: absolute; top: 90px; left: 600px;"><p style="font-size: 2.5em; font-family: 'Verdana';"><b>E R P</b></p></div>
            <!-- Buscador y botones -->
            <div style="display: flex; align-items: center; gap: 10px; flex-wrap: nowrap;">
                <!-- Buscador -->
                <form method="GET" action="ver_almacen.php" class="form-inline" style="margin-right: 10px;">
                    <div class="input-group">
                        <?php if(isset($_GET['search']) && !empty($_GET['search'])): ?>
                            <a href="ver_almacen.php" class="input-group-prepend" title="Cancelar búsqueda" style="display: flex; align-items: center; padding: 0 5px;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" style="margin-right: 5px;">
                                    <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                                    <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/>
                                </svg>
                            </a>
                        <?php endif; ?>
                        <input type="text" name="search" class="form-control" id="psearch" 
                            placeholder="Buscar artículos..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" style="width: 200px;">
                        <div class="input-group-append">
                            <button type="submit" class="btn btn-outline-secondary" title="Buscar">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                    <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </form>
                
                <!-- Botones -->
                <a href="reg_articulo_alm.php" class="btn btn-success chompa">Nuevo Artículo</a>
                <?php if($role === 'admin'): ?>
                    <a href="historico_movs_alm.php" class="btn btn-info chompa">Ver Movimientos</a>
                <?php endif; ?>
                <a href="salida_manual.php" class="btn btn-info chompa">Salida Manual</a>
                <a href="entrada_manual.php" class="btn btn-info chompa">Entrada Manual</a>
                <a href="/ERP/all_projects.php" class="btn btn-secondary chompa">Regresar</a>
            </div>
        </div>
    </div>
</div>

<div class="table-container">
    <!-- Filtros adicionales -->
    <div class="filter-section">
        <form method="GET" action="ver_almacen.php">
            <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
            <div class="form-row">
                <div class="col-md-4">
                    <label for="categoria">Categoría:</label>
                    <select name="categoria" id="categoria" class="form-control">
                        <option value="">Todas las categorías</option>
                        <?php foreach ($categorias as $cat): ?>
                            <option value="<?php echo $cat['id_cat_alm']; ?>" <?php echo ($categoria_filter == $cat['id_cat_alm']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['categoria']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4" style="display: flex; align-items: flex-end;">
                    <button type="submit" class="btn btn-primary">Filtrar</button>
                    <a href="ver_almacen.php" class="btn btn-link">Limpiar</a>
                </div>
            </div>
        </form>
    </div>

    <div class="header-buttons">
        <h2>Inventario de Almacén</h2>
        <div>
            <div class="text-muted">Total: <?php echo count($articulos); ?> registros</div>
            <span class="badge badge-danger">Stock nulo</span>
            <span class="badge badge-warning">Stock bajo</span>
            <span class="badge badge-success">Stock suficiente</span>
        </div>
    </div>
    
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
                <th>Entradas</th>
                <th>Salidas</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($articulos)): ?>
                <?php foreach ($articulos as $art): 
                    $stock_disponible = $art['existencia'];
                    $min_stock = $art['min_stock'] ?? 0;
                    
                    if ($stock_disponible < $min_stock and $stock_disponible > 0 ) {
                        $stock_class = 'stock-low';
                    } elseif ($stock_disponible > ($art['max_stock'] ?? $min_stock * 2)) {
                        $stock_class = 'stock-high';
                    }
                     elseif ($stock_disponible == 0) {
                        $stock_class = 'stock-null';
                    } else {
                        $stock_class = 'stock-ok';
                    }
                ?>
                    <tr class="<?php echo $stock_class; ?>">
                        <td id="code"><?php echo htmlspecialchars($art['codigo']); ?></td>
                        <td><?php echo htmlspecialchars($art['descripcion']); ?></td>
                        <td><?php echo htmlspecialchars($art['categoria'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($art['existencia']); ?></td>
                        <td><?php echo htmlspecialchars($art['min_stock']); ?></td>
                        <td><?php echo htmlspecialchars($art['max_stock']); ?></td>
                        <td><?php echo htmlspecialchars($art['unidad_medida']); ?></td>
                        <td><?php echo htmlspecialchars($art['entradas'] ?? 0); ?></td>
                        <td><?php echo htmlspecialchars($art['salidas'] ?? 0); ?></td>
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