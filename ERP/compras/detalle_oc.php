<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: /login.php");
    exit();
}

require 'C:/xampp/htdocs/db_connect.php';
require 'C:/xampp/htdocs/role.php';

// Validar ID de OC
$oc_id = $_GET['id'] ?? 0;
if (!$oc_id) die("ID de OC no válido");

// Datos principales de la OC
$sql_oc = "SELECT 
            oc.id_oc, 
            oc.folio, 
            pr.empresa as proveedor, 
            of.id_fab, 
            p.nombre as proyecto_nombre, 
            p.cod_fab, 
            oc.descripcion_destino,
            oc.fecha_solicitud,
            (SELECT SUM(d.cantidad * d.precio_unitario) 
             FROM detalle_orden_compra d 
             WHERE d.id_oc = oc.id_oc AND d.activo = 1) as subtotal,
            (SELECT SUM(d.cantidad * d.precio_unitario) * 0.16 
             FROM detalle_orden_compra d 
             WHERE d.id_oc = oc.id_oc AND d.activo = 1) as iva,
            (SELECT SUM(d.cantidad * d.precio_unitario) * 1.16 
             FROM detalle_orden_compra d 
             WHERE d.id_oc = oc.id_oc AND d.activo = 1) as total
           FROM ordenes_compra oc
           LEFT JOIN proveedores pr ON oc.id_pr = pr.id_pr
           LEFT JOIN orden_fab of ON oc.id_fab = of.id_fab
           LEFT JOIN proyectos p ON of.id_proyecto = p.cod_fab
           WHERE oc.id_oc = ?";
$stmt = $conn->prepare($sql_oc);
$stmt->bind_param("i", $oc_id);
$stmt->execute();
$oc = $stmt->get_result()->fetch_assoc();

// Items de la OC
$sql_items = "SELECT i.codigo, i.descripcion, d.cantidad, d.precio_unitario, 
              (d.cantidad * d.precio_unitario) as subtotal, i.unidad_medida
              FROM detalle_orden_compra d
              JOIN inventario_almacen i ON d.id_alm = i.id_alm
              WHERE d.id_oc = ? AND d.activo = 1";
$stmt_items = $conn->prepare($sql_items);
$stmt_items->bind_param("i", $oc_id);
$stmt_items->execute();
$items = $stmt_items->get_result()->fetch_all(MYSQLI_ASSOC);

// Determinar el texto para OF/Destino
$of_destino = '';
if (!empty($oc['id_fab'])) {
    $of_destino = 'OF-' . $oc['id_fab'];
    if (!empty($oc['proyecto_nombre'])) {
        $of_destino .= ' (' . $oc['proyecto_nombre'] . ')';
    }
} elseif (!empty($oc['descripcion_destino'])) {
    $of_destino = $oc['descripcion_destino'];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Orden Compra <?php echo htmlspecialchars($oc['folio']); ?></title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="/ERP/seep.css">
    <link rel="icon" href="/assets/logo.ico">
    <style>
        .main-table, .thead, .tbody, .title {
            border: 0.1em solid;
            padding: 0px 5px;
        }
        .title {background-color: #341ca8;}
        .text-title {color: white;}
        .text-title, #header {
            text-align: center;
        }
        .tbody {font-size: 0.8em;}
        .info {
            height: auto;
            font-size: 0.8em;
            margin-top: 5px;
            border-bottom: 1px solid;
            line-height: 0.1;
        }
        #title {
            color: #000;
            text-decoration: underline black;
            text-align: center;
            padding: 10px 0px;
        }
        .totals-row {
            font-weight: bold;
            background-color: #f0f0f0;
        }
        @media print {
            .btn { display: none !important; }
            body { padding: 15px; margin: 0; }
            img { max-width: 100%; page-break-inside: avoid; }
            table { page-break-inside: auto; }
            tr { page-break-inside: avoid; page-break-after: auto; }
        }
    </style>
</head>
<body>

<div class="container mt-4">
    <table id="header" style="width: 100%; border-collapse: collapse;">
        <tr>
            <td rowspan="3" style="width: 20%; border: 1px solid;">
                <img src="/assets/grupo_aamap.webp" alt="Logo AAMAP" style="width: 100%;">
            </td>
            <td rowspan="3" style="width: 60%; border: 1px solid; vertical-align: middle;">
                <h2>Orden de Compra</h2>
            </td>
            <td class="info" style="border: 1px solid; padding: 5px;">Código: AAMAP-CS-F-08</td>
        </tr>
        <tr>
            <td class="info" style="border: 1px solid; padding: 5px;">
                Fecha: <?php echo date('d/m/Y', strtotime($oc['fecha_solicitud'])); ?>
            </td>
        </tr>
        <tr>
            <td class="info" style="border: 1px solid; padding: 5px;">
                Folio: <?php echo htmlspecialchars($oc['folio']); ?>
            </td>
        </tr>
    </table>

    <?php echo "<div id='title'><h4>Orden de Compra: " . htmlspecialchars($oc['folio']) . "</h4></div>";?>

    <table class="main-table" style="width: 100%;">
        <thead class="thead">
            <tr>
                <th class="title"><p class="text-title">OC</p></th>
                <th class="title"><p class="text-title">Proveedor</p></th>
                <th class="title"><p class="text-title">OF/Destino</p></th>
                <th class="title"><p class="text-title">Código</p></th>
                <th class="title"><p class="text-title">Descripción</p></th>
                <th class="title"><p class="text-title">Cantidad</p></th>
                <th class="title"><p class="text-title">UM</p></th>
                <th class="title"><p class="text-title">P. Unitario</p></th>
                <th class="title"><p class="text-title">Subtotal</p></th>
            </tr>
        </thead>
        <tbody>
            <?php
            $rowspan = count($items);
            foreach ($items as $index => $item):
                echo "<tr>";
                if ($index === 0) {
                    echo "<td class='tbody text-center align-middle' rowspan='{$rowspan}'>" . htmlspecialchars($oc['folio']) . "</td>";
                    echo "<td class='tbody align-middle' rowspan='{$rowspan}'>" . htmlspecialchars($oc['proveedor']) . "</td>";
                    echo "<td class='tbody align-middle' rowspan='{$rowspan}'>" . htmlspecialchars($of_destino) . "</td>";
                }
                echo "<td class='tbody'>" . htmlspecialchars($item['codigo']) . "</td>";
                echo "<td class='tbody'>" . htmlspecialchars($item['descripcion']) . "</td>";
                echo "<td class='tbody text-center'>" . htmlspecialchars($item['cantidad']) . "</td>";
                echo "<td class='tbody text-center'>" . htmlspecialchars($item['unidad_medida']) . "</td>";
                echo "<td class='tbody text-right'>$" . number_format($item['precio_unitario'], 2) . "</td>";
                echo "<td class='tbody text-right'>$" . number_format($item['subtotal'], 2) . "</td>";
                echo "</tr>";
            endforeach;
            ?>
        </tbody>
    </table>
    <table class="mt-4 d-flex justify-content-end ">
        <tbody>
            <tr class="totals-row">
                <td colspan="8"></td>
                <td class="text-right"><strong>Subtotal:</strong></td>
                <td class="text-right">$<?php echo number_format($oc['subtotal'], 2); ?></td>
            </tr>
            <tr class="totals-row">
                <td colspan="8"></td>
                <td class="text-right"><strong>IVA:</strong></td>
                <td class="text-right">$<?php echo number_format($oc['iva'], 2); ?></td>
            </tr>
            <tr class="totals-row">
                <td colspan="8"></td>
                <td class="text-right"><strong>Total:</strong></td>
                <td class="text-right">$<?php echo number_format($oc['total'], 2); ?></td>
            </tr>
        </tbody>
    </table>

    <div class="mt-4 d-flex justify-content-center ">
        <a href="ver_ordenes_compra.php" class="btn btn-secondary mx-2">Regresar</a>
        <a href="ver_logs_oc.php?id=<?php echo $oc['id_oc']; ?>" class="btn btn-info mx-2">Ver Historial</a>
    </div>
</div>

</body>
</html>
