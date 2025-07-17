<?php
session_start();
if (!isset($_SESSION['username']) || !isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit();
}

require 'C:/xampp/htdocs/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_oc = intval($_POST['id_oc'] ?? 0);
    $observaciones = $_POST['notas'] ?? '';
    $recibidos = $_POST['recibido'] ?? [];

    if ($id_oc <= 0 || empty($recibidos)) {
        exit('Error: Datos incompletos');
    }

    // Iniciar transacción para asegurar consistencia
    $conn->begin_transaction();

    try {
        // Actualizar cantidades recibidas
        foreach ($recibidos as $id_detalle => $cantidad) {
            $cantidad = intval($cantidad);

            if ($cantidad < 0) continue;

            // Obtener datos actuales del detalle
            $stmt = $conn->prepare("SELECT cantidad, recibido FROM detalle_orden_compra WHERE id_detalle = ?");
            $stmt->bind_param("i", $id_detalle);
            $stmt->execute();
            $stmt->bind_result($cantidad_total, $ya_recibido);
            $stmt->fetch();
            $stmt->close();

            $pendiente = $cantidad_total - $ya_recibido;

            if ($cantidad > $pendiente) {
                throw new Exception("Error: Cantidad a recibir mayor que la pendiente en producto ID $id_detalle");
            }

            // Actualizar recibido
            $stmt = $conn->prepare("UPDATE detalle_orden_compra SET recibido = recibido + ? WHERE id_detalle = ?");
            $stmt->bind_param("ii", $cantidad, $id_detalle);
            $stmt->execute();
            $stmt->close();
        }

        // Verificar si se completó toda la orden
        $stmt = $conn->prepare("SELECT SUM(cantidad) as total, SUM(recibido) as recibido FROM detalle_orden_compra WHERE id_oc = ?");
        $stmt->bind_param("i", $id_oc);
        $stmt->execute();
        $result = $stmt->get_result();
        $totales = $result->fetch_assoc();
        $stmt->close();

        $id_usuario = $_SESSION['user_id'] ?? 0;
        $estatus = ($totales['total'] == $totales['recibido']) ? 'almacen' : 'parcial';

        // Registrar en logs_estatus_oc
        $stmt = $conn->prepare("INSERT INTO logs_estatus_oc (id_oc, estatus, id_usuario, observaciones, fecha_cambio) 
                               VALUES (?, ?, ?, ?, NOW())");
        $stmt->bind_param("isis", $id_oc, $estatus, $id_usuario, $observaciones);
        $stmt->execute();
        $stmt->close();

        $conn->commit();
        exit('ok');
    } catch (Exception $e) {
        $conn->rollback();
        exit($e->getMessage());
    }
}

// Resto del código para mostrar el formulario...
$id_oc = intval($_GET['id_oc'] ?? 0);
if ($id_oc <= 0) {
    die("ID de orden inválido");
}

$stmt = $conn->prepare("
    SELECT 
        d.id_detalle,
        d.id_alm,
        ia.codigo,
        ia.descripcion,
        ia.unidad_medida,
        d.cantidad,
        d.recibido
    FROM detalle_orden_compra d
    JOIN inventario_almacen ia ON d.id_alm = ia.id_alm
    WHERE d.id_oc = ? AND d.activo = 1
");
$stmt->bind_param("i", $id_oc);
$stmt->execute();
$result = $stmt->get_result();
$detalles = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$conn->close();
?>

<h5>Detalles de la orden:</h5>
<form id="formRecepcionParcialInner" method="post">
    <input type="hidden" name="id_oc" value="<?php echo $id_oc; ?>">
    <table class="table table-bordered table-sm">
        <tbody>
            <?php foreach ($detalles as $item): 
                $pendiente = $item['cantidad'] - $item['recibido'];
            ?>
                <tr>
                    <td>
                        <?php echo htmlspecialchars($item['codigo'] . ' - ' . $item['descripcion']); ?>
                        (<?php echo $item['unidad_medida']; ?>)
                    </td>
                    <td>
                        <small class="text-muted">
                            Solicitado: <?php echo $item['cantidad']; ?> —
                            Recibido: <?php echo $item['recibido']; ?> —
                            <strong>Pendiente: <?php echo $pendiente; ?></strong>
                        </small>
                    </td>
                    <td>
                        <input type="number" 
                            name="recibido[<?php echo $item['id_detalle']; ?>]" 
                            class="form-control form-control-sm"
                            min="0" 
                            max="<?php echo $pendiente; ?>" 
                            placeholder="Cantidad"
                            <?php if ($pendiente == 0): ?> disabled <?php endif; ?>>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <div class="form-group">
        <label for="inputNotas">Notas</label>
        <textarea name="notas" class="form-control" rows="3" required></textarea>
    </div>
    <div id="respuestaRecepcion" class="text-success"></div>
    <button type="submit" class="btn btn-primary">Registrar Recepción</button>
</form>

<script>
$('#formRecepcionParcialInner').submit(function(e) {
    e.preventDefault();
    $.ajax({
        url: 'recepcion_parcial.php',
        type: 'POST',
        data: $(this).serialize(),
        success: function(response) {
            if (response.trim() === 'ok') {
                $('#respuestaRecepcion').text("Recepción registrada correctamente.");
                setTimeout(() => {
                    $('#modalRecepcionParcial').modal('hide');
                    location.reload();
                }, 1500);
            } else {
                $('#respuestaRecepcion').addClass('text-danger').text("Error: " + response);
            }
        },
        error: function(xhr) {
            $('#respuestaRecepcion').addClass('text-danger').text("Error: " + xhr.responseText);
        }
    });
});
</script>