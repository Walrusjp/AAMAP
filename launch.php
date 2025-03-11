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
                    <p>Inicio de sesi&oacute;n exitoso.</p>
                </div>
                <button class="close-btn btn-secondary" onclick="closeModal()">Cerrar</button>
            </div>
        </div>
    <?php endif; ?>

    <!-- Navbar con logo -->
    <div class="navbar">
        <img src="/assets/grupo_aamap.png" alt="Logo AAMAP">
        <form method="POST" action="">
            <button type="submit" name="logout" class="btn btn-secondary chompa" id="logout">Cerrar sesi&oacute;n</button>
        </form>
        <div style="position: absolute; top: 70px; left: 430px;"><h4>Usuario: <b><?php echo htmlspecialchars($_SESSION['username']); ?></b></h4></div>
    </div>

    <!-- Contenedor centrado -->
    <div class="launcher-container">
        <h1>ESCOGE UNA OPCION</h1>
        <button onclick="window.location.href='/PAPELERIA/papeleria.php'">PAPELERIA</button>
        <button onclick="window.location.href='/error404.html'">VIATICOS</button><!--/PREDIEM/index.php-->


        <?php if($username === 'admin' || $username === 'h.galicia'): ?>
            <button onclick="window.location.href='/PROYECTOS/all_projects.php'">CRM PROYECTOS</button>
        <?php else: ?>
            <button onclick="window.location.href='/PROYECTOS/all_projects.php'" disabled>CRM PROYECTOS</button>
        <?php endif; ?>


        <?php if($role === 'admin' || $username === 'CIS'): ?>
            <button onclick="window.location.href='/ERP/all_projects.php'">ERP PROYECTOS</button>
        <?php else: ?>
            <button onclick="window.location.href='/ERP/all_projects.php'" disabled>ERP PROYECTOS</button>
        <?php endif; ?>
    </div>

    <script type="text/javascript">
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