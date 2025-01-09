<?php
	session_start();
	if (!isset($_SESSION['username'])) {
	    header("Location: index.php");
	    exit();
	}

	include 'db_connect.php';
	include 'role.php';

	// Verificar si se solicitó el cierre de sesión
	if (isset($_POST['logout'])) {
	    session_unset();
	    session_destroy();
	    header("Location: index.php");
	    exit();
	}
?>

<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="stylesheet" type="text/css" href="styles.css">
	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
	<title>Agregar Producto</title>
</head>
<body id="fondo">
	<div class="login-container">
		<h2>Agregar Producto</h2>
		<form action="agregar_producto.php" method="POST" enctype="multipart/form-data">
			<div class="input-group">
				<label for="id">ID:</label>
				<input type="text" name="id" id="id" value="PROD000 (cambiarlo)" required><br>
			</div>
			<div class="input-group">
				<label for="imagen">Imagen:</label>
				<input type="file" name="imagen" id="imagen" required><br>
			</div>
			<div class="input-group">
				<label for="descripcion">Descripción:</label><br>
				<textarea name="descripcion" id="descripcion" rows="4" cols="45" maxlength="200" required></textarea><br>
			</div>
			<div class="input-group">
				<label for="precio">Precio:</label>
				<input type="number" step="0.01" name="precio" id="precio" required><br>
			</div>

			<p class="part">Buscar el prodcucto solicitado en <a href="https://www.ofix.mx/" target="_blank" rel="noopener noreferrer">ofix</a></p>

			<input type="submit" value="Agregar Producto" id="addp"><br>
		</form>
	</div>

	<a href="papeleria.php" class="btn btn-danger" id="back">REGRESAR</a>
</body>
</html>