<?php 
$username = $_SESSION['username'];
$query = $conn->prepare("SELECT role FROM users WHERE username = ?");
$query->bind_param("s", $username);
$query->execute();
$result = $query->get_result();
$user = $result->fetch_assoc();

$role = $user['role'];	
?>