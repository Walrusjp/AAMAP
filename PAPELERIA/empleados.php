<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Verificar si se solicitó el cierre de sesión
if (isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}

include 'db_connect.php';
//include 'role.php';

// Manejar eliminación (marcar como inactivo)
if (isset($_POST['delete'])) {
    $id = $_POST['id'];
    $query = "UPDATE empleados SET activo = 0 WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
}

// Buscar empleados
$search = "";
if (isset($_GET['search'])) {
    $search = $_GET['search'];
}

$query = "SELECT id, nombre, apellidos, area, puesto, correo, telefono, foto, tipo FROM empleados WHERE activo = 1 AND (nombre LIKE ? OR apellidos LIKE ? OR area LIKE ? OR puesto LIKE ? OR correo LIKE ? OR telefono LIKE ? OR tipo LIKE ?)";
$search_param = "%" . $search . "%";
$stmt = $conn->prepare($query);
$stmt->bind_param("sssssss", $search_param, $search_param, $search_param, $search_param, $search_param, $search_param, $search_param);
$stmt->execute();
$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html>
<head>
    <title>Empleados</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f8ff; /* Fondo claro */
            margin: 0;
            padding: 20px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        h1 {
            color: #0056b3;
            margin: 0;
        }
        .button {
            padding: 5px 10px;
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
        .search-box {
            margin-left: auto;
        }
        .search-box input {
            padding: 10px;
            border: 1px solid #0056b3;
            border-radius: 4px;
            font-size: 16px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table, th, td {
            border: 1px solid black;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #0056b3;
            color: white;
        }
	#crear {
	    margin-left: 60px;
	}
	#return {
	    margin-left: 400px;
	    background: #f95951;
	}
	#return:hover {
	    background: #f93a2b;
	}
            .modal {
            display: block; /* Cambia a "none" para ocultar */
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5); /* Fondo semitransparente */
            justify-content: center;
            align-items: center;
            z-index: 2000; /* Superior a la barra de navegación */
        }

        /*Ventana emergente*/
        .modal-content {
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            text-align: center;
            max-width: 600px;
            margin: auto;
        }

        .close-btn {
            background-color: red;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 20px;
            font-size: 18px;
            transition: background-color 0.3s;
        }

        .close-btn:hover {
            background-color: darkred;
        }

    </style>
</head>
    <script>
        function closeModal() {
            document.getElementById("welcomeModal").style.display = "none";
        }
    </script>

<body>
    <div id="welcomeModal" class="modal">
        <div class="modal-content">
            <h2>Bienvenido, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
            <p>Gracias por iniciar sesión en nuestro sistema.</p>
            <button class="close-btn" onclick="closeModal()">Cerrar</button>
        </div>
    </div>

    <div class="header">
        <h1>Lista de Empleados</h1>
        <?php //if($role === 'admin' || $role === 'operador'): ?>
        <a href="crear_empleado.php" class="button" id="crear">A&ntilde;adir Empleado</a>
        <?php //endif; ?>
	<a href="welcome.php" class="button" id="return">Regresar</a>
        <div class="search-box">
            <form method="GET" action="">
                <input type="text" name="search" placeholder="Buscar..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="button">Buscar</button>
            </form>
        </div>
    </div>
    <table>
        <thead>
            <tr>
                <th>Foto</th>
                <th>ID</th>
                <th>Nombre</th>
                <th>Apellidos</th>
                <th>&Aacute;rea</th>
                <th>Puesto</th>
                <th>Correo</th>
                <th>Teléfono</th>
                <th>Tipo</th>
                <?php //if($role === 'admin' || $role === 'operador'): ?>
                <th>Acciones</th>
                <?php //endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php while($row = $result->fetch_assoc()): ?>
                <tr>
		    <td><img src="mostrar_imagen.php?id=<?php echo $row['id']; ?>" alt="Foto" width="50"></td>
                    <td><?php echo htmlspecialchars($row['id']); ?></td>
                    <td><?php echo htmlspecialchars($row['nombre']); ?></td>
                    <td><?php echo htmlspecialchars($row['apellidos']); ?></td>
                    <td><?php echo htmlspecialchars($row['area']); ?></td>
                    <td><?php echo htmlspecialchars($row['puesto']); ?></td>
                    <td><?php echo htmlspecialchars($row['correo']); ?></td>
                    <td><?php echo htmlspecialchars($row['telefono']); ?></td>
                    <td><?php echo htmlspecialchars($row['tipo']); ?></td>
                    <?php //if($role === 'admin' || $role === 'operador'): ?>
                    <td>
                        <a href="editar_empleado.php?id=<?php echo $row['id']; ?>" class="button">Editar</a><br><br>
                        <form method="POST" action="" style="display:inline;">
                            <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                            <button type="submit" name="delete" class="button" onclick="return confirm('¿Estás seguro de que deseas eliminar este empleado?')">Eliminar</button>
                        </form>
                    </td>
                    <?php //endif;?>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</body>
</html>
