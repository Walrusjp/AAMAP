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

// Consulta base para clientes
$query = "SELECT * FROM clientes_p WHERE activo=1";

// Aplicar filtros
if (!empty($search)) {
    $query .= " AND (nombre_comercial LIKE '%" . $conn->real_escape_string($search) . "%' 
                OR razon_social LIKE '%" . $conn->real_escape_string($search) . "%'
                OR rfc LIKE '%" . $conn->real_escape_string($search) . "%')";
}

$query .= " ORDER BY id ASC";

$result = $conn->query($query);
$clientes = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Clientes</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="/ERP/stprojects.css">
    <link rel="icon" href="/assets/logo.ico">
    <style>
        .card-container {
            margin: 20px auto;
            width: 95%;
        }
        .cliente-card {
            cursor: pointer;
            transition: transform 0.2s;
            height: 100%;
        }
        .cliente-card:hover {
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
        .comprador-item {
            border-bottom: 1px solid #eee;
            padding: 10px 0;
        }
        .comprador-item:last-child {
            border-bottom: none;
        }
        .btn-modal { margin-left: 20px; }
        #clienteModalLabel { margin-right: 20px; }
        #close:hover { background-color: red; }
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
                <form method="GET" action="ver_clientes.php" class="form-inline" style="margin-right: 10px;">
                    <div class="input-group">
                        <?php if(isset($_GET['search']) && !empty($_GET['search'])): ?>
                            <a href="ver_clientes.php" class="input-group-prepend" title="Cancelar búsqueda" style="display: flex; align-items: center; padding: 0 5px;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" style="margin-right: 5px;">
                                    <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                                    <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/>
                                </svg>
                            </a>
                        <?php endif; ?>
                        <input type="text" name="search" class="form-control" id="csearch" 
                            placeholder="Buscar clientes..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" style="width: 200px;">
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
                <a href="reg_client.php" class="btn btn-success chompa">Nuevo Cliente</a>
                <a href="/ERP/all_projects.php" class="btn btn-secondary chompa">Regresar</a>
                <?php if($username === 'admin'): ?>
                    <a href="borrar_cliente.php" class="btn btn-danger chompa">Eliminar cliente</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="container">
    <div class="header-buttons">
        <h2>Clientes Registrados</h2>
        <div class="text-muted">Total: <?php echo count($clientes); ?> registros</div>
    </div>
    
    <!-- Cards de clientes -->
<div class="row">
    <?php if (!empty($clientes)): ?>
        <?php foreach ($clientes as $cliente): ?>
            <div class="col-md-4 mb-4">
                <div class="card cliente-card" data-toggle="modal" data-target="#clienteModal" data-id="<?php echo $cliente['id']; ?>">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($cliente['nombre_comercial']); ?></h5>
                        <h6 class="card-subtitle mb-2"><?php echo htmlspecialchars($cliente['razon_social']); ?></h6>
                        <p class="card-text">
                            <small class="text-muted">
                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" viewBox="0 0 16 16">
                                    <path d="M4.715 6.542L3.343 7.914a3 3 0 1 0 4.243 4.243l1.828-1.829A3 3 0 0 0 8.586 5.5L8 6.086a1 1 0 0 0-.154.199 2 2 0 0 1 .861 3.337L6.88 11.45a2 2 0 1 1-2.83-2.83l.793-.792a4 4 0 0 1-.128-1.287z"/>
                                    <path d="M6.586 4.672A3 3 0 0 0 7.414 9.5l.775-.776a4 4 0 0 1-.196-1.188l-.96-.96a2 2 0 0 1 2.83-2.83l.793.792c.112.42.155.855.128 1.287l1.372-1.372a3 3 0 0 0-4.243-4.243z"/>
                                </svg>
                                <?php echo htmlspecialchars($cliente['rfc']); ?>
                            </small>
                        </p>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="col-12 text-center py-5">
            <h4>No hay clientes registrados</h4>
            <a href="reg_client.php" class="btn btn-success mt-3">Agregar nuevo cliente</a>
        </div>
    <?php endif; ?>
</div>
</div>

<!-- Modal para detalles del cliente -->
<div class="modal fade" id="clienteModal" tabindex="-1" role="dialog" aria-labelledby="clienteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="clienteModalLabel">Detalles del Cliente</h5>
                <button id="close" type="button" class="close" data-dismiss="modal" aria-label="Close">
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
    // Cargar datos del cliente cuando se abre el modal
    $('#clienteModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var idCliente = button.data('id');
        var modal = $(this);
        
        // Mostrar spinner mientras se carga
        modal.find('.modal-body').html(`
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">Cargando...</span>
                </div>
            </div>
        `);
        
        // Cargar datos del cliente via AJAX
        $.ajax({
            url: 'get_cliente_details.php',
            type: 'GET',
            data: { id: idCliente },
            success: function(response) {
                modal.find('.modal-body').html(response);
            },
            error: function() {
                modal.find('.modal-body').html(`
                    <div class="alert alert-danger">
                        Error al cargar los datos del cliente
                    </div>
                `);
            }
        });
    });
</script>
</body>
</html>