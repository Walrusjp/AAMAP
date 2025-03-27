<?php
session_start();
include 'db_connect.php';

$error = ""; // Variable para almacenar errores

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Consulta para verificar el nombre de usuario y contraseña
    $query = "SELECT id, username, password FROM users WHERE username = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        // Obtener la IP del cliente
        $ip_address = $_SERVER['REMOTE_ADDR'];

        // Registrar el acceso en la tabla login_logs
        $log_query = "INSERT INTO login_logs (user_id, username, ip_address) VALUES (?, ?, ?)";
        $log_stmt = $conn->prepare($log_query);
        $log_stmt->bind_param("iss", $user['id'], $user['username'], $ip_address);
        $log_stmt->execute();
        $log_stmt->close();

        // Guardar datos en la sesión
        $_SESSION['username'] = $user['username'];
        $_SESSION['user_id'] = $user['id'];

        // Redirigir al usuario
        header("Location: launch.php");
        exit();
    } else {
        $error = "Nombre de usuario o contraseña incorrectos.";
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Inicio de Sesi&oacute;n</title>
    <link rel="stylesheet" type="text/css" href="styles.css">
    <link rel="icon" href="/assets/logo.png" type="image/png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
    <div class="login-container">
        <h2>Inicio de Sesi&oacute;n</h2>
        <form method="POST" action="">
            <div class="input-group">
                <label>Usuario:</label>
                <input type="text" name="username" required>
            </div>
            <div class="input-group">
                <label>Contrase&ntilde;a:</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" name="login">Iniciar Sesi&oacute;n</button>
        </form>

    <!-- Mostrar el alert si hay un error -->
    <?php if (!empty($error)): ?>
        <script>
            alert("<?php echo $error; ?>");
        </script>
    <?php endif; ?>

</body>
</html>