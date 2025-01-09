<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

// Verificar si se solicitó el cierre de sesión
if (isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header("Location: index.php");
    exit();
}

require 'db_connect.php';
require 'role.php';

?>

<!DOCTYPE html>
<html>
<head>
    <title>Bienvenido</title>
    <link rel="stylesheet" type="text/css" href="styles.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background-color: #f0f8ff; /* Fondo claro */
            text-align: center;
        }
        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            background-color: transparent;
            border-bottom: 1px solid #ddd;
            padding: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
        }
        .navbar h1 {
            margin: 0;
            font-size: 40px;
            font-family: 'Arial Black', sans-serif;
        }
        .logout-button {
            font-size: 16px;
            font-family: 'Arial', sans-serif;
            color: #007bff;
            background: none;
            border: none;
            cursor: pointer;
            padding: 10px;
            text-decoration: none;
            transition: color 0.3s;
	    font-weight: bolder;
	    margin-right: 150px;
        }
        .logout-button:hover {
            color: red;
	    font-weight: bolder;
	    background: none;
        }
        .button-container {
            display: flex;
            justify-content: center;
            align-items: center;
            height: calc(100vh - 60px); /* Ajusta para dejar espacio para el navbar */
        }
        .button {
            display: inline-block;
            padding: 20px 40px;
            margin: 20px;
            font-size: 24px;
            font-family: 'Impact', sans-serif;
            color: #ffffff;
            background-color: #007bff; /* Azul fuerte */
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s, transform 0.3s;
        }
        .button:hover {
            background-color: #0056b3; /* Azul más oscuro */
            transform: scale(1.05);
        }
    </style>
</head>
<body>
    <div class="navbar">
        <h1>Bienvenido, <?php echo htmlspecialchars($_SESSION['username']); ?></h1>
        <form method="POST" action="">
            <button type="submit" name="logout" class="logout-button">Cerrar sesi&oacute;n</button>
            <a href="accesos.php">ki</a>
        </form>
    </div>
</body>
</html>
