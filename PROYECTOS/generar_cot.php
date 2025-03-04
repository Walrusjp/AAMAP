<?php
include 'C:/xampp/htdocs/PAPELERIA/db_connect.php';
// Validar y sanitizar la entrada del ID del proyecto
$proyecto_id = filter_var($_GET['id'], FILTER_SANITIZE_STRING);
if ($proyecto_id === false || empty($proyecto_id)) {
    echo "ID de proyecto no válido.";
    exit();
}

// Consultar datos del proyecto y el cliente
$sql = "SELECT p.cod_fab, p.descripcion AS descripcion, p.etapa, p.observaciones, p.nombre AS nombre_proyecto, c.nombre_comercial AS nombre_cliente, 
               c.direccion AS ubicacion_cliente, c.comprador AS atencion_cliente, c.telefono AS telefono_cliente, 
               c.correo AS email_cliente, p.fecha_entrega
        FROM proyectos p
        LEFT JOIN clientes_p c ON p.id_cliente = c.id
        WHERE p.cod_fab =?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $proyecto_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "Proyecto no encontrado.";
    exit();
}

$proyecto = $result->fetch_assoc();

// Consultar las partidas del proyecto
$sqlPartidas = "SELECT pa.descripcion, pa.cantidad, pa.unidad_medida, pa.precio_unitario
                FROM partidas pa
                WHERE pa.cod_fab =?";

$stmtPartidas = $conn->prepare($sqlPartidas);
$stmtPartidas->bind_param("s", $proyecto_id);
$stmtPartidas->execute();
$resultPartidas = $stmtPartidas->get_result();
$partidas = $resultPartidas->fetch_all(MYSQLI_ASSOC);

// Generar el número de cotización (puedes usar tu propia lógica aquí)
$cotizacion_no = $proyecto_id;
?>