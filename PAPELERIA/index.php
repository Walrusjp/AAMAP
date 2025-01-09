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
        // Si las credenciales son válidas, almacenar el user_id en la sesión
        $_SESSION['username'] = $user['username'];
        $_SESSION['user_id'] = $user['id']; // Guardar el user_id en la sesión

        // Redirigir al usuario a la página de bienvenida u otra página
        header("Location: papeleria.php");// cambiar a papeleria.php o error404.html
        exit();
    } else {
        $error = "Nombre de usuario o contraseña incorrectos."; // Almacenar el error
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

        <form method="POST" action="/error404.html"><!-- cambiar a /laucn.php o error404.html-->
            <button type="submit" name="logout" class="logout-button push" id="main">Regresar</button>
        </form>
    </div>

    <!-- Mostrar el alert si hay un error -->
    <?php if (!empty($error)): ?>
        <script>
            alert("<?php echo $error; ?>");
        </script>
    <?php endif; ?>

</body>
</html>
