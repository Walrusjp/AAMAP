<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: /login.php");
    exit();
}

require 'C:/xampp/htdocs/db_connect.php';
require 'C:/xampp/htdocs/role.php';

// Obtener los proyectos desde la base de datos
$sql = "SELECT
            p.cod_fab AS proyecto_id,
            p.nombre AS proyecto_nombre,
            p.descripcion,
            p.etapa AS estatus,
            p.fecha_entrega,
            c.nombre_comercial AS cliente_nombre -- Usar nombre comercial del cliente
        FROM proyectos AS p
        INNER JOIN clientes_p AS c ON p.id_cliente = c.id
        WHERE p.etapa IN ('en proceso', 'finalizado', 'facturacion')
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
    $sql = "UPDATE proyectos SET etapa = ? WHERE cod_fab = ?";
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
<img src="/assets/grupo_aamap.webp" style="width: 18%; position: absolute; top: 25px; left: 10px;">
<div class="sticky-header">
    <div class="container d-flex justify-content-between chompa">
        <p class="text-center">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</p>
        <div class="d-flex justify-content-center mb-3">
            <label for="filter" class="mr-2">Filtrar:</label>
            <select id="filter" class="form-control w-auto">
                <option value="todos">Todos</option>
                <option value="en proceso">En proceso</option>
                <option value="finalizado">Finalizados</option>
                <option value="facturacion">Facturación</option>
            </select>
            <!--<a href="new_OF.php" class="btn btn-info chompa">Nueva OF</a>-->
            <?php if ($username == 'admin'): ?>
                <a href="delete_project.php" class="btn btn-danger chompa">Eliminar Proyecto</a>
            <?php endif; ?>
            <a href="/launch.php" class="btn btn-secondary chompa">Regresar</a>
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
                                 ($proyecto['estatus'] == 'en proceso' ? 'warning' : 'dark')); 
                        ?>">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($proyecto['proyecto_id']); ?> || <?php echo htmlspecialchars($proyecto['proyecto_nombre']); ?></h5>
                                <p class="card-text">
                                    Cliente: <?php echo htmlspecialchars($proyecto['cliente_nombre']); ?><br>
                                    Descripción: <?php echo htmlspecialchars($proyecto['descripcion']); ?><br>
                                </p>
                            </div>
                        </div>
                    </a>

                    <?php if ($proyecto['estatus'] == 'finalizado'): ?>
                        <form method="POST" action="">
                            <input type="hidden" name="proyecto_id" value="<?php echo htmlspecialchars($proyecto['proyecto_id']); ?>">
                            <button type="submit" name="facturar" class="btn btn-primary mt-2 btn-card">Mandar a Facturar</button>
                        </form>
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
            if (selectedFilter === 'todos' || proyecto.dataset.estatus === selectedFilter) {
                proyecto.style.display = 'block';
            } else {
                proyecto.style.display = 'none';
            }
        });
    });
</script>

</body>
</html>