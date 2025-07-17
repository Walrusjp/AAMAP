<?php
session_start();
if (!isset($_SESSION['username']) || !isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit();
}

require 'C:/xampp/htdocs/db_connect.php';

// Manejar la solicitud POST (envío desde el prompt)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_oc = $_POST['id_oc'] ?? null;
    $id_usuario = $_SESSION['user_id'];
    $observaciones = $_POST['observaciones'] ?? '';

    // Validaciones básicas
    if (!$id_oc) {
        die(json_encode(['error' => 'ID de orden no válido']));
    }

    // Verificar si ya está pagada
    $stmt_check = $conn->prepare("SELECT 1 FROM logs_estatus_oc WHERE id_oc = ? AND estatus = 'pago'");
    $stmt_check->bind_param("i", $id_oc);
    $stmt_check->execute();
    $stmt_check->store_result();

    if ($stmt_check->num_rows > 0) {
        die(json_encode(['error' => 'Esta orden ya fue marcada como pagada']));
    }
    $stmt_check->close();

    // Insertar en la base de datos
    $stmt = $conn->prepare("INSERT INTO logs_estatus_oc (id_oc, estatus, id_usuario, observaciones, fecha_cambio) VALUES (?, 'pago', ?, ?, NOW())");
    $stmt->bind_param("iss", $id_oc, $id_usuario, $observaciones);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'estatus' => 'pago']);
    } else {
        echo json_encode(['error' => 'Error al registrar pago: ' . $stmt->error]);
    }
    exit();
}

// Manejar la solicitud GET (mostrar prompt)
$id_oc = intval($_GET['id'] ?? 0);
if ($id_oc <= 0) {
    die("<script>alert('ID de orden no válido'); window.history.back();</script>");
}

// Verificar permisos (solo admin y contabilidad)
if (!in_array($_SESSION['role'], ['admin', 'contabilidad'])) {
    die("<script>alert('No tienes permisos para registrar pagos'); window.history.back();</script>");
}
?>
<script>
// Mostrar prompt y procesar
var observaciones = prompt('Ingrese observaciones del pago (opcional):');
    
if (observaciones === null) {
    // Usuario canceló
    window.history.back();
} else {
    // Enviar datos al servidor
    fetch('registrar_pago.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'id_oc=<?php echo $id_oc; ?>&observaciones=' + encodeURIComponent(observaciones)
    })
    .then(response => response.json())
    .then(data => {
        if (data.error) {
            alert(data.error);
            window.history.back();
        } else {
            alert('Orden marcada como PAGADA');
            window.location.href = 'ver_ordenes_compra.php?msg=success';
        }
    })
    .catch(error => {
        alert('Error en la comunicación con el servidor');
        console.error(error);
        window.history.back();
    });
}
</script>
<?php
$conn->close();
?>