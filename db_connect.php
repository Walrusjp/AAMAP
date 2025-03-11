<?php
$servername = "localhost";
$username = "root";
$password = "44m4p_php";
$dbname = "aamap";

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Comprobar conexión
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Establecer el conjunto de caracteres a utf8mb4
$conn->set_charset("utf8mb4");
//echo "Connected successfully";
?>
