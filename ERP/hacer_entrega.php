<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: /login.php");
    exit();
}

require 'C:/xampp/htdocs/db_connect.php';

// Validar y sanitizar la entrada del ID del proyecto
$proyecto_id = filter_var($_GET['id'], FILTER_SANITIZE_STRING);
if ($proyecto_id === false || empty($proyecto_id)) {
    echo "ID de proyecto no válido.";
    exit();
}

// Consultar datos del proyecto
$sql = "SELECT 
            of.id_fab AS proyecto_id,
            p.cod_fab,
            p.nombre AS nombre_proyecto,
            c.nombre_comercial AS nombre_cliente,
            p.fecha_entrega,
            p.etapa
        FROM orden_fab of
        INNER JOIN proyectos p ON of.id_proyecto = p.cod_fab
        INNER JOIN clientes_p c ON of.id_cliente = c.id
        WHERE of.id_fab = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $proyecto_id);
$stmt->execute();
$result = $stmt->get_result();
$proyecto = $result->fetch_assoc();

// Consultar las partidas del proyecto
$sqlPartidas = "SELECT 
                    pa.id AS partida_id,
                    pa.descripcion AS nombre_partida, 
                    pa.cantidad,
                    pa.unidad_medida
                FROM partidas pa
                WHERE pa.cod_fab = (SELECT id_proyecto FROM orden_fab WHERE id_fab = ?)";

$stmtPartidas = $conn->prepare($sqlPartidas);
$stmtPartidas->bind_param("s", $proyecto_id);
$stmtPartidas->execute();
$resultPartidas = $stmtPartidas->get_result();
$partidas = $resultPartidas->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Entrega Parcial - OF-<?php echo htmlspecialchars($proyecto['proyecto_id']) ?></title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="seep.css">
    <link rel="icon" href="/assets/logo.ico">
</head>
<body>
<div class="container mt-4">
    <h2>Entrega Parcial - Proyecto: <?php echo htmlspecialchars($proyecto['nombre_proyecto']) ?></h2>
    
    <form action="procesar_entrega.php" method="post">
        <input type="hidden" name="proyecto_id" value="<?php echo htmlspecialchars($proyecto_id) ?>">
        <input type="hidden" name="cod_fab" value="<?php echo htmlspecialchars($proyecto['cod_fab']) ?>">
        
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Partida</th>
                    <th>Descripción</th>
                    <th>Cantidad Total</th>
                    <th>Cantidad a Entregar</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($partidas as $partida): ?>
                <tr>
                    <td><?php echo htmlspecialchars($partida['partida_id']) ?></td>
                    <td><?php echo htmlspecialchars($partida['nombre_partida']) ?></td>
                    <td><?php echo htmlspecialchars($partida['cantidad']) ?> <?php echo htmlspecialchars($partida['unidad_medida']) ?></td>
                    <td>
                        <input type="number" name="cantidad[<?php echo $partida['partida_id'] ?>]" 
                               min="0" max="<?php echo $partida['cantidad'] ?>" 
                               class="form-control" value="0">
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div class="form-group">
            <label for="observaciones">Observaciones:</label>
            <textarea name="observaciones" class="form-control" rows="3"></textarea>
        </div>
        
        <div class="text-center">
            <button type="submit" name="mandar_facturar_entrega" class="btn btn-primary">Mandar a Facturar Entrega</button>
            <a href="ver_proyecto.php?id=<?php echo $proyecto_id ?>" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>
</body>
</html>