<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: /login.php");
    exit();
}

require 'C:/xampp/htdocs/db_connect.php';
require 'C:/xampp/htdocs/role.php';

// Obtener parámetros de búsqueda
$search = $_GET['search'] ?? '';
$estado_filter = $_GET['estado'] ?? '';
$iso_filter = isset($_GET['iso']) ? $_GET['iso'] : '';

// Consulta base para proveedores
$query = "SELECT * FROM proveedores WHERE activo = TRUE";

// Aplicar filtros
if (!empty($search)) {
    $query .= " AND (empresa LIKE '%" . $conn->real_escape_string($search) . "%' 
                OR contacto LIKE '%" . $conn->real_escape_string($search) . "%'
                OR rfc LIKE '%" . $conn->real_escape_string($search) . "%')";
}

if (!empty($estado_filter)) {
    $query .= " AND estado = '" . $conn->real_escape_string($estado_filter) . "'";
}

if ($iso_filter !== '') {
    $query .= " AND iso = " . ($iso_filter === 'si' ? '1' : '0');
}

$query .= " ORDER BY empresa ASC";

$result = $conn->query($query);
$proveedores = $result->fetch_all(MYSQLI_ASSOC);

// Obtener estados únicos para filtro
$estados = $conn->query("SELECT DISTINCT estado FROM proveedores WHERE estado IS NOT NULL ORDER BY estado")->fetch_all(MYSQLI_ASSOC);

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Proveedores</title>
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
        .filter-section {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .iso-badge {
            font-size: 0.75rem;
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
                <form method="GET" action="ver_proveedores.php" class="form-inline" style="margin-right: 10px;">
                    <div class="input-group">
                        <?php if(isset($_GET['search']) && !empty($_GET['search'])): ?>
                            <a href="ver_proveedores.php" class="input-group-prepend" title="Cancelar búsqueda" style="display: flex; align-items: center; padding: 0 5px;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" style="margin-right: 5px;">
                                    <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                                    <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/>
                                </svg>
                            </a>
                        <?php endif; ?>
                        <input type="text" name="search" class="form-control" id="psearch" 
                            placeholder="Buscar proveedores..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" style="width: 200px;">
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
                <a href="registrar_proveedor.php" class="btn btn-success chompa">Nuevo Proveedor</a>
                <a href="/ERP/all_projects.php" class="btn btn-secondary chompa">Regresar</a>
            </div>
        </div>
    </div>
</div>

<div class="table-container">
    <!-- Filtros adicionales -->
    <div class="filter-section">
        <form method="GET" action="ver_proveedores.php">
            <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
            <div class="form-row">
                <div class="col-md-4">
                    <label for="estado">Estado:</label>
                    <select name="estado" id="estado" class="form-control">
                        <option value="">Todos los estados</option>
                        <?php foreach ($estados as $estado): ?>
                            <option value="<?php echo htmlspecialchars($estado['estado']); ?>" 
                                <?php echo ($estado_filter == $estado['estado']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($estado['estado']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="iso">Certificación ISO:</label>
                    <select name="iso" id="iso" class="form-control">
                        <option value="">Todos</option>
                        <option value="si" <?php echo ($iso_filter === 'si') ? 'selected' : ''; ?>>Con ISO</option>
                        <option value="no" <?php echo ($iso_filter === 'no') ? 'selected' : ''; ?>>Sin ISO</option>
                    </select>
                </div>
                <div class="col-md-4" style="display: flex; align-items: flex-end;">
                    <button type="submit" class="btn btn-primary">Filtrar</button>
                    <a href="ver_proveedores.php" class="btn btn-link">Limpiar</a>
                </div>
            </div>
        </form>
    </div>

    <div class="header-buttons">
        <h2>Proveedores Registrados</h2>
        <div class="text-muted">Total: <?php echo count($proveedores); ?> registros</div>
    </div>
    
    <table class="table table-striped table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th>Empresa</th>
                <th>Contacto</th>
                <th>RFC</th>
                <th>Teléfono</th>
                <th>Estado</th>
                <th>Certificaciones</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($proveedores)): ?>
                <?php foreach ($proveedores as $prov): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($prov['id_pr']); ?></td>
                        <td><?php echo htmlspecialchars($prov['empresa']); ?></td>
                        <td><?php echo htmlspecialchars($prov['contacto']); ?></td>
                        <td><?php echo htmlspecialchars($prov['rfc']); ?></td>
                        <td><?php echo htmlspecialchars($prov['telefono']); ?></td>
                        <td><?php echo htmlspecialchars($prov['estado']); ?></td>
                        <td>
                            <?php if ($prov['iso']): ?>
                                <span class="badge badge-success iso-badge">ISO</span>
                            <?php else: ?>
                                <span class="badge badge-secondary iso-badge">No</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="editar_proveedor.php?id=<?php echo $prov['id_pr']; ?>" class="btn btn-primary btn-sm btn-action">Editar</a>
                            <a href="ver_compras_proveedor.php?id=<?php echo $prov['id_pr']; ?>" class="btn btn-info btn-sm btn-action">Compras</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8" class="text-center">No hay proveedores registrados</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script>
    // Actualizar tabla al cambiar filtros
    $(document).ready(function() {
        $('#estado, #iso').change(function() {
            $(this).closest('form').submit();
        });
    });
</script>
</body>
</html>