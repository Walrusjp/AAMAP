<?php
// Configuración de la base de datos
$host = 'localhost';
$dbname = 'aamap';
$username = 'root';
$password = '44m4p_php';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

date_default_timezone_set("America/Mexico_City");

// Consulta 1: Proyectos finalizados
$queryFinalizados = "
    SELECT 
        p.cod_fab AS proyecto_id,
        p.nombre AS nombre_proyecto,
        of.id_fab AS orden_produccion_id,
        p.fecha_creacion AS fecha_inicio_proyecto,
        of.of_created AS fecha_inicio_produccion,
        p.fecha_entrega AS fecha_final_proyecto_esperada,
        of.updated_at AS fecha_finalizacion_real,
        DATEDIFF(of.updated_at, of.of_created) AS dias_produccion,
        DATEDIFF(of.of_created, p.fecha_creacion) AS dias_cotizacion,
        DATEDIFF(of.updated_at, p.fecha_creacion) AS dias_total_proyecto
    FROM 
        proyectos p
    JOIN 
        orden_fab of ON p.cod_fab = of.id_proyecto
    WHERE 
        p.etapa = 'finalizado'
    AND
        p.activo = 1
    ORDER BY 
        'fecha_inicio_proyecto' DESC
";

// Consulta 2: Proyectos en curso
$queryEnCurso = "
    SELECT 
        p.cod_fab AS proyecto_id,
        of.id_fab AS orden_fab_id,
        p.nombre AS nombre_proyecto,
        CASE 
            WHEN p.etapa = 'creado' THEN 'en proceso CRM'
            WHEN p.etapa = 'aprobado' THEN 'aprobado'
            WHEN p.etapa = 'rechazado' THEN 'no concretado'
            WHEN p.etapa = 'en proceso' THEN 'en proceso ERP'
            WHEN p.etapa = 'directo' THEN 'en proceso ERP (directas)'
            WHEN p.etapa = 'facturacion' THEN 'en facturación'
            ELSE p.etapa
        END AS estatus_actual,
        p.fecha_creacion AS fecha_inicio,
        of.of_created AS fecha_inicio_produccion,
        DATEDIFF(NOW(), p.fecha_creacion) AS dias_abierto,
        DATEDIFF(NOW(), of.of_created) AS dias_produccion
    FROM 
        proyectos p
    LEFT JOIN 
        orden_fab of ON p.cod_fab = of.id_proyecto
    WHERE 
        p.etapa != 'finalizado'
    AND
        p.activo = 1
    ORDER BY 
        'fecha_inicio' DESC
";

// Consulta para estadísticas generales
$queryEstadisticas = "
    SELECT 
        p.cod_fab,
        COUNT(*) AS total_proyectos,
        SUM(CASE WHEN etapa = 'finalizado' THEN 1 ELSE 0 END) AS finalizados,
        SUM(CASE WHEN etapa != 'finalizado' AND etapa != 'rechazado' THEN 1 ELSE 0 END) AS en_curso,
        SUM(CASE WHEN etapa = 'rechazado' THEN 1 ELSE 0 END) AS no_concretado,
        AVG(CASE WHEN etapa = 'finalizado' THEN DATEDIFF(of.updated_at, p.fecha_creacion) ELSE NULL END) AS promedio_dias_total,
        AVG(CASE WHEN p.etapa = 'finalizado' AND DATEDIFF(of.of_created, p.fecha_creacion) > 0 -- Excluye proyectos con 0 días en CRM
        THEN DATEDIFF(of.of_created, p.fecha_creacion) ELSE NULL END) AS promedio_dias_cotizacion,
        AVG(CASE WHEN etapa = 'finalizado' THEN DATEDIFF(of.updated_at, of.of_created) ELSE NULL END) AS promedio_dias_produccion
    FROM 
        proyectos p
    LEFT JOIN 
        orden_fab of ON p.cod_fab = of.id_proyecto
    WHERE
        p.activo = 1
";

// Consulta para distribución de etapas
$queryDistribucionEtapas = "
    SELECT 
        CASE 
            WHEN etapa = 'creado' THEN 'en proceso CRM'
            WHEN etapa = 'aprobado' THEN 'aprobado'
            WHEN etapa = 'rechazado' THEN 'no concretado'
            WHEN etapa = 'en proceso' THEN 'en proceso ERP'
            WHEN etapa = 'directo' THEN 'en proceso ERP (directas)'
            WHEN etapa = 'facturacion' THEN 'en facturación'
            WHEN etapa = 'finalizado' THEN 'finalizado'
            ELSE etapa
        END AS etapa_agrupada,
        COUNT(*) AS cantidad
    FROM 
        proyectos
    WHERE
        activo = 1
    GROUP BY 
        etapa_agrupada
    ORDER BY 
        cantidad DESC
";

// Ejecutar consultas
$finalizados = $pdo->query($queryFinalizados)->fetchAll(PDO::FETCH_ASSOC);
$enCurso = $pdo->query($queryEnCurso)->fetchAll(PDO::FETCH_ASSOC);
$estadisticas = $pdo->query($queryEstadisticas)->fetch(PDO::FETCH_ASSOC);
$distribucionEtapas = $pdo->query($queryDistribucionEtapas)->fetchAll(PDO::FETCH_ASSOC);

// Preparar datos para gráficos
$etiquetasGraficoPastel = [];
$datosGraficoPastel = [];
$coloresGraficoPastel = [];

foreach ($distribucionEtapas as $etapa) {
    $etiquetasGraficoPastel[] = $etapa['etapa_agrupada'];
    $datosGraficoPastel[] = $etapa['cantidad'];
    $coloresGraficoPastel[] = sprintf('#%06X', mt_rand(0, 0xFFFFFF));
}

// Preparar datos para gráfico de barras (etapas agrupadas)
$etiquetasGraficoBarras = ['en proceso CRM', 'aprobado', 'no concretado', 'en proceso ERP', 'en facturación', 'finalizado'];
$datosGraficoBarras = [0, 0, 0, 0, 0, 0]; // Inicializar contadores

foreach ($distribucionEtapas as $etapa) {
    switch($etapa['etapa_agrupada']) {
        case 'en proceso CRM':
            $datosGraficoBarras[0] += $etapa['cantidad'];
            break;
        case 'aprobado':
            $datosGraficoBarras[1] += $etapa['cantidad'];
            break;
        case 'no concretado':
            $datosGraficoBarras[2] += $etapa['cantidad'];
            break;
        case 'en proceso ERP':
        case 'en proceso ERP (directas)':
            $datosGraficoBarras[3] += $etapa['cantidad'];
            break;
        case 'en facturación':
            $datosGraficoBarras[4] += $etapa['cantidad'];
            break;
        case 'finalizado':
            $datosGraficoBarras[5] += $etapa['cantidad'];
            break;
    }
}

// Preparar datos para gráfico de pastel (CRM vs ERP)
$etiquetasGraficoPastel2 = ['CRM', 'ERP'];
$datosGraficoPastel2 = [
    $datosGraficoBarras[0] + $datosGraficoBarras[1], // CRM (en proceso + aprobado)
    $datosGraficoBarras[3] + $datosGraficoBarras[4]  // ERP (en proceso + facturación)
];
$coloresGraficoPastel2 = ['#FF6384', '#36A2EB'];

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Proyectos</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            color: #333;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .stat-card {
            background: #f8f9fa;
            border-left: 4px solid #007bff;
            padding: 15px;
            border-radius: 4px;
        }
        .stat-card:hover { 
            background-color:rgb(228, 228, 228);
            cursor: pointer;
        }
        .stat-card h3 {
            margin-top: 0;
            color: #007bff;
        }
        .stat-card .value {
            font-size: 24px;
            font-weight: bold;
        }
        .chart-container {
            margin-bottom: 30px;
            width: 100%;
            display: flex;
            justify-content: flex-start; /* Alinea a la izquierda */
        }
        
        .chart-box {
            margin: 0; /* Quitamos el margen automático */
        }
        #pastel1 { width: 70%; }
        #pastel2 { width: 63%; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        h1, h2 {
            color: #2c3e50;
        }
        .bold { font-weight: bold; }


        @media print {
            .card {
                page-break-after: always; /* Salto de página después de cada sección */
                page-break-inside: avoid; /* Evita dividir una sección entre páginas */
                break-after: page; /* Versión moderna para navegadores nuevos */
            }
            
            .card:last-child {
                page-break-after: auto; /* No agregar salto después de la última sección */
            }
            
            /* Ajustes adicionales para impresión */
            body {
                padding: 80px 40px;
                margin: 0;
                background: white;
                color: black;
            }
            
            .stat-card {
                border-left: 4px solid #ccc !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            /* Asegurar que los gráficos se vean bien al imprimir */
            canvas {
                max-width: 100% !important;
                height: auto !important;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Reporte de Proyectos</h1>
        
        <!-- Estadísticas generales -->
        <div class="card">
            <h2>Estadísticas Clave</h2>
            <div style="background: #f8f9fa; padding: 5px 10px; border-radius: 4px; font-weight: bold;">
                Reporte generado: <?= date('d/m/Y H:i') ?>
            </div>
            <div class="stats-container">
                <div class="stat-card">
                    <h3>Proyectos Totales</h3>
                    <div class="value"><?= $estadisticas['total_proyectos'] ?></div>
                </div>
                <div class="stat-card">
                    <h3>Proyectos Finalizados</h3>
                    <div class="value"><?= $estadisticas['finalizados'] ?></div>
                </div>
                <div class="stat-card">
                    <h3>Proyectos en Curso</h3>
                    <div class="value"><?= $estadisticas['en_curso'] ?></div>
                </div>
                <div class="stat-card">
                    <h3>Proyectos No Concretados</h3>
                    <div class="value"><?= $estadisticas['no_concretado'] ?></div>
                </div>
                <div class="stat-card">
                    <h3>Días promedio (total)</h3>
                    <div class="value"><?= round($estadisticas['promedio_dias_total'], 1) ?></div>
                </div>
                <div class="stat-card">
                    <h3>Días promedio (cotización)</h3>
                    <div class="value"><?= round($estadisticas['promedio_dias_cotizacion'], 1) ?></div>
                </div>
                <div class="stat-card">
                    <h3>Días promedio (producción)</h3>
                    <div class="value"><?= round($estadisticas['promedio_dias_produccion'], 1) ?></div>
                </div>
            </div>
        </div>
        
        <!-- Gráficos -->
        <div class="card">
            <h2>Distribución de Proyectos</h2>
            
            <!-- Primer gráfico: Pastel detallado -->
            <div class="chart-container">
                <div class="chart-box" id="pastel1">
                    <canvas id="pieChart"></canvas>
                </div>
            </div>
            
            <!-- Segundo gráfico: Pastel CRM vs ERP -->
            <div class="chart-container">
                <div class="chart-box" id="pastel2">
                    <canvas id="pieChart2"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Tabla de proyectos en curso -->
        <div class="card">
            <h2>Proyectos en Curso (<?= count($enCurso) ?>)</h2>
            <table>
                <thead>
                    <tr>
                        <th style="width: 11%; ">ID Proyecto</th>
                        <th style="width: 10%; ">ID OF</th>
                        <th style="width: 28%; ">Nombre</th>
                        <th style="width: 14%; ">Etapa</th>
                        <th style="width: 10%; ">Inicio CRM</th>
                        <th style="width: 10%; ">Inicio ERP</th>
                        <th style="width: 6%; ">Días Abierto</th>
                        <th style="width: 10%; ">Días Prod.</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($enCurso as $proyecto): ?>
                    <tr>
                        <td><?= htmlspecialchars($proyecto['proyecto_id']) ?></td>
                        <td class="<?= $proyecto['orden_fab_id'] === null ? 'text-danger bold' : '' ?>">
                            <?= htmlspecialchars($proyecto['orden_fab_id'] ?? 'Sin alta en ERP') ?>
                        </td>
                        <td><?= htmlspecialchars($proyecto['nombre_proyecto']) ?></td>
                        <td><?= htmlspecialchars($proyecto['estatus_actual']) ?></td>
                        <td><?= date('d/m/Y', strtotime($proyecto['fecha_inicio'])) ?></td>
                        <td class="<?= $proyecto['fecha_inicio_produccion'] === null ? 'text-danger bold' : '' ?>">
                            <?= isset($proyecto['fecha_inicio_produccion']) ? date('d/m/Y', strtotime($proyecto['fecha_inicio_produccion'])) : 'Sin alta en ERP' ?>
                        </td>
                        <td><?= $proyecto['dias_abierto'] ?></td>
                        <td class="<?= $proyecto['orden_fab_id'] === null ? 'text-danger bold' : '' ?>">
                            <?= $proyecto['dias_produccion'] ?? 'Sin alta en ERP' ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Tabla de proyectos finalizados -->
        <div class="card">
            <h2>Proyectos Finalizados (<?= count($finalizados) ?>)</h2>
            <table>
                <thead>
                    <tr>
                        <th style="width: 11%; ">ID Proyecto</th>
                        <th style="width: 8%; ">ID OF</th>
                        <th style="width: 33%; ">Nombre</th>
                        <th style="width: 9%; ">Inicio CRM</th>
                        <th style="width: 9%; ">Inicio ERP</th>
                        <th style="width: 7%; ">Entrega Esperada</th>
                        <th style="width: 7%; ">Entrega Real</th>
                        <th style="width: 6%; ">Días CRM</th>
                        <th style="width: 6%; ">Días ERP</th>
                        <th style="width: 4%; ">Total Días</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($finalizados as $proyecto): ?>
                    <tr>
                        <td><?= htmlspecialchars($proyecto['proyecto_id']) ?></td>
                        <td>OF-<?= htmlspecialchars($proyecto['orden_produccion_id']) ?></td>
                        <td><?= htmlspecialchars($proyecto['nombre_proyecto']) ?></td>
                        <td><?= date('d/m/Y', strtotime($proyecto['fecha_inicio_proyecto'])) ?></td>
                        <td><?= date('d/m/Y', strtotime($proyecto['fecha_inicio_produccion'])) ?></td>
                        <td><?= date('d/m/Y', strtotime($proyecto['fecha_final_proyecto_esperada'])) ?></td>
                        <td><?= date('d/m/Y', strtotime($proyecto['fecha_finalizacion_real'])) ?></td>
                        <td><?= $proyecto['dias_cotizacion'] ?></td>
                        <td><?= $proyecto['dias_produccion'] ?></td>
                        <td><?= $proyecto['dias_total_proyecto'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Gráfico de pastel - Distribución de etapas detallada
        const pieCtx = document.getElementById('pieChart').getContext('2d');
        const pieChart = new Chart(pieCtx, {
            type: 'pie',
            data: {
                labels: <?= json_encode(array_map(function($label, $data) {
                    return $label.' ('.$data.')';
                }, $etiquetasGraficoPastel, $datosGraficoPastel)) ?>,
                datasets: [{
                    data: <?= json_encode($datosGraficoPastel) ?>,
                    backgroundColor: [
                        '#198754', // success
                        '#ffc107', // warning
                        '#FFCE56', // Amarillo
                        '#FF9F40', // naranja
                        '#dc3545', // danger
                        '#0dcaf0'  // info
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            font: {
                                size: 12
                            }
                        }
                    },
                    tooltip: {
                        enabled: true // Desactivamos el tooltip ya que los datos están en la etiqueta
                    },
                    title: {
                        display: true,
                        text: 'Distribución por Etapa Detallada',
                        font: {
                            size: 14
                        }
                    }
                }
            }
        });


        // Gráfico de pastel - CRM vs ERP
        const pieCtx2 = document.getElementById('pieChart2').getContext('2d');
        const pieChart2 = new Chart(pieCtx2, {
            type: 'pie',
            data: {
                labels: <?= json_encode(array_map(function($label, $data) {
                    return $label.' ('.$data.')';
                }, $etiquetasGraficoPastel2, $datosGraficoPastel2)) ?>,
                datasets: [{
                    data: <?= json_encode($datosGraficoPastel2) ?>,
                    backgroundColor: ['#0d6efd', '#0dcaf0'], // Rojo para CRM, Verde para ERP
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            font: {
                                size: 12
                            }
                        }
                    },
                    tooltip: {
                        enabled: true
                    },
                    title: {
                        display: true,
                        text: 'Distribución CRM vs ERP',
                        font: {
                            size: 14
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>