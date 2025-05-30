<?php
// historico_movs_alm.php

session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['username'])) {
    header("Location: /login.php");
    exit();
}

// 1. Conexión a la base de datos
require 'C:/xampp/htdocs/db_connect.php';
require 'C:/xampp/htdocs/role.php';

// 2. Consulta principal de TODOS los movimientos
$sql = "SELECT m.*, i.codigo, i.descripcion as item_desc, u.username as usuario
        FROM movimientos_almacen m
        LEFT JOIN inventario_almacen i ON m.id_alm = i.id_alm
        LEFT JOIN users u ON m.id_usuario = u.id
        ORDER BY m.fecha_mov DESC";

$result = $conn->query($sql);
$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="/assets/logo.ico">
    <title>Histórico de Movimientos</title>
    <style>
        /* Estilos generales */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 30px 60px;
            background-color: white;
        }
        
        /* Contenedor principal */
        .container {
            padding: 0;
            width: 100%;
            margin: 0 auto;
        }
        
        /* Encabezado */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        /* Botón de regreso */
        .btn-regresar {
            background-color: #6c757d;
            color: white;
            padding: 8px 12px;
            border: none;
            border-radius: 4px;
            text-decoration: none;
            font-size: 14px;
        }
        
        /* Contenedor de tabla responsive */
        .table-responsive {
            width: 100%;
            overflow-y: auto;
        }
        
        /* Estilos de tabla */
        table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid black;
            font-size: 0.9em;
        }
        
        th, td {
            padding: 10px;
            text-align: left;
            border: 1px solid black;
            vertical-align: middle;
        }
        
        th {
            background-color:rgba(7, 8, 66, 0.73);
            color: white;
            font-weight: bold;
        }

        tbody tr:hover {
            background-color:rgb(202, 194, 231);
        }

        .entrada { background-color: lightgreen; font-weight: 500; }
        .salida { background-color: lightcoral; font-weight: 500; }
        .ajuste { background-color: lightblue; font-weight: 500; }
        
        /* Estilos para impresión */
        @media print {
            body {
                padding: 30px;
            }
            .btn-regresar {
                display: none;
            }
            table {
                border: 1px solid black;
            }
            th, td {
                border: 1px solid black;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h3>Histórico Completo de Movimientos</h3>
            <a href="ver_almacen.php" class="btn-regresar">Regresar</a>
        </div>
        
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Tipo</th>
                        <th>Artículo</th>
                        <th>Código</th>
                        <th>Cantidad</th>
                        <th>OC/Fab</th>
                        <th>Usuario</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo date('d/m/Y H:i', strtotime($row['fecha_mov'])); ?></td>
                                <td class="<?php echo strtolower($row['tipo_mov']); ?>">
                                    <?php echo $row['tipo_mov']; ?>
                                </td>
                                <td><?php echo htmlspecialchars($row['item_desc']); ?></td>
                                <td><?php echo htmlspecialchars($row['codigo']); ?></td>
                                <td><?php echo $row['cantidad']; ?></td>
                                <td>
                                    <?php 
                                    if ($row['id_oc']) {
                                        echo "OC-" . $row['id_oc'];
                                    } elseif ($row['id_fab']) {
                                        echo "FAB-" . $row['id_fab'];
                                    } else {
                                        echo "N/A";
                                    }
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($row['usuario'] ?? 'N/A'); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center;">No se encontraron movimientos</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>