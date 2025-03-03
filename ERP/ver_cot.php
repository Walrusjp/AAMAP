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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cotización_<?php echo $cotizacion_no;?></title>
    <link rel="icon" href="/assets/logo.ico">
    <style>
        p, h1, h2, h3, h4, .content {text-align: center;}
        .interlineado {line-height: 0.4;}
        .interlineador {line-height: 0.1;}
        #cot {
            display: flex;
            font-size: 0.8em;
            border: 1px solid;
            border-bottom: none;
            border-right: none;
            line-height: 1.0;
            text-align: center;
            justify-content: center;
            align-items: center;
            margin-left: auto;
            width: fit-content;
            margin-top: -25px;
        }
        #cod1 {
            font-size: 0.8em;
            border-bottom: 1px solid;
            line-height: 0.3;
        }
        #cod2 {
            font-size: 0.8em;
            border-bottom: 1px solid;
            line-height: 0.3;
        }
        #cod3 {
            font-size: 0.8em;
            line-height: 0.3;
        }
        table {
            width: 100%;
            padding: 10px;
            border: 1px solid;
            border-spacing: 0;
            border-collapse: collapse;
        }
        .cols {
            border: 1px solid; font-size: 0.8em;
        }
        .bg-cols {
            background-color: lightgray;
            padding-top: 8px;
            padding-bottom: 8px;
        }
        .content {
            padding-top: 3px;
            padding-bottom: 3px;
        }
        .cliente {font-size: 0.8em;}
        .vigencia {font-size: 0.7em;}
        .cifras {line-height: 0;}

        @media print {
            .interlineado {line-height: 0.3;}
            .interlineador {line-height: 0.1; font-size: 0.7em}

        }
        .cols {font-size: 0.7em}
    </style>
</head>
<body style="padding: 10px; font-family: Arial;">
    <!-- Primera tabla: Distribución 80%-20% -->
    <table style="margin-bottom: 10px;">
        <tr>
            <td style="width: 80%; height: 30px; border: 1px solid;">
                <img src="/assets/grupo_aamap.png" style="width: 30%;">
            </td>
            <td style="width: 20%; height: auto; border: 1px solid;" class="interlineador">
                <div id="cod1"><p style="text-align: left;">Código: AAMAP-VE-F-03</p></div>
                <div id="cod2"><p style="text-align: left;">Fecha de revisión: 24/06/2024</p></div>
                <div id="cod3"><p style="text-align: left;">Revisión: 00</p></div>
            </td>
        </tr>
    </table>

    <!-- Segunda tabla: Información de la empresa -->
    <table style="border: 1px solid; border-bottom: none;">
        <tr>
            <td colspan="2" style="width: 100%;" class="interlineado">
                <p style="font-size: 1em;"><b>Agregados, Aceros, Maquilas Prefabricadas S.A de C.V</b></p>
                <p style="font-size: 1em;"><b>AAM2102261R5</b></p>
                <p style="font-size: 0.9em;">Camino a San Miguel #1537</p>
                <p style="font-size: 0.9em;">Col. Granjas Mayorazgo Puebla, Pue.</p>
                <p style="font-size: 0.9em;">C.P. 72480 TEL: 222-910-19-51</p>
                <div id="cot"><p style="padding-left: 8px; padding-right: 8px;">Cotización No. <br><?php echo $cotizacion_no;?></p></div>
            </td>
        </tr>
    </table>

    <!-- Tercera tabla: Distribución 20%-80% -->
    <table>
        <tr>
            <td style="width: 20%; border: 1px solid; border-right: none;" class="interlineado cliente">
                <p style="text-align: left;"><b><u>CLIENTE:</u></b></p>
                <p style="text-align: left;"><b><u>UBICACIÓN:</u></b></p>
                <p style="text-align: left;"><b><u>TEL:</u></b></p>
                <p style="text-align: left;"><b><u>E-MAIL:</u></b></p>
            </td>
            <td style="width: 80%; border: 1px solid; border-left: none;" class="interlineado cliente">
                <p style="text-align: left;"><?php echo $proyecto['nombre_cliente'];?></p>
                <p style="text-align: left;"><?php echo $proyecto['ubicacion_cliente'];?></p>
                <p style="text-align: left;"><?php echo $proyecto['telefono_cliente'];?></p>
                <p style="text-align: left;"><?php echo $proyecto['email_cliente'];?></p>
            </td>
        </tr>
        <tr>
            <td style="border: 1px solid; border-right: none; border-bottom: none;"><p style="text-align: left;" class="cliente"><b><u>ATENCIÓN:</u></b></p></td>
            <td style="border-bottom: none;"><P style="text-align: left;"><?php echo $proyecto['atencion_cliente'];?></P></td>
        </tr>
        <tr>
            <td colspan="2" style="border-bottom: 1px solid;" class="cliente"><p>POR MEDIO DEL PRESENTE, DEJO A SUS ORDENES LA SIGUIENTE OFERTA.</p></td>
        </tr>

        <tr><td><p style="font-size: 0.5em; border-bottom: none;">&nbsp;&nbsp;&nbsp;</p></td></tr>
    </table>

    <!--tabla de partidas-->
    <table>
        <thead>
            <tr>
                <th style="width: 10%; " class="cols bg-cols">PARTIDA</th>
                <th style="width: 30%; " class="cols bg-cols">DESCRIPCIÓN</th>
                <th style="width: 10%; " class="cols bg-cols">CANT</th>
                <th style="width: 10%; " class="cols bg-cols">UM</th>
                <th style="width: 15%; " class="cols bg-cols">P.U</th>
                <th style="width: 25%; " class="cols bg-cols">SUBTOTAL PARTIDA</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $subtotal = 0;
            foreach ($partidas as $index => $partida) {
                $subtotal_partida = $partida['cantidad'] * $partida['precio_unitario'];
                $subtotal += $subtotal_partida;

                echo "<tr>";
                echo "<td class='cols content'>" . ($index + 1) . "</td>";
                echo "<td class='cols content'>" . $partida['descripcion'] . "</td>";
                echo "<td class='cols content'>" . $partida['cantidad'] . "</td>";
                echo "<td class='cols content'>" . $partida['unidad_medida'] . "</td>";
                echo "<td class='cols content'>$" . number_format($partida['precio_unitario'], 2) . "</td>";
                echo "<td class='cols content'>$" . number_format($subtotal_partida, 2) . "</td>";
                echo "</tr>";
            }
            ?>
        </tbody>
    </table>

    <!--Tabla de totales y nota-->
    <table style="border-bottom: none;">
        <tr>
            <td class="cols cifras" style="width: 10%;"><p><b>NOTA:</b></p></td>
            <td class="cols cifras" style="width: 50%; padding-top: 5px; padding-bottom: 5px;"><p><?php echo $proyecto['descripcion']; ?></p></td>
            <td class="cols cifras" style="width: 15%;"><p><B>SUBTOTAL:</B></p></td>
            <td class="cols cifras" style="width: 25%;"><P>$<?php echo number_format($subtotal, 2); ?></P></td>
        </tr>
        <tr>
            <td class="cliente cifras" style="width: 10%;"></td>
            <td class="cliente cifras" style="width: 50%;"></td>
            <td class="cols cifras" style="width: 15%;"><p><B>IVA:</B></p></td>
            <td class="cols cifras" style="width: 25%;"><P>$<?php echo number_format($subtotal * 0.16, 2); ?></P></td>
        </tr>
        <tr>
            <td class="cliente cifras" style="width: 10%;"></td>
            <td class="cliente cifras" style="width: 50%;"></td>
            <td class="cols cifras" style="width: 15%;"><p><B>TOTAL:</B></p></td>
            <td class="cols cifras" style="width: 25%;"><P>$<?php echo number_format($subtotal * 1.16, 2); ?></P></td>
        </tr>
    </table>

    <!--tabla de daotos de vigencia y bancarios-->
    <table style="border-top: none">
        <tr><div id="data">
            <td class="interlineado vigencia" style="width: 15%;">
                <p style="text-align: left;"><b>VIGENCIA:</b></p>
                <p style="text-align: left;"><b>PRECIOS</b></p>
                <p style="text-align: left;"><b>MONEDA</b></p>
                <p style="text-align: left;"><b>COND. PAGO</b></p>
                <p style="text-align: left;"><b>L.A.B:</b></p>
                <p style="text-align: left;"><b>TPO. ENTR:</b></p>
            </td>
            <td class="interlineado vigencia" style="width: 40%;">
                <p style="text-align: left;">Enero 2025</p>
                <p style="text-align: left;">Sujetos a cambio sin previo aviso.</p>
                <p style="text-align: left;">MXN/USD/EU</p>
                <p style="text-align: left;">60% anticipo y 40% contra aviso de entrega</p>
                <p style="text-align: left;">Puebla, Pue.</p>
                <p style="text-align: left;">A convenir con el cliente</p>
            </td>
            <td style="width: 45%;">
                <img src="/assets/datos.png" style="width: 100%;">
            </td>
        </div></tr>

        <tr>
            <td colspan="6" style="background-color: yellow; line-height: 1;" class="vigencia">
                <p>LA REVISION FINAL DE LA PRESENTE COTIZACION ES REPONSABILIDAD DEL CLIENTE, DESPUES DE ENTREGADA LA MERCANCIA NO SE ACEPTAN CAMBIOS NI DEVOLUCIONES </p>
            </td>
        </tr>
    </table>
</body>
</html>
