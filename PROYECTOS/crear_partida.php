<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

include 'C:/xampp/htdocs/PAPELERIA/db_connect.php';

// Obtener el cod_fab desde la URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "Proyecto no especificado.";
    exit();
}

$proyecto_id = $_GET['id'];

// Procesar el formulario de registro de la partida
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre_partida = isset($_POST['nombre_partida']) ? $_POST['nombre_partida'] : '';
    $mac = isset($_POST['mac']) ? 1 : 0;
    $man = isset($_POST['man']) ? 1 : 0;
    $com = isset($_POST['com']) ? 1 : 0;

    // Obtener la fecha y hora actuales
    $fecha_log = date('Y-m-d H:i:s');
    $estatus_log = 'sin registro';  // Valor por defecto

    // Insertar nueva partida
    $insertSql = "INSERT INTO partidas (cod_fab, nombre, mac, man, com) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($insertSql);
    $stmt->bind_param("ssiii", $proyecto_id, $nombre_partida, $mac, $man, $com);
    $stmt->execute();

    // Obtener el ID de la nueva partida
    $partida_id = $stmt->insert_id;

    // Insertar registro en la tabla registro_estatus
    $insertEstatusSql = "INSERT INTO registro_estatus (id_partida, estatus_log, fecha_log) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($insertEstatusSql);
    $stmt->bind_param("iss", $partida_id, $estatus_log, $fecha_log);
    $stmt->execute();

    // Redirigir a detalle del proyecto
    header("Location: ver_proyecto.php?id=" . $proyecto_id);
    exit();
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar Nueva Partida</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="stprojects.css">
    <link rel="icon" href="/assets/logo.ico">
</head>
<body>

<div class="container mt-5">
    <h1>Registrar Nueva Partida</h1>
    <form method="POST">
        <div class="form-group">
            <label for="nombre_partida">Nombre de la Partida</label>
            <input type="text" class="form-control" id="nombre_partida" name="nombre_partida" required>
        </div>
        <div class="form-group form-check">
            <input type="checkbox" class="form-check-input" id="com" name="com">
            <label class="form-check-label" for="com">COM</label>
        </div>
        <div class="form-group form-check">
            <input type="checkbox" class="form-check-input" id="man" name="man">
            <label class="form-check-label" for="man">MAN</label>
        </div>
        <div class="form-group form-check">
            <input type="checkbox" class="form-check-input" id="mac" name="mac">
            <label class="form-check-label" for="mac">MAC</label>
        </div>
        <button type="submit" class="btn btn-primary">Registrar Partida</button>
    </form>
</div>

</body>
</html>
