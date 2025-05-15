<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: /login.php");
    exit();
}

require 'C:/xampp/htdocs/db_connect.php';
require 'C:/xampp/htdocs/role.php';

date_default_timezone_set("America/Mexico_City");
$fechaH = date("Y-m-d H:i:s");

// Verificar si se envió el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $empresa = $_POST['empresa'];
    $rfc = $_POST['rfc'];
    $contacto = $_POST['contacto'];
    $direccion = $_POST['direccion'];
    $cp = $_POST['cp'];
    $municipio = $_POST['municipio'];
    $estado = $_POST['estado'];
    $pais = $_POST['pais'];
    $telefono = $_POST['telefono'];
    $correo = $_POST['correo'];
    $iso = $_POST['iso'] ?? 0; // Valor por defecto 0 si no se marca
    $ref_bancarias = json_decode($_POST['ref_bancarias'], true);

    $conn->begin_transaction();
    try {
        // Insertar proveedor
        $sqlProveedor = "INSERT INTO proveedores (empresa, rfc, contacto, direccion, cp, municipio, estado, pais, telefono, correo, iso, activo, created_at)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?)";
        $stmtProveedor = $conn->prepare($sqlProveedor);
        $stmtProveedor->bind_param('ssssssssssis', 
            $empresa, $rfc, $contacto, $direccion, $cp, $municipio, $estado, $pais, $telefono, $correo, $iso, $fechaH);
        $stmtProveedor->execute();
        $id_pr = $stmtProveedor->insert_id;

        // Insertar referencias bancarias si existen
        if (!empty($ref_bancarias)) {
            $sqlRefBancaria = "INSERT INTO ref_bancarias_prov (id_pr, banco, cuenta_bancaria, cuenta_convenio, clabe, clave_banco)
                               VALUES (?, ?, ?, ?, ?, ?)";
            $stmtRefBancaria = $conn->prepare($sqlRefBancaria);
            
            foreach ($ref_bancarias as $ref) {
                $stmtRefBancaria->bind_param(
                    'isssss', 
                    $id_pr, 
                    $ref['banco'], 
                    $ref['cuenta_bancaria'], 
                    $ref['cuenta_convenio'], 
                    $ref['clabe'], 
                    $ref['clave_banco']
                );
                $stmtRefBancaria->execute();
            }
        }

        $conn->commit();
        echo "<script>alert('Proveedor registrado exitosamente.'); window.location.href = 'ver_proveedores.php';</script>";
    } catch (Exception $e) {
        $conn->rollback();
        echo "<script>alert('Error al registrar: " . $e->getMessage() . "');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar Nuevo Proveedor</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="icon" href="/assets/logo.png" type="image/png">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body style="background-color: rgba(211, 211, 211, 0.4) !important;">

<div class="container">
    <h1 class="text-center">Registrar Nuevo Proveedor</h1>
    <a href="ver_proveedores.php" class="btn btn-secondary">Regresar</a>
    <p>&nbsp;&nbsp;&nbsp;&nbsp;</p>
    <form id="proveedorForm" method="POST" action="registrar_proveedor.php">
        <div class="form-group">
            <label for="empresa">Empresa</label>
            <input type="text" class="form-control" id="empresa" name="empresa" required>
        </div>
        <div class="form-group">
            <label for="rfc">RFC</label>
            <input type="text" class="form-control" id="rfc" name="rfc" required>
        </div>
        <div class="form-group">
            <label for="contacto">Contacto</label>
            <input type="text" class="form-control" id="contacto" name="contacto" required>
        </div>
        <div class="form-group">
            <label for="direccion">Dirección</label>
            <input type="text" class="form-control" id="direccion" name="direccion" required>
        </div>
        <div class="form-group">
            <label for="cp">Código Postal</label>
            <input type="text" class="form-control" id="cp" name="cp" >
        </div>
        <div class="form-group">
            <label for="municipio">Municipio</label>
            <input type="text" class="form-control" id="municipio" name="municipio" value="Heroica Puebla de Zaragoza" required>
        </div>
        <div class="form-group">
            <label for="estado">Estado</label>
            <input type="text" class="form-control" id="estado" name="estado" value="Puebla" required>
        </div>
        <div class="form-group">
            <label for="pais">País</label>
            <input type="text" class="form-control" id="pais" name="pais" value="México" required>
        </div>
        <div class="form-group">
            <label for="telefono">Teléfono</label>
            <input type="text" class="form-control" id="telefono" name="telefono" >
        </div>
        <div class="form-group">
            <label for="correo">Correo Electrónico</label>
            <input type="email" class="form-control" id="correo" name="correo" >
        </div>
        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" id="iso" name="iso" value="1">
            <label class="form-check-label" for="iso">
                Certificado ISO
            </label>
        </div>

        <h3>Referencias Bancarias</h3>
        <div class="form-row">
            <div class="col">
                <input type="text" class="form-control" id="banco" placeholder="Banco" >
            </div>
            <div class="col">
                <input type="text" class="form-control" id="cuenta_bancaria" placeholder="Cuenta Bancaria">
            </div>
            <div class="col">
                <input type="text" class="form-control" id="cuenta_convenio" placeholder="Num Convenio">
            </div>
            <div class="col">
                <input type="text" class="form-control" id="clabe" placeholder="CLABE interbancaria">
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
            <tbody id="refBancariasTable"></tbody>
        </table>

        <input type="hidden" name="ref_bancarias" id="ref_bancarias">
        <button type="submit" class="btn btn-success btn-block">Registrar Proveedor</button>
    </form>
</div>

<script>
    const refBancarias = [];

    function agregarRefBancaria() {
        const banco = $('#banco').val();
        const cuenta_bancaria = $('#cuenta_bancaria').val();
        const cuenta_convenio = $('#cuenta_convenio').val();
        const clabe = $('#clabe').val();
        const clave_banco = $('#clave_banco').val();

        if (banco) {
            refBancarias.push({ 
                banco, 
                cuenta_bancaria, 
                cuenta_convenio, 
                clabe, 
                clave_banco 
            });
            
            $('#refBancariasTable').append(`
                <tr>
                    <td>${banco}</td>
                    <td>${cuenta_bancaria || '-'}</td>
                    <td>${cuenta_convenio || '-'}</td>
                    <td>${clabe || '-'}</td>
                    <td>${clave_banco || '-'}</td>
                    <td><button class="btn btn-danger btn-sm removeRefBancaria">Eliminar</button></td>
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
        const index = $(this).closest('tr').index();
        refBancarias.splice(index, 1);
        $(this).closest('tr').remove();
    });

    $('#proveedorForm').submit(function () {
        $('#ref_bancarias').val(JSON.stringify(refBancarias));
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