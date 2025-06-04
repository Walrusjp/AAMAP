<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: /login.php");
    exit();
}

require 'C:/xampp/htdocs/db_connect.php';
require 'C:/xampp/htdocs/role.php';

// Obtener ID del proveedor a editar
$id_pr = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_pr <= 0) {
    header("Location: ver_proveedores.php");
    exit();
}

// Obtener datos del proveedor (solo para mostrar el nombre)
$sqlProveedor = "SELECT empresa FROM proveedores WHERE id_pr = ?";
$stmtProveedor = $conn->prepare($sqlProveedor);
$stmtProveedor->bind_param('i', $id_pr);
$stmtProveedor->execute();
$resultProveedor = $stmtProveedor->get_result();

if ($resultProveedor->num_rows === 0) {
    header("Location: ver_proveedores.php");
    exit();
}

$proveedor = $resultProveedor->fetch_assoc();

// Obtener referencias bancarias del proveedor
$refBancarias = [];
$sqlRefBancarias = "SELECT * FROM ref_bancarias_prov WHERE id_pr = ?";
$stmtRefBancarias = $conn->prepare($sqlRefBancarias);
$stmtRefBancarias->bind_param('i', $id_pr);
$stmtRefBancarias->execute();
$resultRefBancarias = $stmtRefBancarias->get_result();

if ($resultRefBancarias->num_rows > 0) {
    while ($row = $resultRefBancarias->fetch_assoc()) {
        $refBancarias[] = $row;
    }
}

// Procesar el formulario de edición
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nuevas_ref = json_decode($_POST['ref_bancarias'], true);

    $conn->begin_transaction();
    try {
        // Eliminar todas las referencias bancarias existentes
        $sqlDeleteRefs = "DELETE FROM ref_bancarias_prov WHERE id_pr = ?";
        $stmtDeleteRefs = $conn->prepare($sqlDeleteRefs);
        $stmtDeleteRefs->bind_param('i', $id_pr);
        $stmtDeleteRefs->execute();

        // Insertar las nuevas referencias bancarias
        if (!empty($nuevas_ref)) {
            $sqlInsertRef = "INSERT INTO ref_bancarias_prov 
                           (id_pr, banco, cuenta_bancaria, cuenta_convenio, clabe, clave_banco)
                           VALUES (?, ?, ?, ?, ?, ?)";
            $stmtInsertRef = $conn->prepare($sqlInsertRef);
            
            foreach ($nuevas_ref as $ref) {
                $stmtInsertRef->bind_param(
                    'isssss', 
                    $id_pr, 
                    $ref['banco'], 
                    $ref['cuenta_bancaria'], 
                    $ref['cuenta_convenio'], 
                    $ref['clabe'], 
                    $ref['clave_banco']
                );
                $stmtInsertRef->execute();
            }
        }

        $conn->commit();
        echo "<script>alert('Referencias bancarias actualizadas exitosamente.'); window.location.href = 'ver_proveedores.php';</script>";
    } catch (Exception $e) {
        $conn->rollback();
        echo "<script>alert('Error al actualizar: " . $e->getMessage() . "');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Referencias Bancarias</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="icon" href="/assets/logo.png" type="image/png">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body style="background-color: rgba(211, 211, 211, 0.4) !important;">

<div class="container">
    <h1 class="text-center">Editar Referencias Bancarias</h1>
    <h3 class="text-center">Proveedor: <?php echo htmlspecialchars($proveedor['empresa']); ?></h3>
    <a href="ver_proveedores.php" class="btn btn-secondary">Regresar</a>
    <p>&nbsp;&nbsp;&nbsp;&nbsp;</p>
    <form id="refBancariasForm" method="POST" action="editar_ref_pr.php?id=<?php echo $id_pr; ?>">
        <div class="form-row">
            <div class="col">
                <input type="text" class="form-control" id="banco" placeholder="Banco" >
            </div>
            <div class="col">
                <input type="text" class="form-control" id="cuenta_bancaria" placeholder="Cuenta Bancaria">
            </div>
            <div class="col">
                <input type="text" class="form-control" id="cuenta_convenio" placeholder="Cuenta Convenio">
            </div>
            <div class="col">
                <input type="text" class="form-control" id="clabe" placeholder="CLABE">
            </div>
            <div class="col">
                <input type="text" class="form-control" id="clave_banco" placeholder="Clave Banco">
            </div>
            <div class="col">
                <button type="button" class="btn btn-primary" id="addRefBancaria">Agregar</button>
            </div>
        </div>

        <table class="table mt-3">
            <thead>
                <tr>
                    <th>Banco</th>
                    <th>Cuenta Bancaria</th>
                    <th>Cuenta Convenio</th>
                    <th>CLABE</th>
                    <th>Clave Banco</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody id="refBancariasTable">
                <?php foreach ($refBancarias as $index => $ref): ?>
                <tr data-index="<?php echo $index; ?>">
                    <td><input type="text" class="form-control" name="ref[<?php echo $index; ?>][banco]" value="<?php echo htmlspecialchars($ref['banco']); ?>"></td>
                    <td><input type="text" class="form-control" name="ref[<?php echo $index; ?>][cuenta_bancaria]" value="<?php echo htmlspecialchars($ref['cuenta_bancaria'] ?? ''); ?>"></td>
                    <td><input type="text" class="form-control" name="ref[<?php echo $index; ?>][cuenta_convenio]" value="<?php echo htmlspecialchars($ref['cuenta_convenio'] ?? ''); ?>"></td>
                    <td><input type="text" class="form-control" name="ref[<?php echo $index; ?>][clabe]" value="<?php echo htmlspecialchars($ref['clabe'] ?? ''); ?>"></td>
                    <td><input type="text" class="form-control" name="ref[<?php echo $index; ?>][clave_banco]" value="<?php echo htmlspecialchars($ref['clave_banco'] ?? ''); ?>"></td>
                    <td><button type="button" class="btn btn-danger btn-sm removeRefBancaria">Eliminar</button></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <input type="hidden" name="ref_bancarias" id="ref_bancarias" value='<?php echo json_encode($refBancarias); ?>'>
        <button type="submit" class="btn btn-success btn-block">Actualizar Referencias Bancarias</button>
    </form>
</div>

<script>
    // Inicializar el array con las referencias existentes
    let refBancarias = <?php echo json_encode($refBancarias); ?> || [];

    function agregarRefBancaria() {
        const banco = $('#banco').val();
        const cuenta_bancaria = $('#cuenta_bancaria').val();
        const cuenta_convenio = $('#cuenta_convenio').val();
        const clabe = $('#clabe').val();
        const clave_banco = $('#clave_banco').val();

        if (banco) {
            const nuevaRef = { 
                banco, 
                cuenta_bancaria, 
                cuenta_convenio, 
                clabe, 
                clave_banco 
            };
            
            refBancarias.push(nuevaRef);
            
            const newIndex = refBancarias.length - 1;
            $('#refBancariasTable').append(`
                <tr data-index="${newIndex}">
                    <td><input type="text" class="form-control" name="ref[${newIndex}][banco]" value="${banco}"></td>
                    <td><input type="text" class="form-control" name="ref[${newIndex}][cuenta_bancaria]" value="${cuenta_bancaria}"></td>
                    <td><input type="text" class="form-control" name="ref[${newIndex}][cuenta_convenio]" value="${cuenta_convenio}"></td>
                    <td><input type="text" class="form-control" name="ref[${newIndex}][clabe]" value="${clabe}"></td>
                    <td><input type="text" class="form-control" name="ref[${newIndex}][clave_banco]" value="${clave_banco}"></td>
                    <td><button type="button" class="btn btn-danger btn-sm removeRefBancaria">Eliminar</button></td>
                </tr>
            `);
            
            // Limpiar campos
            $('#banco').val('');
            $('#cuenta_bancaria').val('');
            $('#cuenta_convenio').val('');
            $('#clabe').val('');
            $('#clave_banco').val('');
        }
    }

    $('#addRefBancaria').click(agregarRefBancaria);

    $(document).on('click', '.removeRefBancaria', function () {
        const row = $(this).closest('tr');
        const index = row.data('index');
        refBancarias.splice(index, 1);
        row.remove();
        
        // Reindexar las filas restantes
        $('#refBancariasTable tr').each(function(newIndex) {
            $(this).data('index', newIndex);
            $(this).find('input').each(function() {
                const name = $(this).attr('name').replace(/\[\d+\]/, `[${newIndex}]`);
                $(this).attr('name', name);
            });
        });
    });

    $('#refBancariasForm').submit(function(e) {
        e.preventDefault();
        
        // Actualizar el array con los valores actuales de los inputs
        refBancarias = [];
        $('#refBancariasTable tr').each(function() {
            const inputs = $(this).find('input');
            refBancarias.push({
                banco: $(inputs[0]).val(),
                cuenta_bancaria: $(inputs[1]).val(),
                cuenta_convenio: $(inputs[2]).val(),
                clabe: $(inputs[3]).val(),
                clave_banco: $(inputs[4]).val()
            });
        });
        
        $('#ref_bancarias').val(JSON.stringify(refBancarias));
        this.submit();
    });

    // Prevenir el envío del formulario al presionar Enter en los campos
    $('#banco, #cuenta_bancaria, #cuenta_convenio, #clabe, #clave_banco').keypress(function (e) {
        if (e.which === 13) {
            e.preventDefault();
            agregarRefBancaria();
        }
    });
</script>

</body>
</html>