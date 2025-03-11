<?php
require 'db_connect.php';

if (isset($_POST['select_user'])) {
    $userId = $_POST['user_id'];

    // Obtener los datos del usuario seleccionado
    $query = $conn->prepare("SELECT id, username, password, role FROM users WHERE id = ?");
    $query->bind_param("i", $userId);
    $query->execute();
    $result = $query->get_result();
    $user = $result->fetch_assoc();

    // Verificar si se encontró el usuario
    if (!$user) {
        $message = "Usuario no encontrado.";
    }
}

if (isset($_POST['edit'])) {
    $userId = $_POST['user_id']; // Asegurarse de que el id del usuario esté incluido al editar
    $username = $_POST['username'];
    $password = $_POST['password'];
    $role = $_POST['role'];

    // Depurar: Verificar los datos recibidos
    echo "ID del Usuario: $userId<br>";
    echo "Nombre de Usuario: $username<br>";
    echo "Contraseña: $password<br>";
    echo "Rol: $role<br>";

    // Si se cambia la contraseña, la ciframos
    if (!empty($password)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $query = $conn->prepare("UPDATE users SET username = ?, password = ?, role = ? WHERE id = ?");
        $query->bind_param("sssi", $username, $hashedPassword, $role, $userId);
    } else {
        // Si no se cambia la contraseña, solo actualizamos el nombre de usuario y el rol
        $query = $conn->prepare("UPDATE users SET username = ?, role = ? WHERE id = ?");
        $query->bind_param("ssi", $username, $role, $userId);
    }

    // Ejecutar la consulta y verificar el resultado
    if ($query->execute()) {
        $message = "Usuario actualizado exitosamente.";
    } else {
        $message = "Error al actualizar el usuario: " . $query->error;
    }
}

// Obtener todos los usuarios para mostrarlos en el select
$query = $conn->prepare("SELECT id, username FROM users");
$query->execute();
$usersResult = $query->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Editar Usuario</title>
    <link rel="stylesheet" type="text/css" href="styles.css">
</head>
<body>
    <div class="login-container">
        <h2>Editar Usuario</h2>

        <?php if (isset($message)): ?>
            <p class="message"><?php echo $message; ?></p>
        <?php endif; ?>

        <!-- Formulario para seleccionar el usuario -->
        <form method="POST" action="">
            <div class="input-group">
                <label>Seleccionar Usuario:</label>
                <select name="user_id" required>
                    <option value="">Seleccione un usuario</option>
                    <?php while ($row = $usersResult->fetch_assoc()): ?>
                        <option value="<?php echo $row['id']; ?>" <?php echo isset($userId) && $userId == $row['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($row['username']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <button type="submit" name="select_user">Seleccionar</button>
        </form>

        <?php if (isset($user)): ?>
            <!-- Formulario para editar el usuario seleccionado -->
            <form method="POST" action="">
                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                <div class="input-group">
                    <label>Usuario:</label>
                    <input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                </div>
                <div class="input-group">
                    <label>Contraseña:</label>
                    <input type="password" name="password">
                    <small>Dejar en blanco para mantener la contraseña actual.</small>
                </div>

                <div class="input-group">
                    <label>Rol:</label>
                    <select name="role" required>
                        <option value="admin" <?php echo ($user['role'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
                        <option value="operador" <?php echo ($user['role'] == 'operador') ? 'selected' : ''; ?>>Operador</option>
                        <option value="lector" <?php echo ($user['role'] == 'lector') ? 'selected' : ''; ?>>Lector</option>
                    </select>
                </div>

                <button type="submit" name="edit">Actualizar</button>
            </form>
        <?php endif; ?>
        
        <br><br><a href="index.php">Ir a iniciar sesi&oacute;n</a>
    </div>
</body>
</html>
