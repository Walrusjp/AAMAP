<?php
$servername = "localhost";
$username = "root";
$password = "44m4p_php";
$dbname = "aamap";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
//echo "Connected successfully";
?>
