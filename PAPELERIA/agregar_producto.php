<?php
	session_start();
	if (!isset($_SESSION['username'])) {
	    header("Location: login.php");
	    exit();
	}

	require 'C:\xampp\htdocs\db_connect.php';
	require 'C:\xampp\htdocs\role.php';

	// Verificar si se solicitó el cierre de sesión
	if (isset($_POST['logout'])) {
	    session_unset();
	    session_destroy();
	    header("Location: index.php");
	    exit();
	}

	// Procesar el formulario para agregar el enlace
	if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_link'])) {
    // Obtener el enlace desde el formulario
    $link = $_POST['link'];

    // Validar que el enlace no esté vacío
    if (empty($link)) {
        $error = "El enlace es obligatorio.";
    } else {
        // Obtener el user_id de la sesión
        $user_id = $_SESSION['user_id']; // Asegúrate de que el user_id esté almacenado en la sesión

        // Validar que user_id esté disponible
        if (empty($user_id)) {
            $error = "El ID de usuario no está definido. Por favor, inicia sesión nuevamente.";
        } else {
            // Preparar la consulta SQL para insertar el nuevo link con el user_id
            $query = "INSERT INTO share_link (user_id, link) VALUES (?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("is", $user_id, $link); // "i" para enteros, "s" para strings

            // Ejecutar la consulta
            if ($stmt->execute()) {
                // Mostrar mensaje de éxito usando JavaScript
                $success = "Enlace agregado correctamente.";
            } else {
                // Si ocurre un error
                $error = "Hubo un error al agregar el enlace.";
            }

            $stmt->close();
        }
    }
}

?>

<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="stylesheet" type="text/css" href="/styles.css">
	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
	<title>Agregar Producto</title>
	<link rel="icon" href="assets/logo.ico">
	<script>
		// Función para mostrar el mensaje emergente y redirigir después de un clic en "Aceptar"
		function showSuccessMessage() {
			alert("Enlace agregado correctamente.");
			window.location.href = "papeleria.php"; // Redirigir a papeleria.php
		}

		// Función para mostrar mensaje de error
		function showErrorMessage() {
			alert("Hubo un error al agregar el enlace. Inténtalo nuevamente.");
		}
	</script>
</head>
<body id="fondo">
	<div class="login-container">
		<h2>Agregar Producto</h2>

		<!-- Mostrar el mensaje de éxito o error -->
		<?php if (isset($success)) { ?>
			<script>
				// Si se agrega correctamente, mostrar el mensaje emergente
				showSuccessMessage();
			</script>
		<?php } ?>

		<?php if (isset($error)) { ?>
			<script>
				// Si hay error, mostrar el mensaje de error
				showErrorMessage();
			</script>
		<?php } ?>

		<form action="agregar_producto.php" method="POST">
			<div class="input-group">
				<label for="link">Link:</label>
				<input type="text" name="link" id="link" required><br>
			</div>
			<p class="part">De preferencia buscar el prodcucto en <a href="https://www.ofix.mx/" target="_blank" rel="noopener noreferrer">ofix</a></p>
			<input type="submit" name="add_link" value="Agregar Link" id="addp"><br>
		</form>
	</div>

	<a href="papeleria.php" class="btn btn-danger" id="back">REGRESAR</a>
</body>
</html>
