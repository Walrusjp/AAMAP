<?php
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

require 'C:/xampp/htdocs/db_connect.php';
require 'C:/xampp/htdocs/role.php';

$search = $_GET['search'] ?? '';

// Obtener todos los productos directos con información del cliente
$query = "SELECT pd.*, cp.nombre_comercial as cliente_nombre 
          FROM productos_p_directas pd
          JOIN clientes_p cp ON pd.id_cliente = cp.id";

if (!empty($search)) {
    $query .= " WHERE pd.codigo LIKE '%" . $conn->real_escape_string($search) . "%' 
                OR pd.descripcion LIKE '%" . $conn->real_escape_string($search) . "%' 
                OR cp.nombre_comercial LIKE '%" . $conn->real_escape_string($search) . "%'";
}

$query .= " ORDER BY pd.id_pd DESC";

$result = $conn->query($query);
$productos = $result->fetch_all(MYSQLI_ASSOC);
$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Productos Directos</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="stprojects.css">
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
            <!-- Buscador, Filtro y botones -->
            <div style="display: flex; align-items: center; gap: 10px; flex-wrap: nowrap;">
                <!-- Buscador -->
                <form method="GET" action="ver_prod_directos.php" class="form-inline" style="margin-right: 10px;">
                    <div class="input-group">
                        <?php if(isset($_GET['search']) && !empty($_GET['search'])): ?>
                            <a href="ver_prod_directos.php" class="input-group-prepend" title="Cancelar búsqueda" style="display: flex; align-items: center; padding: 0 5px;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" style="margin-right: 5px;">
                                    <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                                    <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/>
                                </svg>
                            </a>
                        <?php endif; ?>
                        <input type="text" name="search" class="form-control" id="psearch" 
                            placeholder="Buscar productos..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" style="width: 200px;">
                        <div class="input-group-append">
                            <button type="submit" class="btn btn-outline-secondary" title="Buscar">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                    <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </form>
                
                <!-- Filtro -->
                <div style="display: flex; align-items: center;">
                    <label for="filter" style="margin-right: 10px;">Filtrar:</label>
                    <select id="filter" class="form-control" style="width: auto;">
                        <option value="todos">Todos</option>
                        <option value="maq">Maquila</option>
                        <option value="man">Manufacturado</option>
                        <option value="com">Comercial</option>
                    </select>
                </div>
                
                <!-- Botones -->
                <a href="reg_prod_direct.php" class="btn btn-success chompa">Agregar Producto</a>
                <a href="all_projects.php" class="btn btn-secondary chompa">Regresar</a>
            </div>
        </div>
    </div>
</div>

<div class="table-container">
    <div class="header-buttons">
        <h2>Productos Directos</h2>
    </div>
    
    <table class="table table-striped table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th>Código</th>
                <th>Descripción</th>
                <th>UM</th>
                <th>Precio Unitario</th>
                <th>Cliente</th>
                <th>Proceso</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($productos)): ?>
                <?php foreach ($productos as $producto): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($producto['id_pd']); ?></td>
                        <td><?php echo htmlspecialchars($producto['codigo']); ?></td>
                        <td><?php echo htmlspecialchars($producto['descripcion']); ?></td>
                        <td><?php echo htmlspecialchars($producto['um']); ?></td>
                        <td>$<?php echo number_format($producto['precio_unitario'], 2); ?></td>
                        <td><?php echo htmlspecialchars($producto['cliente_nombre']); ?></td>
                        <td>
                            <?php 
                                switch($producto['proceso']) {
                                    case 'maq': echo 'Maquila'; break;
                                    case 'man': echo 'Manufacturado'; break;
                                    case 'com': echo 'Comercial'; break;
                                    default: echo htmlspecialchars($producto['proceso']);
                                }
                            ?>
                        </td>
                        <td>
                            <a href="editar_prod_directo.php?id=<?php echo $producto['id_pd']; ?>" class="btn btn-primary btn-sm btn-action">Editar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8" class="text-center">No hay productos directos registrados</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script>
    // Filtrado de la tabla
    $(document).ready(function() {
        $('#filter').change(function() {
            var filter = $(this).val();
            
            if (filter === 'todos') {
                $('tbody tr').show();
            } else {
                $('tbody tr').each(function() {
                    var proceso = $(this).find('td:nth-child(7)').text().toLowerCase();
                    if (proceso.includes(filter)) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            }
        });
    });
</script>
</body>
</html>