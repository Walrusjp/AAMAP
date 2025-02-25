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
		    border: solid 1px;
		    text-align: left; /* Alinea el contenido a la izquierda */
		    margin-bottom: 20px;
		    display: flex; /* Usa flexbox para alinear la imagen y el texto */
		    align-items: center; /* Centra verticalmente */
		}

		.header-text {
		    flex: 1; /* Ocupa el espacio restante */
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
        	border: 1px solid;
            margin-bottom: 0px;
        }
        .empresa-info p {
        	text-align: center;
            margin: 5px 0;
            font-size: 15px;
        }
        .cod_fab {
        	width: 12%;
        	border: 1px solid;
        }
        .cod_fab p {
        	font-size: 12px;
        	text-align: center;
        }
        .cliente-info {
        	border: 1px solid;
        	padding-top: 10px;
        }
        .cliente-info p {
            margin: 5px 0;
            font-size: 12px;
        }
        .partidas {
		    border: 1px solid;
		    padding-top: 10px;
		    margin-bottom: 20px;
		}

		.partida-header, .partida-row {
		    display: flex;
		    justify-content: space-between;
		    margin-bottom: 0; /* Elimina el margen inferior */
		    border: 1px solid;
		}

		.partida-header div, .partida-row div {
		    flex: 1;
		    text-align: center;
		    font-size: 12px;
		    padding: 5px; /* Ajusta el padding si es necesario */
		}

		/* Si quieres que los bordes se fusionen */
		.partidas {
		    border-collapse: collapse;
		}

        /* Contenedor flexible para nota y totales */
        .nota-totales-container {
            display: flex;
            justify-content: right;
            margin-bottom: 5px;
            margin-top: -20px;
            font-size: 12px;
        }

        /* Estilos para la nota */
        .nota {
            border: 1px solid;
            width: 60%; /* Ajusta el ancho según sea necesario */
            padding: 10px;
            box-sizing: border-box;
        }

        /* Estilos para los totales */
        .totales {
            border: 1px solid;
            width: 35%; /* Ajusta el ancho según sea necesario */
            padding: 10px;
            box-sizing: border-box;
            text-align: right; /* Alinea el texto a la derecha */
        }

        .footer {
            text-align: left;
            margin-bottom: 50px;
        }
        .footer p {
            margin: 5px 0;
            font-size: 12px;
        }
        .footer2 {
        	text-align: center;
        }
        /* Elimina position: absolute de la imagen */
		.imagen {
		    display: inline-block; /* Cambia a inline-block para alinear con el texto */
		    vertical-align: top; /* Alinea la imagen con el texto */
		}

		#logo {
		    width: 20%;
		    height: auto; /* Ajusta la altura automáticamente */
		    border: 1px solid;
		    margin-right: 10px; /* Espacio entre la imagen y el texto */
		}

		#logo img {
		    width: 100%;
		    height: auto; /* Mantiene la proporción de la imagen */
		}
        #datos {
        	width: 43%;
        	/*border: 1px solid;*/
        	top: 650px;
        	left: 350px;
        }
        #datos img {
        	width: 100%;
        }
        #ate {
        	text-align: center;
        }
        .partida-header {
        	border: 1px solid;
        }

    </style>
</head>
<body>
    
        <!-- Encabezado -->
		<div class="header">
		    <div class="imagen" id="logo">
		        <img src="/assets/aamap.png" alt="Grupo AAMAP">
		    </div>
		    <div class="header-text">
		        <h2>Cotización</h2>
		        <p>Código: AAMAP-VE-F-03</p>
		        <p>Fecha de revisión: 24/06/2024</p>
		        <p>Revisión: 00</p>
		    </div>
		</div>

        <!-- Información de la empresa -->
        <div class="empresa-info">
            <p><b>Agregados, Aceros, Maquilas Prefabricadas S.A de C.V</b></p>
            <p><b>AAM2102261R5</b></p>
            <p>Camino a San Miguel #1537</p>
            <p>Col. Granjas Mayorazgo, Puebla, Pue.</p>
            <p>C.P. 72480 TEL: 222-910-19-51</p>
            <div class="cod_fab">
            	<p>Código: <br> <?php echo htmlspecialchars($proyecto['cod_fab']); ?></p>
            </div>
        </div>

        <!-- Información del cliente -->
        <div class="cliente-info">
            <p>CLIENTE: <?php echo htmlspecialchars($proyecto['nombre_cliente']); ?></p>
            <p>UBICACIÓN: <?php echo htmlspecialchars($proyecto['ubicacion_cliente']); ?></p>
            <p>TEL: <?php echo htmlspecialchars($proyecto['telefono_cliente']); ?></p>
            <p>E-MAIL: <?php echo htmlspecialchars($proyecto['email_cliente']); ?></p>
        </div>

        <div class="cliente-info">
            <p>ATENCIÓN:</p>
            <p id="ate">POR MEDIO DEL PRESENTE, DEJO A SUS ORDENES LA SIGUIENTE OFERTA.</p>
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

        <!-- Nota y Totales -->
        <div class="nota-totales-container">
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
        </div>

        <!-- Pie de página -->
        <div class="footer">
        	<div class="vigencia">
	            <p>VIGENCIA: <?php echo date('m/Y'); ?></p>
	            <p>PRECIOS: Sujetos a cambio sin previo aviso</p>
	            <p>MONEDA: MXN/USD/EU</p>
	            <p>COND. PAGO: 60% anticipo y 40% contra aviso de entrega</p>
	            <p>L.A.B: Puebla, Pue.</p>
	            <p>TPO. ENTR: A convenir con el cliente</p>
	        </div>
	        <div class="imagen" id="datos">
            	<img src="/assets/datos.png" alt="Imagen 2">
        	</div>
        </div>

        <div class="footer2">
        	<p>LA REVISION FINAL DE LA PRESENTE COTIZACION ES RESPONSABILIDAD DEL CLIENTE, DESPUES DE ENTREGADA LA MERCANCIA NO SE ACEPTAN CAMBIOS NI DEVOLUCIONES</p>
        </div>
    
</body>
</html>