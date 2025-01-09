<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

include 'db_connect.php';
include 'role.php';

$id = $_GET['id'];

// Obtener datos del empleado
$query = "SELECT * FROM empleados WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$empleado = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = $_POST['nombre'];
    $apellidos = $_POST['apellidos'];
    $area = $_POST['area'];
    $puesto = $_POST['puesto'];
    $correo = $_POST['correo'];
    $telefono = $_POST['telefono'];
    $tipo = $_POST['tipo'];

    // Manejo de la foto
    if (!empty($_FILES['foto']['name'])) {
        $foto = $_FILES['foto']['name'];
        $target_dir = "uploads/";
        $target_file = $target_dir . basename($foto);
        move_uploaded_file($_FILES['foto']['tmp_name'], $target_file);
    } else {
        $foto = $empleado['foto'];
    }

    $query = "UPDATE empleados SET nombre = ?, apellidos = ?, area = ?, puesto = ?, correo = ?, telefono = ?, foto = ?, tipo = ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssssssssi", $nombre, $apellidos, $area, $puesto, $correo, $telefono, $foto, $tipo, $id);
    $stmt->execute();

    header("Location: empleados.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Editar Empleado</title>
    <link rel="stylesheet" type="text/css" href="styles.css">
    <style>
        .form-container {
            max-width: 500px;
            margin: auto;
            background: rgba(255, 255, 255, 0.9);
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
        }
        .form-container h2 {
            text-align: center;
            color: #0056b3;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            color: #0056b3;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #0056b3;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .form-group input[type="file"] {
            padding: 3px;
        }
        .button {
            width: 100%;
            padding: 10px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 16px;
        }
        .button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Editar Empleado</h2>
        <form method="POST" action="" enctype="multipart/form-data">
            <div class="form-group">
                <label for="nombre">Nombre:</label>
                <input type="text" name="nombre" value="<?php echo htmlspecialchars($empleado['nombre']); ?>" required>
            </div>
            <div class="form-group">
                <label for="apellidos">Apellidos:</label>
                <input type="text" name="apellidos" value="<?php echo htmlspecialchars($empleado['apellidos']); ?>" required>
            </div>
            <div class="form-group">
                <label for="area">Área:</label>
                <input type="text" name="area" value="<?php echo htmlspecialchars($empleado['area']); ?>" required>
            </div>
            <div class="form-group">
                <label for="puesto">Puesto:</label>
                <input type="text" name="puesto" value="<?php echo htmlspecialchars($empleado['puesto']); ?>" required>
            </div>
            <div class="form-group">
                <label for="correo">Correo:</label>
                <input type="email" name="correo" value="<?php echo htmlspecialchars($empleado['correo']); ?>" required>
            </div>
            <div class="form-group">
                <label for="telefono">Teléfono:</label>
                <input type="text" name="telefono" value="<?php echo htmlspecialchars($empleado['telefono']); ?>" required>
            </div>
            <div class="form-group">
                <label for="foto">Foto:</label>
                <input type="file" name="foto">
                <?php if (!empty($empleado['foto'])): ?>
                    <img src="uploads/<?php echo htmlspecialchars($empleado['foto']); ?>" alt="Foto" width="50">
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label for="tipo">Tipo:</label>
                <input type="text" name="tipo" value="<?php echo htmlspecialchars($empleado['tipo']); ?>" required>
            </div>
            <button type="submit" class="button">Guardar Cambios</button>
        </form>
    </div>
</body>
</html>
