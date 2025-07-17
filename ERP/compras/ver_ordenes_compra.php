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

// Consulta base para órdenes de compra con el último estatus
$query = "SELECT 
            oc.id_oc, 
            oc.folio, 
            oc.fecha_solicitud,
            oc.descripcion_destino, 
            le.estatus, 
            le.observaciones, 
            (SELECT SUM(d2.cantidad * d2.precio_unitario) FROM detalle_orden_compra d2 
             WHERE d2.id_oc = oc.id_oc AND d2.activo = 1) as subtotal,
            (SELECT SUM(d2.cantidad * d2.precio_unitario) * 1.16 FROM detalle_orden_compra d2 
             WHERE d2.id_oc = oc.id_oc AND d2.activo = 1) as total,
            pr.empresa as proveedor,
            COUNT(d.id_detalle) as items,
            SUM(d.cantidad) as cantidad_total_pedida,
            SUM(d.recibido) as cantidad_recibida_total,
            of.id_fab,
            p.nombre as proyecto_nombre,
            p.cod_fab,
            u.username,
            u.nombre as solicitante_nombre
          FROM ordenes_compra oc
          LEFT JOIN proveedores pr ON oc.id_pr = pr.id_pr
          LEFT JOIN detalle_orden_compra d ON oc.id_oc = d.id_oc AND d.activo = 1
          LEFT JOIN orden_fab of ON oc.id_fab = of.id_fab
          LEFT JOIN proyectos p ON of.id_proyecto = p.cod_fab
          LEFT JOIN users u ON oc.solicitante = u.id
          LEFT JOIN (
              SELECT id_oc, estatus, observaciones 
              FROM logs_estatus_oc 
              WHERE (id_oc, fecha_cambio) IN (
                  SELECT id_oc, MAX(fecha_cambio) 
                  FROM logs_estatus_oc 
                  GROUP BY id_oc
              )
          ) le ON oc.id_oc = le.id_oc
          WHERE oc.activo = TRUE";

// Aplicar filtros
if (!empty($search)) {
    $query .= " AND (oc.folio LIKE '%" . $conn->real_escape_string($search) . "%' 
                OR pr.empresa LIKE '%" . $conn->real_escape_string($search) . "%')";
}

if ($estatus_filter != 'todos') {
    $query .= " AND le.estatus = '" . $conn->real_escape_string($estatus_filter) . "'";
}

if (!empty($proveedor_filter)) {
    $query .= " AND oc.id_pr = " . intval($proveedor_filter);
}

if (!empty($fecha_inicio) && !empty($fecha_fin)) {
    $query .= " AND DATE(oc.fecha_solicitud) BETWEEN '" . $conn->real_escape_string($fecha_inicio) . "' 
                AND '" . $conn->real_escape_string($fecha_fin) . "'";
}

// Solo un GROUP BY al final
$query .= " GROUP BY oc.id_oc, oc.folio, oc.fecha_solicitud, oc.descripcion_destino, le.estatus, le.observaciones, 
            pr.empresa, of.id_fab, p.nombre, p.cod_fab, u.username, u.nombre
            ORDER BY oc.fecha_solicitud DESC";

$result = $conn->query($query);
$ordenes = $result->fetch_all(MYSQLI_ASSOC);

// Obtener proveedores para filtro
$proveedores = $conn->query("SELECT id_pr, empresa FROM proveedores WHERE activo = TRUE")->fetch_all(MYSQLI_ASSOC);

function getEstadoBanderas($estatusActual) {
    $banderas = [
        'solicitada' => 'verde', // Por defecto en verde
        'autorizado' => 'amarillo',
        'pago' => 'rojo',
        'parcial' => 'rojo',
        'almacen' => 'rojo'
    ];
    
    switch($estatusActual) {
        case 'solicitada':
            // Por defecto ya está solicitado en verde, los demás en rojo
            break;
            
        case 'autorizado':
            $banderas['solicitada'] = 'verde';
            $banderas['autorizado'] = 'verde';
            $banderas['parcial'] = 'amarillo';
            $banderas['pago'] = 'amarillo';
            break;
        
        case 'pago':
        case 'parcial':
            $banderas['solicitada'] = 'verde';
            $banderas['autorizado'] = 'verde';
            $banderas['pago'] = 'verde';
            $banderas['parcial'] = 'verde';
            $banderas['almacen'] = 'amarillo';
            break;
            
        case 'almacen':
            $banderas['solicitada'] = 'verde';
            $banderas['autorizado'] = 'verde';
            $banderas['pago'] = 'verde';
            $banderas['parcial'] = 'verde';
            $banderas['almacen'] = 'verde';
            break;
            
        case 'rechazado':
            // Todos en rojo si se rechaza
            $banderas['solicitada'] = 'rojo';
            $banderas['autorizado'] = 'rojo';
            $banderas['pago'] = 'rojo';
            $banderas['parcial'] = 'rojo';
            $banderas['almacen'] = 'rojo';
            break;
    }
    
    return $banderas;
}

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
            color: rgb(19, 17, 17);
            position: absolute;  /* Posicionamiento absoluto */
            top: 10px;          /* Distancia desde arriba */
            right: 10px;        /* Distancia desde la derecha */
            z-index: 1;         /* Para que quede sobre otros elementos */
            font-family: 'Consolas';
        }
        .filter-section {
            padding: 10px 15px;
            background-color: #f8f9fa; /* Color de fondo opcional */
            border-bottom: 1px solid #dee2e6; /* Línea separadora opcional */
            margin-bottom: 0 !important; /* Elimina cualquier margen inferior */
        }

        /* Elimina márgenes/paddings innecesarios */
        .filter-section .form-row {
            margin-bottom: 0 !important;
        }

        .filter-section .form-control {
            padding: 0.375rem 0.75rem;
            height: calc(1.8125rem + 2px); /* Reduce altura de inputs */
        }

        .filter-section label {
            margin-bottom: 0.2rem; /* Reduce espacio bajo labels */
            font-size: 0.9rem; /* Tamaño de fuente más pequeño */
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
        .solicitada { background-color: gold; }
        .aprobada { background-color: lawngreen; }
        .pagada { background-color: cornflowerblue; }
        .recibida { background-color: mediumspringgreen; }
        .cancelada { background-color: lightcoral; }
        .banderas-estado {
            display: flex;
            flex-direction: column;
            gap: 5px;
            margin-top: 10px;
        }

        .card-estados {
            display: flex;
            justify-content: space-between;
        }
        
        .card-content {
            flex: 1;
            padding-right: 15px;
        }
        
        .banderas-container {
            display: flex;
            flex-direction: column;
            justify-content: center;
            width: 120px;
            padding-left: 15px;
            border-left: 1px solid #eee;
        }
        
        .bandera {
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 0.7rem;
            text-align: center;
            margin: 2px 0;
            width: 100%;
            box-sizing: border-box;
        }
        
        .bandera.amarillo {
            background-color: gold;
            color: #000;
        }
        
        .bandera.verde {
            background-color: #28a745;
            color: white;
        }
        
        .bandera.rojo {
            background-color: #dc3545;
            color: white;
        }
        
        .progreso-recepcion {
            height: 10px;
            background-color: #e9ecef;
            border-radius: 5px;
            margin-top: 5px;
        }
        
        .progreso-recepcion .barra {
            height: 100%;
            border-radius: 5px;
            background-color: #28a745;
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
                <a href="reg_orden_compra.php" class="btn btn-success chompa">Nueva OC</a>
                <a href="/ERP/all_projects.php" class="btn btn-secondary chompa">Regresar</a>
            </div>
        </div>
    </div>
</div>

<div class="container">
    <!-- Filtros adicionales -->
    <div class="filter-section sticky-top bg-light"> <!-- sticky-top lo mantiene visible al hacer scroll -->
    <form method="GET" action="ver_ordenes_compra.php" class="py-1"> <!-- py-1 reduce padding vertical -->
        <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
        <div class="form-row align-items-center"> <!-- align-items-center para vertical centering -->
            <div class="col-md-3 mb-1"> <!-- mb-1 reduce margen inferior -->
                <label for="estatus" class="mb-0">Estatus:</label> <!-- mb-0 elimina margen inferior -->
                <select name="estatus" id="estatus" class="form-control form-control-sm">
                    <option value="todos" <?php echo ($estatus_filter == 'todos') ? 'selected' : ''; ?>>Todos los estatus</option>
                    <option value="solicitada" <?php echo ($estatus_filter == 'solicitada') ? 'selected' : ''; ?>>Solicitadas</option>
                    <option value="aprobada" <?php echo ($estatus_filter == 'aprobada') ? 'selected' : ''; ?>>Aprobadas</option>
                    <option value="pagada" <?php echo ($estatus_filter == 'pagada') ? 'selected' : ''; ?>>Pagadas</option>
                    <option value="almacen" <?php echo ($estatus_filter == 'almacen') ? 'selected' : ''; ?>>Almacén</option>
                </select>
            </div>
            <div class="col-md-3 mb-1">
                <label for="proveedor" class="mb-0">Proveedor:</label>
                <select name="proveedor" id="proveedor" class="form-control form-control-sm">
                    <option value="">Todos los proveedores</option>
                    <?php foreach ($proveedores as $prov): ?>
                        <option value="<?php echo $prov['id_pr']; ?>" <?php echo ($proveedor_filter == $prov['id_pr']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($prov['empresa']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6 text-right mb-1">
                <button type="submit" class="btn btn-primary btn-sm">Filtrar</button> <!-- btn-sm para botón pequeño -->
                <a href="ver_ordenes_compra.php" class="btn btn-outline-secondary btn-sm">Limpiar</a>
            </div>
        </div>
    </form>
</div>

    <div class="oc-header">
        <h2>Órdenes de Compra</h2>
        <div class="text-muted">Total: <?php echo count($ordenes); ?> registros</div>
    </div>

    <div class="proyectos-container px-0">
        <div id="proyectos-container" class="w-100">
            <?php if (!empty($ordenes)): ?>
                <?php foreach ($ordenes as $oc):
                    $banderas = getEstadoBanderas($oc['estatus']);
                    $porcentajeRecibido = 0;
                    if ($oc['cantidad_total_pedida'] > 0) {
                        $porcentajeRecibido = ($oc['cantidad_recibida_total'] / $oc['cantidad_total_pedida']) * 100;
                    }
                    $estatusVisual = $oc['estatus'];
                    if ($oc['estatus'] == 'pago' && $oc['cantidad_recibida_total'] > 0 && $porcentajeRecibido < 100) {
                        $estatusVisual = 'parcial';
                    } elseif ($oc['estatus'] == 'pago' && $porcentajeRecibido >= 100) {
                        $estatusVisual = 'almacen';
                    }
                ?>
                    <div class="mb-4 proyecto-card w-100" data-estatus="<?php echo htmlspecialchars($estatusVisual); ?>">
                        <div class="card text-dark w-100">
                            <div class="card-body card-estados">
                                <div class="card-content">
                                    <a href="detalle_oc.php?id=<?php echo urlencode($oc['id_oc']); ?>" class="card-link">
                                    <h5 class="card-title text-start"><?php echo htmlspecialchars($oc['folio']); ?> || <?php echo htmlspecialchars($oc['proveedor']); ?></h5>
                                    <p class="text-start card-text">
                                        <strong>Solicitante:</strong> <?php echo htmlspecialchars($oc['username']); ?><br>
                                        <strong>Fecha de solicitud:</strong> <?php echo date('d/m/Y', strtotime($oc['fecha_solicitud'])); ?><br>
                                        <strong>Monto:</strong> $<?php echo number_format($oc['total'], 2); ?><br>
                                        <strong>Observaciones:</strong> <?php echo htmlspecialchars($oc['observaciones'] ?? ''); ?><br>
                                        <?php if (!empty($oc['id_fab'])): ?>
                                            <strong>Asignado a: </strong> OF-<?php echo htmlspecialchars($oc['id_fab'] ?? ''); ?> <?php echo htmlspecialchars($oc['proyecto_nombre'] ?? 'OF-' . $oc['id_fab']); ?>
                                        <?php elseif (!empty($oc['descripcion_destino'])): ?>
                                            <strong>Asignado a: </strong> <?php echo htmlspecialchars($oc['descripcion_destino']); ?>
                                        <?php else: ?>
                                            <span style="color: darkred;"><u>Sin proyecto/destino asignado</u></span>
                                        <?php endif; ?>
                                    </p>
                                    
                                    <?php if ($oc['estatus'] == 'pago' || $oc['estatus'] == 'parcial' || $oc['estatus'] == 'almacen'): ?>
                                        <div class="mt-2">
                                            <small>Recepción: <?php echo min(100, round($porcentajeRecibido,2)); ?>% completado</small>
                                            <div class="progreso-recepcion">
                                                <div class="barra" style="width: <?php echo min(100, $porcentajeRecibido); ?>%"></div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    </a>
                                </div>
                                
                                <!-- Banderas de estado a la derecha -->
                                <div class="banderas-container">
                                    <div class="bandera <?php echo $banderas['solicitada']; ?>">Solicitada</div>
                                    <div class="bandera <?php echo $banderas['autorizado']; ?>">Autorizado</div>
                                    <div class="bandera <?php echo $banderas['pago']; ?>">Pago</div>
                                    <div class="bandera <?php echo $banderas['almacen']; ?>">Almacén</div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-2">
                            <?php if ($oc['estatus'] == 'solicitada' && ($role == 'admin')): ?>
                                <a href="verificar_oc.php?id=<?php echo $oc['id_oc']; ?>" class="btn btn-info btn-card">Verificar</a>
                            <?php endif; ?>
                            
                            <?php if ($oc['estatus'] == 'autorizado'): ?>
                                <?php if($username == 'contabilidad2' || $username == 'admin'): ?>
                                    <a href="registrar_pago.php?id=<?php echo $oc['id_oc']; ?>" class="btn btn-info btn-card">Registrar Pago</a>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <?php if ($oc['estatus'] == 'pago' || $oc['estatus'] == 'parcial'): ?>
                                <?php if($username == 'CIS' || $username == 'admin'): ?>
                                    <a href="#" class="btn btn-sm btn-warning btn-recepcion-parcial" data-id="<?php echo $oc['id_oc']; ?>">Recepción parcial</a>
                                    <a href="completar_recepcion.php?id=<?php echo $oc['id_oc']; ?>" class="btn btn-primary btn-card">Completar Recepción</a>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <p class="text-muted text-center">No se encontraron órdenes de compra con los filtros aplicados</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</div>


<!-- Modal Recepción Parcial -->
<div class="modal fade" id="modalRecepcionParcial" tabindex="-1" role="dialog" aria-labelledby="modalRecepcionParcialLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <form id="formRecepcionParcial" method="post">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="modalRecepcionParcialLabel">Registrar Recepción Parcial</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
            <div id="modalRecepcionContenido">
                <!-- Aquí se cargará dinámicamente el contenido del modal desde recepcion_parcial.php -->
                <div class="text-center text-muted">Cargando datos de la orden...</div>
            </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Registrar</button>
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
        </div>
      </div>
    </form>
  </div>
</div>


<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script>
    // Actualizar filtros al cambiar fechas
    $(document).ready(function() {
        $('#estatus, #proveedor').change(function() {
            $(this).closest('form').submit();
        });

        $('.btn-recepcion-parcial').click(function(e) {
            e.preventDefault();

            let idOc = $(this).data('id');
            $('#modalRecepcionContenido').html('<div class="text-muted text-center">Cargando detalles...</div>');

            $.get('recepcion_parcial.php', { id_oc: idOc }, function(html) {
                $('#modalRecepcionContenido').html(html);
            });

            $('#modalRecepcionParcial').modal('show');
        });

        // Enviar recepción parcial
        $('#formRecepcionParcial').submit(function(e) {
            e.preventDefault();

            $.ajax({
                url: 'recepcion_parcial.php',
                type: 'POST',
                data: $(this).serialize(),
                success: function(response) {
                    if (response.trim() === 'ok') {
                        $('#respuestaRecepcion').text("Recepción parcial registrada correctamente.");
                        setTimeout(() => {
                            $('#modalRecepcionParcial').modal('hide');
                            location.reload(); // O actualizar la tabla
                        }, 1500);
                    } else {
                        $('#respuestaRecepcion').addClass('text-danger').text("Error: " + response);
                    }
                },
                error: function(xhr) {
                    $('#respuestaRecepcion').addClass('text-danger').text("Error: " + xhr.responseText);
                }
            });
        });
    });

    function confirmarAccion(id, accion) {
        let mensaje = '';
        if (accion === 'aprobar') {
            mensaje = '¿Estás seguro de autorizar esta orden de compra?';
        } else if (accion === 'rechazar') {
            mensaje = '¿Estás seguro de rechazar esta orden de compra? Esto cambiará todos los estatus a rojo.';
        } else {
            mensaje = '¿Confirmar acción?';
        }

        if (confirm(mensaje)) {
            window.location.href = `aprobar_oc.php?id=${id}&accion=${accion}`;
        }
    }
</script>
</body>
</html>