<?php
session_start();
if (!isset($_SESSION['username']) || !isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit();
}

require 'C:/xampp/htdocs/db_connect.php';

// Manejar la solicitud POST (envío del formulario)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_oc = $_POST['id_oc'] ?? null;
    $accion = $_POST['accion'] ?? null;
    $id_usuario = $_SESSION['user_id'];
    $observaciones = $_POST['observaciones'] ?? '';

    // Validaciones
    if (!$id_oc || !$accion) {
        die(json_encode(['error' => 'Solicitud inválida']));
    }

    $acciones_validas = ['aprobar', 'rechazar'];
    if (!in_array($accion, $acciones_validas)) {
        die(json_encode(['error' => 'Acción no válida']));
    }

    // Validar observaciones para rechazos
    if ($accion == 'rechazar' && empty(trim($observaciones))) {
        die(json_encode(['error' => 'Las observaciones son obligatorias para rechazos']));
    }

    $nuevo_estatus = ($accion == 'aprobar') ? 'autorizado' : 'rechazado';

    // Insertar en la base de datos
    $stmt = $conn->prepare("INSERT INTO logs_estatus_oc (id_oc, estatus, id_usuario, observaciones, fecha_cambio) VALUES (?, ?, ?, ?, NOW())");
    $stmt->bind_param("isis", $id_oc, $nuevo_estatus, $id_usuario, $observaciones);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'estatus' => $nuevo_estatus]);
    } else {
        echo json_encode(['error' => 'Error al actualizar estatus: ' . $stmt->error]);
    }
    exit();
}

// Manejar la solicitud GET (mostrar prompt)
$id_oc = $_GET['id'] ?? null;
$accion = $_GET['accion'] ?? null;

if (!$id_oc || !$accion) {
    die("<script>alert('Parámetros faltantes'); window.history.back();</script>");
}
?>
<script>
// Función para mostrar el prompt y procesar la respuesta
function procesarAccion() {
    var mensaje = '<?php echo ($accion == "rechazar") ? "Ingrese motivo del rechazo:" : "Ingrese observaciones (opcional):"; ?>';
    var observaciones = prompt(mensaje);
    
    if (observaciones === null) {
        // Usuario canceló
        window.history.back();
        return;
    }
    
    // Validación para rechazos
    if ('<?php echo $accion; ?>' === 'rechazar' && observaciones.trim() === '') {
        alert('Debe ingresar observaciones para rechazar');
        window.history.back();
        return;
    }
    
    // Enviar datos al servidor
    fetch('aprobar_oc.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'id_oc=<?php echo $id_oc; ?>&accion=<?php echo $accion; ?>&observaciones=' + encodeURIComponent(observaciones)
    })
    .then(response => response.json())
    .then(data => {
        if (data.error) {
            alert(data.error);
            window.history.back();
        } else {
            alert('Orden marcada como ' + data.estatus);
            window.location.href = 'ver_ordenes_compra.php?msg=success';
        }
    })
    .catch(error => {
        alert('Error en la comunicación con el servidor');
        console.error(error);
        window.history.back();
    });
}

// Ejecutar inmediatamente al cargar
procesarAccion();
</script>