<?php
session_start();
if (!isset($_SESSION['username'])) {
    die('Acceso no autorizado');
}

require 'C:/xampp/htdocs/db_connect.php';

$id_cliente = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_cliente <= 0) {
    die('ID de cliente inválido');
}

// Obtener datos del cliente
$sqlCliente = "SELECT * FROM clientes_p WHERE id = ?";
$stmtCliente = $conn->prepare($sqlCliente);
$stmtCliente->bind_param('i', $id_cliente);
$stmtCliente->execute();
$cliente = $stmtCliente->get_result()->fetch_assoc();

// Obtener compradores asociados
$sqlCompradores = "SELECT * FROM compradores WHERE id_cliente = ?";
$stmtCompradores = $conn->prepare($sqlCompradores);
$stmtCompradores->bind_param('i', $id_cliente);
$stmtCompradores->execute();
$compradores = $stmtCompradores->get_result()->fetch_all(MYSQLI_ASSOC);

$conn->close();
?>

<head>
    <style>
        .info-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }
        .info-item {
            background-color: #f8f9fa;
            padding: 12px;
            border-radius: 5px;
        }
        .info-label {
            font-weight: bold;
            font-size: 0.8rem;
            color: #6c757d;
            margin-bottom: 5px;
        }
        .info-value {
            font-size: 0.9rem;
        }
        .comprador-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
        }
        .comprador-card {
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 12px;
            margin-bottom: 15px;
        }
        .comprador-name {
            font-weight: bold;
            margin-bottom: 8px;
            font-size: 0.95rem;
        }
        .comprador-detail {
            font-size: 0.8rem;
            margin-bottom: 3px;
        }
        .action-buttons {
            margin-top: 20px;
            text-align: right;
        }
        .section-title {
            margin-bottom: 15px;
            padding-bottom: 5px;
            border-bottom: 1px solid #eee;
        }
    </style>
</head>

<div class="container-fluid">
    <!-- Información del cliente -->
    <h5 class="section-title">Información Comercial</h5>
    <div class="info-grid">
        <div class="info-item">
            <div class="info-label">Nombre Comercial</div>
            <div class="info-value"><?php echo htmlspecialchars($cliente['nombre_comercial']); ?></div>
        </div>
        <div class="info-item">
            <div class="info-label">Razón Social</div>
            <div class="info-value"><?php echo htmlspecialchars($cliente['razon_social']); ?></div>
        </div>
        <div class="info-item">
            <div class="info-label">RFC</div>
            <div class="info-value"><?php echo htmlspecialchars($cliente['rfc']); ?></div>
        </div>
        <div class="info-item" style="grid-column: span 3;">
            <div class="info-label">Dirección</div>
            <div class="info-value"><?php echo htmlspecialchars($cliente['direccion']); ?></div>
        </div>
        <!-- Puedes agregar más campos aquí si es necesario -->
    </div>

    <!-- Compradores -->
    <?php if (!empty($compradores)): ?>
    <h5 class="section-title">Compradores</h5>
    <div class="comprador-grid">
        <?php foreach ($compradores as $comprador): ?>
        <div class="comprador-card">
            <div class="comprador-name"><?php echo htmlspecialchars($comprador['nombre']); ?></div>
            <div class="comprador-detail">
                <strong>Teléfono:</strong> <?php echo htmlspecialchars($comprador['telefono'] ?? 'N/A'); ?>
            </div>
            <div class="comprador-detail">
                <strong>Correo:</strong> <?php echo htmlspecialchars($comprador['correo'] ?? 'N/A'); ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Botones de acción -->
    <div class="action-buttons">
        <a href="edit_client.php?id=<?php echo $cliente['id']; ?>" class="btn btn-info btn-sm">Editar Cliente</a>
        <a href="reg_comprador.php?id=<?php echo $comprador['id_cliente']; ?>" class="btn btn-info btn-sm">Registrar Comprador</a>
        <!--<a href="ver_proyectos_cliente.php?id=<?php echo $cliente['id']; ?>" class="btn btn-info btn-sm">Ver Proyectos</a>-->
    </div>
</div>