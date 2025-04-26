<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: /login.php");
    exit();
}

require 'C:/xampp/htdocs/db_connect.php';
require 'C:/xampp/htdocs/role.php';

// Obtener parámetros de filtrado
$search = $_GET['search'] ?? '';
$estatus_filter = $_GET['estatus'] ?? 'todos';
$proveedor_filter = $_GET['proveedor'] ?? '';
$fecha_inicio = $_GET['fecha_inicio'] ?? '';
$fecha_fin = $_GET['fecha_fin'] ?? '';

// Consulta base para órdenes de compra
$query = "SELECT 
            oc.id_oc, 
            oc.folio, 
            oc.fecha_solicitud, 
            oc.estatus, 
            oc.total,
            pr.empresa as proveedor,
            COUNT(d.id_detalle) as items,
            of.id_fab,
            p.nombre as proyecto_nombre,
            p.cod_fab,
            u.nombre as solicitante_nombre
          FROM ordenes_compra oc
          LEFT JOIN proveedores pr ON oc.id_pr = pr.id_pr
          LEFT JOIN detalle_orden_compra d ON oc.id_oc = d.id_oc
          LEFT JOIN orden_fab of ON oc.id_fab = of.id_fab
          LEFT JOIN proyectos p ON of.id_proyecto = p.cod_fab
          LEFT JOIN users u ON oc.solicitante = u.id
          WHERE oc.activo = TRUE";

// Aplicar filtros
if (!empty($search)) {
    $query .= " AND (oc.folio LIKE '%" . $conn->real_escape_string($search) . "%' 
                OR pr.empresa LIKE '%" . $conn->real_escape_string($search) . "%')";
}

if ($estatus_filter != 'todos') {
    $query .= " AND oc.estatus = '" . $conn->real_escape_string($estatus_filter) . "'";
}

if (!empty($proveedor_filter)) {
    $query .= " AND oc.id_pr = " . intval($proveedor_filter);
}

if (!empty($fecha_inicio) && !empty($fecha_fin)) {
    $query .= " AND DATE(oc.fecha_solicitud) BETWEEN '" . $conn->real_escape_string($fecha_inicio) . "' 
                AND '" . $conn->real_escape_string($fecha_fin) . "'";
}

$query .= " GROUP BY oc.id_oc ORDER BY oc.fecha_solicitud DESC";

$result = $conn->query($query);
$ordenes = $result->fetch_all(MYSQLI_ASSOC);

// Obtener proveedores para filtro
$proveedores = $conn->query("SELECT id_pr, empresa FROM proveedores WHERE activo = TRUE")->fetch_all(MYSQLI_ASSOC);

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Órdenes de Compra</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="/ERP/stprojects.css">
    <link rel="icon" href="/assets/logo.ico">
    <style>
        .card-oc {
            transition: all 0.3s ease;
            margin-bottom: 20px;
            border-radius: 10px;
            overflow: hidden;
        }
        .card-oc:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transform: translateY(-5px);
        }
        .badge-estatus {
            font-size: 0.9rem;
            padding: 5px 10px;
        }
        .filter-section {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .oc-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .oc-proyecto {
            font-size: 0.9rem;
            color: #6c757d;
        }
        .oc-actions {
            margin-top: 10px;
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
                <form method="GET" action="ver_ordenes_compra.php" class="form-inline" style="margin-right: 10px;" autocomplete="off">
                    <div class="input-group">
                        <?php if(isset($_GET['search']) && !empty($_GET['search'])): ?>
                            <a href="ver_ordenes_compra.php" class="input-group-prepend" title="Cancelar búsqueda" style="display: flex; align-items: center; padding: 0 5px;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" style="margin-right: 5px;">
                                    <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                                    <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/>
                                </svg>
                            </a>
                        <?php endif; ?>
                        <input type="text" name="buscar_oc_aamap" class="form-control" id="psearch" 
                            placeholder="Buscar OC..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" style="width: 200px;" autocomplete="off">
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
                <a href="crear_orden_compra.php" class="btn btn-success chompa">Nueva OC</a>
                <a href="/ERP/all_projects.php" class="btn btn-secondary chompa">Regresar</a>
            </div>
        </div>
    </div>
</div>

<div class="container">
    <!-- Filtros adicionales -->
    <div class="filter-section">
        <form method="GET" action="ver_ordenes_compra.php">
            <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
            <div class="form-row">
                <div class="col-md-3">
                    <label for="estatus">Estatus:</label>
                    <select name="estatus" id="estatus" class="form-control">
                        <option value="todos" <?php echo ($estatus_filter == 'todos') ? 'selected' : ''; ?>>Todos los estatus</option>
                        <option value="solicitada" <?php echo ($estatus_filter == 'solicitada') ? 'selected' : ''; ?>>Solicitadas</option>
                        <option value="aprobada" <?php echo ($estatus_filter == 'aprobada') ? 'selected' : ''; ?>>Aprobadas</option>
                        <option value="pagada" <?php echo ($estatus_filter == 'pagada') ? 'selected' : ''; ?>>Pagadas</option>
                        <option value="recibida" <?php echo ($estatus_filter == 'recibida') ? 'selected' : ''; ?>>Recibidas</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="proveedor">Proveedor:</label>
                    <select name="proveedor" id="proveedor" class="form-control">
                        <option value="">Todos los proveedores</option>
                        <?php foreach ($proveedores as $prov): ?>
                            <option value="<?php echo $prov['id_pr']; ?>" <?php echo ($proveedor_filter == $prov['id_pr']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($prov['empresa']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="fecha_inicio">Fecha Inicio:</label>
                    <input type="date" name="fecha_inicio" id="fecha_inicio" class="form-control" value="<?php echo htmlspecialchars($fecha_inicio); ?>">
                </div>
                <div class="col-md-3">
                    <label for="fecha_fin">Fecha Fin:</label>
                    <input type="date" name="fecha_fin" id="fecha_fin" class="form-control" value="<?php echo htmlspecialchars($fecha_fin); ?>">
                </div>
            </div>
            <div class="form-row mt-2">
                <div class="col-md-12 text-right">
                    <button type="submit" class="btn btn-primary">Filtrar</button>
                    <a href="ver_ordenes_compra.php" class="btn btn-link">Limpiar</a>
                </div>
            </div>
        </form>
    </div>

    <div class="oc-header">
        <h2>Órdenes de Compra</h2>
        <div class="text-muted">Total: <?php echo count($ordenes); ?> registros</div>
    </div>

    <div class="row">
        <?php if (!empty($ordenes)): ?>
            <?php foreach ($ordenes as $oc): 
                // Determinar color según estatus
                $badge_class = '';
                switch($oc['estatus']) {
                    case 'solicitada': $badge_class = 'bg-warning text-dark'; break;
                    case 'aprobada': $badge_class = 'bg-info'; break;
                    case 'pagada': $badge_class = 'bg-primary'; break;
                    case 'recibida': $badge_class = 'bg-success'; break;
                    default: $badge_class = 'bg-secondary';
                }
            ?>
                <div class="col-md-6">
                    <div class="card card-oc">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h5 class="card-title"><?php echo htmlspecialchars($oc['folio']); ?></h5>
                                    <h6 class="card-subtitle mb-2 text-muted">
                                        <?php echo htmlspecialchars($oc['proveedor']); ?>
                                    </h6>
                                </div>
                                <span class="badge badge-estatus <?php echo $badge_class; ?>">
                                    <?php echo ucfirst($oc['estatus']); ?>
                                </span>
                            </div>
                            
                            <div class="card-text mt-3">
                                <div><strong>Solicitante:</strong> <?php echo htmlspecialchars($oc['solicitante_nombre']); ?></div>
                                <div><strong>Fecha:</strong> <?php echo date('d/m/Y', strtotime($oc['fecha_solicitud'])); ?></div>
                                <div><strong>Artículos:</strong> <?php echo $oc['items']; ?></div>
                                <div><strong>Total:</strong> $<?php echo number_format($oc['total'], 2); ?></div>
                                
                                <?php if (!empty($oc['id_fab'])): ?>
                                    <div class="oc-proyecto mt-2">
                                        <strong>Proyecto/OF:</strong> 
                                        <?php echo htmlspecialchars($oc['proyecto_nombre'] ?? 'OF-' . $oc['id_fab']); ?>
                                        (<?php echo htmlspecialchars($oc['cod_fab'] ?? ''); ?>)
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="oc-actions">
                                <a href="editar_orden_compra.php?id=<?php echo $oc['id_oc']; ?>" class="btn btn-sm btn-primary">Detalles</a>
                                <?php if ($oc['estatus'] == 'solicitada' && ($role == 'admin' || $role == 'compras')): ?>
                                    <a href="aprobar_oc.php?id=<?php echo $oc['id_oc']; ?>" class="btn btn-sm btn-success">Aprobar</a>
                                <?php endif; ?>
                                <?php if ($oc['estatus'] == 'aprobada' && $role == 'admin'): ?>
                                    <a href="recepcion_oc.php?id=<?php echo $oc['id_oc']; ?>" class="btn btn-sm btn-info">Registrar Recepción</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-info">No se encontraron órdenes de compra con los filtros aplicados</div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script>
    // Actualizar filtros al cambiar fechas
    $(document).ready(function() {
        $('#estatus, #proveedor').change(function() {
            $(this).closest('form').submit();
        });
    });
</script>
</body>
</html>