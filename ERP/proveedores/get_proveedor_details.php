<?php
session_start();
if (!isset($_SESSION['username'])) {
    die('Acceso no autorizado');
}

require 'C:/xampp/htdocs/db_connect.php';

$id_proveedor = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_proveedor <= 0) {
    die('ID de proveedor inválido');
}

// Obtener datos del proveedor
$sqlProveedor = "SELECT * FROM proveedores WHERE id_pr = ?";
$stmtProveedor = $conn->prepare($sqlProveedor);
$stmtProveedor->bind_param('i', $id_proveedor);
$stmtProveedor->execute();
$proveedor = $stmtProveedor->get_result()->fetch_assoc();

// Obtener referencias bancarias
$sqlRefs = "SELECT * FROM ref_bancarias_prov WHERE id_pr = ?";
$stmtRefs = $conn->prepare($sqlRefs);
$stmtRefs->bind_param('i', $id_proveedor);
$stmtRefs->execute();
$refBancarias = $stmtRefs->get_result()->fetch_all(MYSQLI_ASSOC);

$conn->close();
?>

<head>
    <style>
        dt, dd, .btn10 { font-size: 0.8em; }
        dt, dd, .sopas, .btn10 { line-height: 1.2em; }
        .sopas { font-size: 0.9em; }
    </style>
</head>

<div class="row">
    <div class="col-md-6">
        <h5>Información General</h5>
        <dl class="row">
            <dt class="col-sm-4">Empresa:</dt>
            <dd class="col-sm-8"><?php echo htmlspecialchars($proveedor['empresa']); ?></dd>
            
            <dt class="col-sm-4">Contacto:</dt>
            <dd class="col-sm-8"><?php echo htmlspecialchars($proveedor['contacto']); ?></dd>
            
            <dt class="col-sm-4">RFC:</dt>
            <dd class="col-sm-8"><?php echo htmlspecialchars($proveedor['rfc']); ?></dd>
            
            <dt class="col-sm-4">Dirección:</dt>
            <dd class="col-sm-8"><?php echo htmlspecialchars($proveedor['direccion']); ?></dd>
            
            <dt class="col-sm-4">CP:</dt>
            <dd class="col-sm-8"><?php echo htmlspecialchars($proveedor['cp']); ?></dd>
            
            <dt class="col-sm-4">Municipio:</dt>
            <dd class="col-sm-8"><?php echo htmlspecialchars($proveedor['municipio']); ?></dd>
            
            <dt class="col-sm-4">Estado:</dt>
            <dd class="col-sm-8"><?php echo htmlspecialchars($proveedor['estado']); ?></dd>
            
            <dt class="col-sm-4">País:</dt>
            <dd class="col-sm-8"><?php echo htmlspecialchars($proveedor['pais']); ?></dd>
        </dl>
    </div>
    <div class="col-md-6">
        <h5>Información de Contacto</h5>
        <dl class="row">
            <dt class="col-sm-4">Teléfono:</dt>
            <dd class="col-sm-8"><?php echo htmlspecialchars($proveedor['telefono']); ?></dd>
            
            <dt class="col-sm-4">Correo:</dt>
            <dd class="col-sm-8"><?php echo htmlspecialchars($proveedor['correo']); ?></dd>
            
            <dt class="col-sm-4">ISO:</dt>
            <dd class="col-sm-8">
                <?php if ($proveedor['iso']): ?>
                    <span class="badge badge-success">Sí</span>
                <?php else: ?>
                    <span class="badge badge-secondary">No</span>
                <?php endif; ?>
            </dd>
            
            <dt class="col-sm-4">Registro:</dt>
            <dd class="col-sm-8"><?php echo date('d/m/Y H:i', strtotime($proveedor['created_at'])); ?></dd>
        </dl>
    </div>
</div>

<?php if (!empty($refBancarias)): ?>
<div class="mt-4">
    <h5>Referencias Bancarias</h5>
    <div class="list-group sopas">
        <?php foreach ($refBancarias as $ref): ?>
        <div class="list-group-item ref-bancaria-item">
            <div class="d-flex w-100 justify-content-between">
                <h6 class="mb-1"><?php echo htmlspecialchars($ref['banco']); ?></h6>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <small>Cuenta Bancaria: <?php echo htmlspecialchars($ref['cuenta_bancaria'] ?? 'N/A'); ?></small><br>
                    <small>Cuenta Convenio: <?php echo htmlspecialchars($ref['cuenta_convenio'] ?? 'N/A'); ?></small>
                </div>
                <div class="col-md-6">
                    <small>CLABE: <?php echo htmlspecialchars($ref['clabe'] ?? 'N/A'); ?></small><br>
                    <small>Clave Banco: <?php echo htmlspecialchars($ref['clave_banco'] ?? 'N/A'); ?></small>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<div class="mt-4 text-right">
    <a href="editar_proveedor.php?id=<?php echo $proveedor['id_pr']; ?>" class="btn btn-primary btn10">Editar Proveedor</a>
    <a href="editar_ref_pr.php?id=<?php echo $proveedor['id_pr']; ?>" class="btn btn-info btn10">Editar Ref. Bancarias</a>
    <a href="ver_compras_proveedor.php?id=<?php echo $proveedor['id_pr']; ?>" class="btn btn-info btn10" >Ver Compras</a>
</div>