<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: /login.php");
    exit();
}

require 'C:/xampp/htdocs/db_connect.php';
require 'C:/xampp/htdocs/role.php';
require 'send_email.php';

// Obtener los proyectos en etapa 'directo' desde la base de datos
$sql = "SELECT
            of.id_fab AS orden_fab_id,  -- Mostrar id_fab en lugar de cod_fab
            p.nombre AS proyecto_nombre,
            p.descripcion,
            p.etapa AS estatus,
            p.observaciones,
            p.fecha_entrega,
            c.nombre_comercial AS cliente_nombre
        FROM proyectos AS p
        INNER JOIN clientes_p AS c ON p.id_cliente = c.id
        INNER JOIN orden_fab AS of ON p.cod_fab = of.id_proyecto  -- Unir con orden_fab
        WHERE p.etapa = 'directo'  -- Filtrar solo proyectos en etapa 'directo'
        ORDER BY of.id_fab ASC";

$result = $conn->query($sql);
$proyectos = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $proyectos[] = $row;
    }
}

// Función para actualizar el estatus del proyecto (si es necesario)
function actualizarEstatusProyecto($conn, $proyecto_id, $nuevo_estatus, $observaciones = null) {
    if ($observaciones !== null) {
        $sql = "UPDATE proyectos SET etapa = ?, observaciones = ? WHERE cod_fab = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $nuevo_estatus, $observaciones, $proyecto_id);
    } else {
        $sql = "UPDATE proyectos SET etapa = ? WHERE cod_fab = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $nuevo_estatus, $proyecto_id);
    }
    
    if ($stmt->execute()) {
        return true; // Éxito al actualizar
    } else {
        return false; // Error al actualizar
    }

    $stmt->close();
}

// Variables para almacenar el mensaje y la redirección
$mensaje = "";
$redireccionar = false;

// Manejar la solicitud de aprobación de cotización (si es necesario)
if (isset($_POST['aprobar_cotizacion'])) {
    $proyecto_id = $_POST['proyecto_id'];
    
    echo "<script>
            if (confirm('¿Estás seguro de que quieres aprobar esta cotización?')) {";
                if (actualizarEstatusProyecto($conn, $proyecto_id, 'aprobado')) {
                    $mensaje = "Cotización aprobada con éxito.";
                    $redireccionar = true;
                } else {
                    $mensaje = "Error al aprobar la cotización.";
                }
            echo "}
          </script>";
}

// Manejar la solicitud de rechazo de cotización (si es necesario)
if (isset($_POST['rechazar_cotizacion'])) {
    $proyecto_id = $_POST['proyecto_id'];
    $observaciones = $_POST['observaciones'];
    
    echo "<script>
            if (confirm('¿Estás seguro de que quieres rechazar esta cotización?')) {";
                if (actualizarEstatusProyecto($conn, $proyecto_id, 'rechazado', $observaciones)) {
                    $mensaje = "Cotización rechazada.";
                    $redireccionar = true;
                } else {
                    $mensaje = "Error al rechazar la cotización.";
                }
            echo "}
          </script>";
}

// Mostrar el mensaje y redirigir solo si es necesario
if ($mensaje !== "") {
    echo "<script>alert('" . $mensaje . "');</script>";
    if ($redireccionar) {
        header("Location: all_projects.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>OF Directas</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="stprojects.css">
    <link rel="icon" href="/assets/logo.ico">
</head>
<body>
<div class="navbar" style="display: flex; align-items: center; justify-content: space-between; padding: 0px; background-color: #f8f9fa; position: relative;">
    <!-- Logo -->
    <img src="/assets/grupo_aamap.webp" alt="Logo AAMAP" style="width: 18%; position: absolute; top: 25px; left: 10px;">
    <div style="position: absolute; top: 90px; left: 600px;"><p style="font-size: 2.3em; font-family: 'Verdana';"><b>C R M</b></p></div>
    <!-- Contenedor de elementos alineados a la derecha -->
    <div class="sticky-header" style="width: 100%;">
        <div class="container" style="display: flex; justify-content: flex-end; align-items: center;">
            <!-- Botones -->
            <div style="display: flex; align-items: center; gap: 10px;">
                <a href="new_of.php" class="btn btn-info chompa">Nueva OF Directa</a>
                <a href="/launch.php" class="btn btn-secondary chompa">Regresar</a>
            </div>
        </div>
    </div>
</div>

<div class="proyectos-container">
    <div id="proyectos-container">
        <?php if (!empty($proyectos)): ?>
            <br>
            <?php foreach ($proyectos as $proyecto): ?>
                <div class="proyecto-card w-100 mb-3" data-estatus="<?php echo htmlspecialchars($proyecto['estatus']); ?>">
                    <a href="ver_cot.php?id=<?php echo urlencode($proyecto['orden_fab_id']); ?>" class="card-link" target="_blank">
                        <div class="card text-dark">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($proyecto['orden_fab_id']); ?> || <?php echo htmlspecialchars($proyecto['proyecto_nombre']); ?></h5>
                                <p class="card-text">
                                    Cliente: <?php echo htmlspecialchars($proyecto['cliente_nombre']); ?><br>
                                    Nota: <?php echo htmlspecialchars($proyecto['descripcion']); ?><br>
                                </p>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12">
                <p class="text-muted text-center">No hay órdenes de fabricación directas disponibles.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>