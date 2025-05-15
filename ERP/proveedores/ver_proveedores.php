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

$query .= " ORDER BY id_pr ASC";

$result = $conn->query($query);
$proveedores = $result->fetch_all(MYSQLI_ASSOC);


// Obtener estados únicos para filtro
$estados = $conn->query("SELECT DISTINCT estado FROM proveedores WHERE estado IS NOT NULL ORDER BY estado")->fetch_all(MYSQLI_ASSOC);

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
        .card-container {
            margin: 20px auto;
            width: 95%;
        }
        .proveedor-card {
            cursor: pointer;
            transition: transform 0.2s;
            height: 100%;
        }
        .proveedor-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .card-title {
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
        }
        .card-subtitle {
            font-size: 0.9rem;
            color: #6c757d;
        }
        .card-text {
            font-size: 0.85rem;
        }
        .iso-badge {
            position: absolute;
            top: 10px;
            right: 10px;
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
        .modal-lg {
            max-width: 800px;
        }
        .ref-bancaria-item {
            border-bottom: 1px solid #eee;
            padding: 10px 0;
        }
        .ref-bancaria-item:last-child {
            border-bottom: none;
        }
        .btn-modal { margin-left: 20px; }
        #proveedorModalLabel { margin-right: 20px; }
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

<div class="container">
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
    
    <!-- Cards de proveedores -->
    <div class="row">
        <?php if (!empty($proveedores)): ?>
            <?php foreach ($proveedores as $prov): ?>
                <div class="col-md-4 mb-4">
                    <div class="card proveedor-card" data-toggle="modal" data-target="#proveedorModal" data-id="<?php echo $prov['id_pr']; ?>">
                        <div class="card-body">
                            <?php if ($prov['iso']): ?>
                                <span class="badge badge-success iso-badge">ISO</span>
                            <?php endif; ?>
                            <h5 class="card-title"><?php echo htmlspecialchars($prov['empresa']); ?></h5>
                            <h6 class="card-subtitle mb-2"><?php echo htmlspecialchars($prov['contacto']); ?></h6>
                            <p class="card-text">
                                <small>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" viewBox="0 0 16 16">
                                        <path d="M3.654 1.328a.678.678 0 0 0-1.015-.063L1.605 2.3c-.483.484-.661 1.169-.45 1.77a17.6 17.6 0 0 0 4.168 6.608 17.6 17.6 0 0 0 6.608 4.168c.601.211 1.286.033 1.77-.45l1.034-1.034a.678.678 0 0 0-.063-1.015l-2.307-1.794a.68.68 0 0 0-.58-.122l-2.19.547a1.75 1.75 0 0 1-1.657-.459L5.482 8.062a1.75 1.75 0 0 1-.46-1.657l.548-2.19a.68.68 0 0 0-.122-.58zM1.884.511a1.745 1.745 0 0 1 2.612.163L6.29 2.98c.329.423.445.974.315 1.494l-.547 2.19a.68.68 0 0 0 .178.643l2.457 2.457a.68.68 0 0 0 .644.178l2.189-.547a1.75 1.75 0 0 1 1.494.315l2.306 1.794c.829.645.905 1.87.163 2.611l-1.034 1.034c-.74.74-1.846 1.065-2.877.702a18.6 18.6 0 0 1-7.01-4.42 18.6 18.6 0 0 1-4.42-7.009c-.362-1.03-.037-2.137.703-2.877z"/>
                                    </svg>
                                    <?php echo htmlspecialchars($prov['telefono']); ?>
                                    <?php if (!empty($prov['correo'])): ?>
                                        <br>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" viewBox="0 0 16 16">
                                            <path d="M.05 3.555A2 2 0 0 1 2 2h12a2 2 0 0 1 1.95 1.555L8 8.414zM0 4.697v7.104l5.803-3.558zM6.761 8.83l-6.57 4.027A2 2 0 0 0 2 14h12a2 2 0 0 0 1.808-1.144l-6.57-4.027L8 9.586zm3.436-.586L16 11.801V4.697z"/>
                                        </svg>
                                        <?php echo htmlspecialchars($prov['correo']); ?>
                                    <?php endif; ?>
                                </small>
                            </p>
                        </div>
                        <!--<div class="card-footer bg-transparent">
                            <small class="text-muted"><?php echo htmlspecialchars($prov['estado']); ?></small>
                            <div class="float-right">
                                <a href="editar_proveedor.php?id=<?php echo $prov['id_pr']; ?>" class="btn btn-sm btn-primary" onclick="event.stopPropagation()">Editar</a>
                                <a href="ver_compras_proveedor.php?id=<?php echo $prov['id_pr']; ?>" class="btn btn-sm btn-info" onclick="event.stopPropagation()">Compras</a>
                            </div>
                        </div>-->
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5">
                <h4>No hay proveedores registrados</h4>
                <a href="registrar_proveedor.php" class="btn btn-success mt-3">Agregar nuevo proveedor</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal para detalles del proveedor -->
<div class="modal fade" id="proveedorModal" tabindex="-1" role="dialog" aria-labelledby="proveedorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="proveedorModalLabel">Detalles del Proveedor</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="modalBodyContent">
                <!-- Contenido cargado por AJAX -->
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Cargando...</span>
                    </div>
                </div>
            </div>
            
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script>
    // Actualizar filtros al cambiar
    $(document).ready(function() {
        $('#estado, #iso').change(function() {
            $(this).closest('form').submit();
        });

        // Cargar datos del proveedor cuando se abre el modal
        $('#proveedorModal').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget);
            var idProveedor = button.data('id');
            var modal = $(this);
            
            // Mostrar spinner mientras se carga
            modal.find('.modal-body').html(`
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Cargando...</span>
                    </div>
                </div>
            `);
            
            // Cargar datos del proveedor via AJAX
            $.ajax({
                url: 'get_proveedor_details.php',
                type: 'GET',
                data: { id: idProveedor },
                success: function(response) {
                    modal.find('.modal-body').html(response);
                },
                error: function() {
                    modal.find('.modal-body').html(`
                        <div class="alert alert-danger">
                            Error al cargar los datos del proveedor
                        </div>
                    `);
                }
            });
        });
    });
</script>
</body>
</html>