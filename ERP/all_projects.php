<?php
session_start();
require 'C:/xampp/htdocs/db_connect.php';
require 'C:/xampp/htdocs/role.php';

// Verificar sesión y obtener datos del usuario
if (!isset($_SESSION['username'])) {
    header("Location: /login.php");
    exit();
}

// Obtener información del usuario actual
$username = $_SESSION['username'];
$user_id = $_SESSION['user_id'];

// Verificar si el usuario tiene acceso a esta página
$query = "SELECT role FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: /login.php");
    exit();
}

$user = $result->fetch_assoc();
$role = $user['role'];


// Obtener los proyectos desde la base de datos (ahora desde orden_fab)
$sql = "SELECT
            of.id_fab AS proyecto_id,
            p.nombre AS proyecto_nombre,
            p.descripcion,
            p.etapa AS estatus,
            p.fecha_entrega,
            p.cod_fab,
            c.nombre_comercial AS cliente_nombre,
            of.es_subproyecto,
            of.of_padre,
            padre.plano_ref AS padre_plano_ref
        FROM orden_fab AS of
        INNER JOIN proyectos AS p ON of.id_proyecto = p.cod_fab
        INNER JOIN clientes_p AS c ON of.id_cliente = c.id
        LEFT JOIN orden_fab AS padre ON of.of_padre = padre.id_fab
        WHERE p.etapa IN ('directo', 'en proceso', 'finalizado', 'facturacion') AND of.activo = 1
        ORDER BY of.id_fab DESC";

$result = $conn->query($sql);
$proyectos = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $proyectos[] = $row;
    }
}

// Función para actualizar el estatus del proyecto
function actualizarEstatusProyecto($conn, $proyecto_id, $nuevo_estatus) {
    $sql = "UPDATE proyectos SET etapa = ? WHERE cod_fab = (SELECT id_proyecto FROM orden_fab WHERE id_fab = ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $nuevo_estatus, $proyecto_id);
    $stmt->execute();
    $stmt->close();
}

// Manejar la solicitud de facturación
if (isset($_POST['facturar'])) {
    $proyecto_id = $_POST['proyecto_id'];
    actualizarEstatusProyecto($conn, $proyecto_id, 'facturacion');
    header("Location: all_projects.php");
    exit();
}

// Manejar la solicitud de aprobación de cotización
if (isset($_POST['aprobar_cotizacion'])) {
    $proyecto_id = $_POST['proyecto_id'];
    actualizarEstatusProyecto($conn, $proyecto_id, 'en proceso');
    header("Location: all_projects.php"); // Redirigir a la página de proyectos
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>ERP Proyectos</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" type="text/css" href="stprojects.css">
    <link rel="icon" href="/assets/logo.ico">
    <style>
        /* Soporte para submenú en dropdown Bootstrap */
        .dropdown-submenu > .dropdown-menu {
        display: none;
        margin-top: 0;
        }

        .dropdown-submenu:hover > .dropdown-menu {
        display: block;
        }

        .disabled-item {
            pointer-events: none;
            cursor: pointer;
        }

        .badge-estatus {
            font-size: 0.9rem;
            padding: 5px 10px;
            color: rgb(19, 17, 17);
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 1;
            font-family: 'Consolas';
        }
    </style>
</head>
<body>
<div class="navbar" style="display: flex; align-items: center; justify-content: space-between; padding: 0px; background-color: #f8f9fa; position: relative;">
    <!-- Logo -->
    <img src="/assets/grupo_aamap.webp" alt="Logo AAMAP" style="width: 18%; position: absolute; top: 25px; left: 10px;">

    <!-- Contenedor de elementos alineados a la derecha -->
    <div class="sticky-header" style="width: 100%; height: 150px;">
        <div class="container" style="display: flex; justify-content: flex-end; align-items: center;">
        <div style="position: absolute; top: 90px; left: 450px;"><p style="font-size: 2.2em; font-family: 'Verdana';"><b>ORDENES DE FABRICACIÓN</b></p></div>
            <!-- Filtro y botones -->
            <div style="display: flex; align-items: start; gap: 10px; position: absolute; top: 10px; left: 420px;">
                <!-- Filtro -->
                <div style="display: flex; align-items: center;">
                    <label for="filter" style="margin-right: 10px;">Filtrar:</label>
                    <select id="filter" class="form-control" style="width: auto;">
                        <option value="todos">Todos</option>
                        <option value="directo">directas</option>
                        <option value="en proceso">proyectos</option>
                        <option value="en proceso,directo">En proceso</option>
                        <option value="finalizado">Finalizados</option>
                        <option value="facturacion">Facturación</option>
                    </select>
                </div>
                <!-- Botones -->
                <?php if ($username === 'admin' || $username === 'l.aca'): ?>
                    <a href="new_project_direct.php" class="btn btn-info chompa">Nuevo proyecto</a>
                <?php endif; ?>
                <?php if ($username == 'admin'): ?>
                    <a href="delete_project.php" class="btn btn-danger chompa"><img src="/assets/delete.ico" style="width: 30px; height: auto; alt=""></a>
                <?php endif; ?>
                <?php if ($role == 'admin' || $username === "CIS"): ?>
                    <a href="ver_prod_directos.php" class="btn btn-info chompa">Productos Directos</a>
                <?php endif; ?>

                <?php if ($role == 'admin' || $username == "CIS" || $username == 'atencionaclientes'): ?>
                <div class="dropdown">
                <button class="btn btn-info dropdown-toggle" type="button" id="almacenDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    Cadena de Suministros
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item disabled" href="#" data-value="proveedores" data-url='proveedores/ver_proveedores.php'>Proveedores</a></li>
                    <li><a class="dropdown-item" href="/launch.php" data-value="almacen" data-url='almacen/ver_almacen.php'>Almacén</a></li>
                    <li><a class="dropdown-item " href="#" data-value="compras" data-url='compras/ver_ordenes_compra.php'>Compras</a></li>
                    <?php if($username == 'CIS'): ?>
                        <li><a class="dropdown-item" href="#" data-value="req_interna" data-url='/ERP/req_interna/panel_almacen.php'>Requisición interna</a></li>
                    <?php else: ?>
                        <li><a class="dropdown-item" href="" data-value="req_interna" data-url='req_interna/req_interna.php'>Requisición interna</a></li>
                    <?php endif; ?>
                <!--Submenú para Reportes-->
                    <li class="dropdown-submenu position-relative" >
                        <a class="dropdown-item disabled-item">Reportes</a>
                        <ul class="dropdown-menu position-absolute start-100 top-0" style="top: -10px; left: 160px;">
                            <li><a class="dropdown-item" href="#" data-value="reporte_inventario" data-url="reportes/reporte_inventario.php">Inventario</a></li>
                            <li><a class="dropdown-item" href="#" data-value="reporte_consumos" data-url="reportes/reporte_consumos.php">Consumos</a></li>
                            <li><a class="dropdown-item" href="#" data-value="reporte_compras" data-url="reportes/reporte_compras.php">Compras</a></li>
                        </ul>
                    </li>
                </ul>
                <input type="hidden" id="almacenValor" name="almacenValor">
                </div>
                <?php endif; ?>
                <a href="/launch.php" class="btn btn-secondary chompa">Regresar</a>
            </div>
        </div>
    </div>
</div>

<div class="proyectos-container">
    <div id="proyectos-container">
        <?php if (!empty($proyectos)): ?>
            <?php foreach ($proyectos as $proyecto): ?>
                <div class="mb-4 proyecto-card" data-estatus="<?php echo htmlspecialchars($proyecto['estatus']); ?>">
                    <a href="ver_proyecto.php?id=<?php echo urlencode($proyecto['proyecto_id']); ?>" class="card-link">
                        <div class="card text-<?php 
                            echo $proyecto['estatus'] == 'finalizado' ? 'success' : 
                                ($proyecto['estatus'] == 'facturacion' ? 'primary' :
                                ($proyecto['estatus'] == 'directo' ? 'warning' : 
                                ($proyecto['estatus'] == 'en proceso' ? 'warning' : 'dark'))); 
                        ?>">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <?php 
                                        echo $proyecto['es_subproyecto'] ? '<i class="fas fa-level-down-alt"></i> ' : '';
                                        echo 'OF-'.htmlspecialchars($proyecto['proyecto_id']).' || '.htmlspecialchars($proyecto['proyecto_nombre']);
                                    ?>
                                </h5>
                                <p class="card-text">
                                    <?php if($proyecto['es_subproyecto']): ?>
                                        <span style="color:rgb(44, 25, 151);">Tipo: Subproyecto (Maquila Interna)</span><br>
                                        Asignado a: <?php echo $proyecto['of_padre'] ? 'OF-'.htmlspecialchars($proyecto['of_padre']) : 'N/A'; ?><br>
                                    <?php else: ?>
                                        Cliente: <?php echo htmlspecialchars($proyecto['cliente_nombre']); ?><br>
                                        Basado en cot: <?php echo htmlspecialchars($proyecto['cod_fab']); ?><br>
                                    <?php endif; ?>
                                    Nota: <?php echo htmlspecialchars($proyecto['descripcion']); ?>
                                </p>

                                <span class="badge <?php 
                                    switch($proyecto['estatus']) {
                                        case 'en proceso': echo 'bg-warning'; break;
                                        case 'directo': echo 'bg-warning'; break;
                                        case 'finalizado': echo 'bg-success text-white'; break;
                                        case 'facturacion': echo 'bg-info'; break;
                                        default: echo 'dark';
                                    }
                                ?> badge-estatus">
                                    <?php 
                                        $estatusTextos = [
                                            'en proceso' => 'En proceso ERP',
                                            'directo' => 'En proceso ERP',
                                            'finalizado' => 'Finalizado',
                                            'facturacion' => 'Facturado'
                                        ];
                                        echo $estatusTextos[$proyecto['estatus']] ?? ucfirst($proyecto['estatus']);
                                    ?>
                                </span>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>

        <?php else: ?>
            <div class="col-12">
                <p class="text-muted text-center">No hay proyectos disponibles.</p>
            </div>
        <?php endif; ?>

    </div>
</div>

<script>
    const almacenDropdown = document.getElementById('almacenDropdown');
    const almacenValor = document.getElementById('almacenValor');
    const almacenOpciones = document.querySelectorAll('.dropdown-menu .dropdown-item');

    almacenOpciones.forEach(opcion => {
        opcion.addEventListener('click', function (e) {
        e.preventDefault();
        const texto = this.textContent;
        const valor = this.getAttribute('data-value');
        const url = this.getAttribute('data-url');
        almacenDropdown.textContent = texto;
        almacenValor.value = valor;
        window.location.href = url;
        });
    });

    // Filtrar proyectos dinámicamente por estado
    document.getElementById('filter').addEventListener('change', function () {
        const selectedFilter = this.value;
        const proyectos = document.querySelectorAll('.proyecto-card');

        proyectos.forEach(function (proyecto) {
            if (selectedFilter === 'todos') {
                proyecto.style.display = 'block';
            } else {
                // Convertir la lista de estados en un array
                const estados = selectedFilter.split(','); 
                if (estados.includes(proyecto.dataset.estatus)) {
                    proyecto.style.display = 'block';
                } else {
                    proyecto.style.display = 'none';
                }
            }
        });
    });

</script>

</body>
</html>