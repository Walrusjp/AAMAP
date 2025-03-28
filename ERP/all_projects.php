<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: /login.php");
    exit();
}

require 'C:/xampp/htdocs/db_connect.php';
require 'C:/xampp/htdocs/role.php';


// Obtener los proyectos desde la base de datos (ahora desde orden_fab)
$sql = "SELECT
            of.id_fab AS proyecto_id,
            p.nombre AS proyecto_nombre,
            p.descripcion,
            p.etapa AS estatus,
            p.fecha_entrega,
            p.cod_fab,
            c.nombre_comercial AS cliente_nombre
        FROM orden_fab AS of
        INNER JOIN proyectos AS p ON of.id_proyecto = p.cod_fab
        INNER JOIN clientes_p AS c ON of.id_cliente = c.id
        WHERE p.etapa IN ('directo', 'en proceso', 'finalizado', 'facturacion')
        ORDER BY p.etapa ASC";

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
    <link rel="stylesheet" type="text/css" href="stprojects.css">
    <link rel="icon" href="/assets/logo.ico">
</head>
<body>
<div class="navbar" style="display: flex; align-items: center; justify-content: space-between; padding: 0px; background-color: #f8f9fa; position: relative;">
    <!-- Logo -->
    <img src="/assets/grupo_aamap.webp" alt="Logo AAMAP" style="width: 18%; position: absolute; top: 25px; left: 10px;">

    <!-- Contenedor de elementos alineados a la derecha -->
    <div class="sticky-header" style="width: 100%;">
        <div class="container" style="display: flex; justify-content: flex-end; align-items: center;">
        <div style="position: absolute; top: 90px; left: 600px;"><p style="font-size: 2.5em; font-family: 'Verdana';"><b>E R P</b></p></div>
            <!-- Filtro y botones -->
            <div style="display: flex; align-items: center; gap: 10px;">
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
                <?php if ($username == 'admin'): ?>
                    <a href="delete_project.php" class="btn btn-danger chompa"><img src="/assets/delete.ico" style="width: 30px; height: auto; alt=""></a>
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
                                <h5 class="card-title">OF-<?php echo htmlspecialchars($proyecto['proyecto_id']); ?> || <?php echo htmlspecialchars($proyecto['proyecto_nombre']); ?></h5>
                                <p class="card-text">
                                    Cliente: <?php echo htmlspecialchars($proyecto['cliente_nombre']); ?><br>
                                    Descripción: <?php echo htmlspecialchars($proyecto['descripcion']); ?><br>
                                    <small class="text-muted"><b>Basado en cot: <?php echo htmlspecialchars($proyecto['cod_fab']); ?></b></small>
                                </p>
                            </div>
                        </div>
                    </a>
            <!--ucbeiubacnneiondioenon-->
                    <?php if ($proyecto['estatus'] == 'produccion'): ?>
                        <a href="complete_reg.php?id=<?php echo urlencode($proyecto['proyecto_id']); ?>" class="btn btn-secondary mt-2 btn-card">Completar Registro</a>
                    <?php endif; ?>

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