<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

include 'C:/xampp/htdocs/PAPELERIA/db_connect.php';
require 'generar_cot.php';

 ?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cotización</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #fff;
        }
        .cotizacion-container {
            width: 800px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #000;
            box-sizing: border-box;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h2 {
            margin: 0;
            font-size: 24px;
        }
        .header p {
            margin: 5px 0;
            font-size: 12px;
        }
        .empresa-info {
            margin-bottom: 20px;
        }
        .empresa-info p {
            margin: 5px 0;
            font-size: 12px;
        }
        .cliente-info {
            margin-bottom: 20px;
        }
        .cliente-info p {
            margin: 5px 0;
            font-size: 12px;
        }
        .partidas {
            margin-bottom: 20px;
        }
        .partida-header, .partida-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .partida-header div, .partida-row div {
            flex: 1;
            text-align: center;
            font-size: 12px;
        }
        .totales {
            margin-bottom: 20px;
        }
        .totales p {
            margin: 5px 0;
            font-size: 12px;
        }
        .footer {
            text-align: center;
            margin-bottom: 20px;
        }
        .footer p {
            margin: 5px 0;
            font-size: 12px;
        }
        .nota {
            margin-bottom: 20px;
        }
        .nota p {
            margin: 5px 0;
            font-size: 12px;
        }
        .imagen {
            position: absolute;
        }
        #logo {
        	top: 10px;
        	left: 30px;
        }
        #logo img {
        	width: 30%;
        }
    </style>
</head>
<body>
    <div class="cotizacion-container">
        <!-- Encabezado -->
        <div class="header">
            <h2>Cotización</h2>
            <p>Código: <?php echo htmlspecialchars($proyecto['cod_fab']); ?></p>
            <p>Fecha de revisión: 24/06/2024</p>
            <p>Revisión: 00</p>
        </div>

        <!-- Información de la empresa -->
        <div class="empresa-info">
            <p>Agregados, Aceros, Maquilas Prefabricadas S.A de C.V</p>
            <p>AAM2102261R5</p>
            <p>Camino a San Miguel #1537</p>
            <p>Col. Granjas Mayorazgo, Puebla, Pue.</p>
            <p>C.P. 72480 TEL: 222-910-19-51</p>
        </div>

        <!-- Información del cliente -->
        <div class="cliente-info">
            <p>CLIENTE: <?php echo htmlspecialchars($proyecto['nombre_cliente']); ?></p>
            <p>UBICACIÓN: <?php echo htmlspecialchars($proyecto['ubicacion_cliente']); ?></p>
            <p>TEL: <?php echo htmlspecialchars($proyecto['telefono_cliente']); ?></p>
            <p>E-MAIL: <?php echo htmlspecialchars($proyecto['email_cliente']); ?></p>
        </div>

        <!-- Partidas -->
        <div class="partidas">
            <div class="partida-header">
                <div>PARTIDA</div>
                <div>DESCRIPCION</div>
                <div>CANT</div>
                <div>UM</div>
                <div>P.U</div>
                <div>SUBTOTAL</div>
            </div>
            <?php foreach ($partidas as $partida): ?>
                <div class="partida-row">
                    <div><?php echo htmlspecialchars($partida['descripcion']); ?></div>
                    <div><?php echo htmlspecialchars($partida['cantidad']); ?></div>
                    <div><?php echo htmlspecialchars($partida['unidad_medida']); ?></div>
                    <div><?php echo htmlspecialchars($partida['precio_unitario']); ?></div>
                    <div><?php echo htmlspecialchars($partida['cantidad'] * $partida['precio_unitario']); ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Nota -->
        <div class="nota">
            <p>NOTA: descripcion (tabla proyectos)</p>
        </div>

        <!-- Totales -->
        <div class="totales">
            <p>SUBTOTAL: <?php echo htmlspecialchars(array_sum(array_map(function($partida) { return $partida['cantidad'] * $partida['precio_unitario']; }, $partidas))); ?></p>
            <p>IVA: <?php echo htmlspecialchars(array_sum(array_map(function($partida) { return $partida['cantidad'] * $partida['precio_unitario']; }, $partidas)) * 0.16); ?></p>
            <p>TOTAL: <?php echo htmlspecialchars(array_sum(array_map(function($partida) { return $partida['cantidad'] * $partida['precio_unitario']; }, $partidas)) * 1.16); ?></p>
        </div>

        <!-- Pie de página -->
        <div class="footer">
            <p>VIGENCIA: <?php echo date('m/Y'); ?></p>
            <p>PRECIOS: Sujetos a cambio sin previo aviso</p>
            <p>MONEDA: MXN/USD/EU</p>
            <p>COND. PAGO: 60% anticipo y 40% contra aviso de entrega</p>
            <p>L.A.B: Puebla, Pue.</p>
            <p>TPO. ENTR: A convenir con el cliente</p>
            <p>LA REVISION FINAL DE LA PRESENTE COTIZACION ES RESPONSABILIDAD DEL CLIENTE, DESPUES DE ENTREGADA LA MERCANCIA NO SE ACEPTAN CAMBIOS NI DEVOLUCIONES</p>
        </div>

        <!-- Imágenes -->
        <div class="imagen" id="logo">
            <img src="/assets/aamap.png" alt="Grupo AAMAP">
        </div>
        <div class="imagen" id="datos">
            <img src="ruta/a/imagen2.jpg" alt="Imagen 2">
        </div>
    </div>
</body>
</html>