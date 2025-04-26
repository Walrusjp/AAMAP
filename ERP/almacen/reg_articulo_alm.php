<?php
session_start();

//verificar si el usuario inició sesión
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

require 'C:/xampp/htdocs/db_connect.php';
require 'C:/xampp/htdocs/role.php';

?>