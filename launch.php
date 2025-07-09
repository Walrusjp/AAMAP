<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

require 'db_connect.php';
require 'role.php';

// Verificar si se solicitó el cierre de sesión
if (isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}

// Verificar si la ventana emergente ya se mostró
if (!isset($_SESSION['welcome_shown'])) {
    $_SESSION['welcome_shown'] = true;
    $showModal = true; 
} else {
    $showModal = false;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="icon" href="/assets/logo.png" type="image/png">
    <link rel="stylesheet" type="text/css" href="st_launch.css">
    <title>Launcher</title>
</head>
<body>
    <?php if ($showModal): ?>
        <div id="welcomeModal" class="modal show">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Hola, <?php echo htmlspecialchars($user['nombre']); ?> <?php echo htmlspecialchars($user['apellido']); ?>!</h2>
                </div>
                <div class="modal-body">
                    <p>Inicio de sesión exitoso.</p>
                </div>
                <button class="close-btn btn-secondary" id="logout" onclick="closeModal()">Cerrar</button>
            </div>
        </div>
    <?php endif; ?>

    <!-- Navbar con logo -->
    <div class="navbar" style="display: flex; align-items: center; justify-content: space-between;">
        <img src="/assets/grupo_aamap.webp" alt="Logo AAMAP" id="aamap">
        <div style="display: flex; align-items: center;">
            <img src="/assets/user.ico" alt="usuario" style="width: 30px; height: auto; margin-right: 10px;">
            <h4 style="margin: 0;" class="mr-3"><b>: <?php echo htmlspecialchars($_SESSION['username']); ?></b></h4>
            <button type="button" class="btn btn-outline-secondary mr-3" id="logout" onclick="openLogoutModal()">
                <img src="/assets/logout.ico" title="Cerrar Sesión" style="width: 35px; height: auto;">
            </button>
        </div>
    </div>

    <!-- Modal de confirmación para cerrar sesión -->
    <div id="logoutModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Cerrar sesión</h2>
            </div>
            <div class="modal-body">
                <p>¿Estás seguro de que deseas cerrar sesión?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeLogoutModal()">Cancelar</button>
                <form method="POST" action="">
                    <button type="submit" name="logout" class="btn btn-danger">Cerrar sesión</button>
                </form>
            </div>
        </div>
    </div>

    <div class="launcher-grid">
        <?php if($role == 'operador' || $role == 'admin'): ?>
        <div class="card" onclick="window.location.href='/PAPELERIA/papeleria.php'">
            <img class="icon" src="/assets/icons/papeleria.svg" alt="Papelería">
            <span>Papelería</span>
        </div>
        <?php else: ?>
        <div class="card disabled" onclick="window.location.href='/PAPELERIA/papeleria.php'">
            <img class="icon" src="/assets/icons/papeleria.svg" alt="Papelería">
            <span>Papelería</span>
        </div>
        <?php endif; ?>

        <div class="card disabled" onclick="window.location.href='/error404.html'">
            <img class="icon" src="/assets/icons/viaticos.svg" alt="Viáticos">
            <span>Viáticos</span>
        </div>

        <?php if($username === 'admin' || $username === 'h.galicia' || $username === 'cuentasxpxc'): ?>
        <div class="card" onclick="window.location.href='/PROYECTOS/all_projects.php'">
            <img class="icon" src="/assets/icons/crm.svg" alt="CRM">
            <span>CRM</span>
        </div>
        <?php elseif($username === 'CIS' || $username === 'atencionaclientes'): ?>
        <div class="card" onclick="window.location.href='/PROYECTOS/direct_projects.php'">
            <img class="icon" src="/assets/icons/crm.svg" alt="CRM">
            <span>CRM</span>
        </div>
        <?php else: ?>
        <div class="card disabled">
            <img class="icon" src="/assets/icons/crm.svg" alt="CRM">
            <span>CRM</span>
        </div>
        <?php endif; ?>
        <?php if($username === 'admin'): ?>
        <div class="card" onclick="window.location.href='/PROYECTOS/direct_projects.php'">
            <img class="icon" src="/assets/icons/crm.svg" alt="CRM">
            <span>CRM directas</span>
        </div>
        <?php endif; ?>

        <?php if($role === 'admin' || $username === 'CIS' || $username === 'atencionaclientes' || $username === 'calidad'): ?>
        <div class="card" onclick="window.location.href='/ERP/all_projects.php'">
            <img class="icon" src="/assets/icons/erp.svg" alt="ERP">
            <span>ERP</span>
        </div>
        <?php else: ?>
        <div class="card disabled">
            <img class="icon" src="/assets/icons/erp.svg" alt="ERP">
            <span>ERP</span>
        </div>
        <?php endif; ?>

        <?php if($username === 'CIS' || $username === 'admin'): ?>
        <div class="card" onclick="window.location.href='/ERP/req_interna/devolucion_prestamo.php'">
            <img class="icon" src="/assets/icons/req_interna.svg" alt="req_interna">
            <span>Requicisión Interna</span>
        </div>
        <?php elseif($role == 'operador' || $username == 'la.lopez' || $username == 'h.galicia'): ?>
        <div class="card" onclick="window.location.href='/ERP/req_interna/prestamo_almacen.php'">
            <img class="icon" src="/assets/icons/req_interna.svg" alt="req_interna">
            <span>Requicisión Interna</span>
        </div>
        <?php else: ?>
        <div class="card disabled" onclick="window.location.href='/ERP/req_interna/devolucion_prestamo.php'">
            <img class="icon" src="/assets/icons/req_interna.svg" alt="req_interna">
            <span>Requicisión Interna</span>
        </div>
        <?php endif; ?>

        <?php if($username === 'CIS' || $role == 'admin'): ?>
        <div class="card" onclick="window.location.href='/ERP/almacen/ver_almacen.php'">
            <img class="icon" src="/assets/icons/almacen.svg" alt="almacen">
            <span>Almacén</span>
        </div>
        <?php else: ?>
        <div class="card disabled" onclick="window.location.href='/ERP/req_interna/devolucion_prestamo.php'">
            <img class="icon" src="/assets/icons/almacen.svg" alt="almacen">
            <span>Almacén</span>
        </div>
        <?php endif; ?>

        <div class="card disabled" onclick="window.location.href='/ERP/req_interna/devolucion_prestamo.php'">
            <img class="icon" src="/assets/icons/orden_compra.svg" alt="oc">
            <span>Ordenes de Compra</span>
        </div>

        <div class="card disabled" onclick="window.location.href='/ERP/req_interna/devolucion_prestamo.php'">
            <img class="icon" src="/assets/icons/catalogo.svg" alt="catalogo">
            <span>Catálogo</span>
        </div>

        <?php if($username === 'h.galicia' || $username === 'admin'): ?>
        <div class="card" onclick="window.open('/reportes/reporte_gral.php', '_blank')">
            <img class="icon" src="/assets/icons/reportes.svg" alt="Reportes">
            <span>Reportes</span>
        </div>
        <?php else: ?>
        <div class="card disabled" onclick="window.open('/reportes/reporte_gral.php', '_blank')">
            <img class="icon" src="/assets/icons/reportes.svg" alt="Reportes">
            <span>Reportes</span>
        </div>
        <?php endif; ?>

        <?php if($username === 'admin'): ?>
        <div class="card" onclick="window.location.href='/externos/ver_almacen.php'">
            <img class="icon" src="/assets/icons/rotecna.svg" alt="ROTECNA">
            <span>ROTECNA</span>
        </div>
        <?php else: ?>
        <div class="card disabled" onclick="window.location.href='/externos/ver_almacen.php'">
            <img class="icon" src="/assets/icons/rotecna.svg" alt="ROTECNA">
            <span>ROTECNA</span>
        </div>
        <?php endif; ?>
    </div>


    <script type="text/javascript">
        // Función para abrir el modal de cierre de sesión
        function openLogoutModal() {
            const modal = document.getElementById('logoutModal');
            modal.classList.add('show');
        }

        // Función para cerrar el modal de cierre de sesión
        function closeLogoutModal() {
            const modal = document.getElementById('logoutModal');
            modal.classList.remove('show');
        }

        // Función para cerrar el modal de bienvenida
        function closeModal() {
            const modal = document.getElementById('welcomeModal');
            modal.classList.remove('show'); 
            setTimeout(() => {
                modal.style.display = 'none';
            }, 500);
        }
    </script>
</body>
</html>