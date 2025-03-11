<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

require 'C:/xampp/htdocs/db_connect.php';
require 'generar_cot.php';

// Incluir la librería mPDF
require_once 'vendor/autoload.php';

// Crear una instancia de mPDF
$mpdf = new \Mpdf\Mpdf();

// Capturar el contenido HTML en una variable
ob_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cotización</title>
    <style>
       /* Estilos generales */
body {
    font-family: sans-serif; 
}

/* Estilos para tablas */.table-bordered td,.table-bordered th {
    border: 1px solid #000; /* Bordes negros en la tabla */
}

/* Estilos para la cabecera */.header-info {
    margin-bottom: 20px; 
}.header-info p {
    margin-bottom: 5px; /* Espacio reducido entre párrafos */
}

/* Estilos para el título de la cotización */
h4.text-center {
    font-weight: bold;
    margin-bottom: 20px; 
}

/* Estilos para la tabla de partidas */.table-bordered th {
    background-color: #f0f0f0; /* Color de fondo para encabezados */
    font-weight: bold;
}

/* Estilos para el pie de página */.table-borderless td {
    vertical-align: middle; 
}

/* Estilos para los datos bancarios */.table-bordered {
    margin-bottom: 10px; /* Eliminar margen inferior */
}
/* Estilos adicionales para la estructura de tabla */.main-table {
    width: 100%;
    border-collapse: collapse;
}.main-table td {
    vertical-align: top;
}

/* Estilos para la distribución de elementos */
#datos_contacto {
    vertical-align: top; /* Asegurar que el contenido se alinee arriba */
    font-size:.9em;
    line-height: 0.5;
}
#iso {
    vertical-align: top; /* Asegurar que el contenido se alinee arriba */
    line-height: 1.1;
}
#info {
    display: flex;
    margin-left: -260px;
    margin-bottom: 30px;
    text-align: center; /* Centrar el texto en la celda */
    font-size: 1.4em;
    line-height: 0.9;
}
.datos_vigencia {
    line-height: 0.5; /* Reducir el interlineado */
    font-size: 0.9em;
}.datos_bancarios {
    font-size: 0.7em; /* Reducir el tamaño de fuente */
}
#logo {
    width: 60%;
    padding-bottom: 50px;

}

#footer {
    background-color: #ff0!important; /* Amarillo con!important */
    font-weight: bold;
}.descuento { /* Estilo para filas de descuento */
    background-color: #0f0!important; /* Verde lima */

.cifras{
    line-height: 0.5;
    font-size: 0.4em;
}

@media print {
    td:empty::after {
        content: none;
    }
    .descuento {
        background-color: green !important;
        color: white !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
}
    </style>
</head>
<body>
    <table class="main-table">
        <tr>
            <td><img src="/assets/grupo_aamap.png" alt="Logo de la empresa" id="logo"></td>
            <td style="width: 50%; text-align: right;" id="iso">  
                <p>GRUPO AAMAP</p>
                <p>Código: AAMAP-VE-F-03</p>
                <p>Fecha de revisión: 24/06/2024</p>
                <p>Revisión: 00</p>
            </td>
        </tr>
        <tr>
            <td rowspan="1" style="width: 50%;" id="datos_contacto">  
                <p>CLIENTE: <?php echo $proyecto['nombre_cliente']; ?></p>  
                <p>UBICACIÓN: <?php echo $proyecto['ubicacion_cliente']; ?></p>  
                <p>TEL: <?php echo $proyecto['telefono_cliente']; ?></p>  
                <p>E-MAIL: <?php echo $proyecto['email_cliente']; ?></p>  
                <p>ATENCIÓN: Ing. Rosa Soto</p>  
            </td>
            <td style="width: 100%;" id="info">  
                <div class="info-content"> 
                    <p><b>Agregados, Aceros, Maquilas Prefabricadas S.A de C.V</b></p>
                    <p>AAM2102261R5</p>
                    <p>Camino a San Miguel #1537</p>
                    <p>Col. Granjas Mayorazgo Puebla, Pue.</p>
                    <p>C.P. 72480 TEL: 222-910-19-51</p>
                </div>
            </td>
        </tr>
        <tr class="gral">
            <td colspan="2" style="text-align: center;">
                <p>POR MEDIO DEL PRESENTE, DEJO A SUS ÓRDENES LA SIGUIENTE OFERTA.</p>
            </td>
        </tr>
        <tr class="gral">
            <td colspan="2" style="text-align: center;">
                <h4 class="text-center">Cotización No. <?php echo $cotizacion_no; ?></h4>  
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <table class="main-table">
                    <thead>
                        <tr>
                            <th>PARTIDA</th>
                            <th>DESCRIPCIÓN</th>
                            <th>CANT</th>
                            <th>UM</th>
                            <th>P.U</th>
                            <th>SUBTOTAL PARTIDA</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $subtotal = 0;
                        foreach ($partidas as $index => $partida) {
                            $subtotal_partida = $partida['cantidad'] * $partida['precio_unitario'];
                            $subtotal += $subtotal_partida;

                            echo "<tr>";
                            echo "<td>" . ($index + 1) . "</td>";
                            echo "<td>" . $partida['descripcion'] . "</td>";
                            echo "<td>" . $partida['cantidad'] . "</td>";
                            echo "<td>" . $partida['unidad_medida'] . "</td>";
                            echo "<td>$" . number_format($partida['precio_unitario'], 2) . "</td>";
                            echo "<td>$" . number_format($subtotal_partida, 2) . "</td>";
                            echo "</tr>";
                        }
                        ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="4" style="border: none;"></td>
                            <td class="text-right"><strong>SUBTOTAL:</strong></td>
                            <td class="text-right">$<?php echo number_format($subtotal, 2); ?></td>
                        </tr>
                        <tr>
                            <td colspan="4" style="border: none;"></td>
                            <td class="text-right"><strong>IVA:</strong></td>
                            <td class="text-right">$<?php echo number_format($subtotal * 0.16, 2); ?></td>
                        </tr>
                        <tr>
                            <td colspan="4" style="border: none;"></td>
                            <td class="text-right"><strong>TOTAL:</strong></td>
                            <td class="text-right">$<?php echo number_format($subtotal * 1.16, 2); ?></td>
                        </tr>
                    </tfoot>
                </table>
            </td>
        </tr>
        <tr>
            <td style="width: 50%;" class="datos_vigencia">
                <table class="table table-borderless">
                    <tr>
                        <td>VIGENCIA:</td>
                        <td>Enero 2025</td>  
                    </tr>
                    <tr>
                        <td>PRECIOS:</td>
                        <td>Sujetos a cambio sin previo aviso.</td>  
                    </tr>
                    <tr>
                        <td>MONEDA:</td>
                        <td>MXN</td>  
                    </tr>
                    <tr>
                        <td>COND. PAGO:</td>
                        <td>60% anticipo y 40% contra aviso de entrega</td>  
                    </tr>
                    <tr>
                        <td>L.A.B:</td>
                        <td>Puebla, Pue.</td>  
                    </tr>
                    <tr>
                        <td>TPO. ENTR:</td>
                        <td>A convenir con el cliente.</td>  
                    </tr>
                </table>
            </td>
            <td colspan="2" style="width: 50%;" class="datos_bancarios">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th colspan="5">DATOS BANCARIOS</th>
                        </tr>
                        <tr>
                            <th>NO. de cuenta</th>
                            <th>Banco</th>
                            <th>Moneda</th>
                            <th>Titular</th>
                            <th>CLABE</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>0116848915</td>
                            <td>BBVA</td>
                            <td>MXP</td>
                            <td>AGREGADOS, ACEROS, MAQUILAS PREFABRICADAS S.A de C.V</td>
                            <td>012650001168489159</td>
                        </tr>
                        <tr>
                            <td>0118249369</td>
                            <td>BBVA</td>
                            <td>USD</td>
                            <td>AGREGADOS, ACEROS, MAQUILAS PREFABRICADAS S.A de C.V</td>
                            <td>012650001182493697</td>
                        </tr>
                    </tbody>
                </table>
            </td>
        </tr>
        <tfoot>
            <tr>
                <td colspan="3" style="text-align: center;" id="footer">
                    <p>LA REVISIÓN FINAL DE LA PRESENTE COTIZACIÓN ES RESPONSABILIDAD DEL CLIENTE, DESPUÉS DE ENTREGADA LA MERCANCÍA NO SE ACEPTAN CAMBIOS NI DEVOLUCIONES</p>
                </td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
<?php
$html = ob_get_clean();

// Escribir el contenido HTML en el PDF
$mpdf->WriteHTML($html);

// Salida del PDF
$mpdf->Output('cotizacion.pdf', 'D');
?>