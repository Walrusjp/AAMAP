<?php
require 'db_connect.php';

if (isset($_POST['register'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $role = $_POST['role'];

    // Hash de la contraseña
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $query = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
    $query->bind_param("sss", $username, $hashedPassword, $role);
    
    if ($query->execute()) {
        $message = "Usuario registrado exitosamente.";
    } else {
        $message = "Error al registrar el usuario.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Registro</title>
    <link rel="stylesheet" type="text/css" href="styles.css">
</head>
<body>
    <div class="login-container">
        <h2>Registro de Usuario</h2>
        <form method="POST" action="">
            <div class="input-group">
                <label>Usuario:</label>
                <input type="text" name="username" required>
            </div>
            <div class="input-group">
                <label>Contrase&ntilde;a:</label>
                <input type="password" name="password" required>
            </div>

	    <div class="input-group">

                <label>Rol:</label>

                <select name="role" required>

                    <option value="admin">Admin</option>

                    <option value="operador">Operador</option>

                    <option value="lector">Lector</option>

                </select>

            </div>

            <button type="submit" name="register">Registrar</button>
            <?php if (isset($message)): ?>
                <p class="message"><?php echo $message; ?></p>
            <?php endif; ?>
            <br><br><a href="login.php">Ir a iniciar sesi&oacute;n</a>
        </form>
    </div>
</body>
</html>
