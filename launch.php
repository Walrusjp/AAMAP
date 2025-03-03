<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="/assets/logo.png" type="image/png">
    <title>Launcher</title>
    <style>
        /* Reseteo básico */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* Estilos del body para centrar el div */
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            font-family: Arial, sans-serif;
            background-color: #f0f0f0;
            position: relative;
        }

        /* Div principal centrado */
        .launcher-container {
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            text-align: center;
            width: 500px;
        }

        /* Botones */
        .launcher-container button {
            display: block;
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            background-color: #007bff;
            color: white;
        }

        .launcher-container button:hover {
            background-color: #0056b3;
        }

        /* Imagen flotante */
        .floating-image {
            position: absolute;
            width: 300px; /* Ajusta el tamaño según prefieras */
            top: 20px;
            left: 20px; /* Puedes ajustar estas posiciones */
            cursor: pointer;
        }
    </style>
</head>
<body>
    <!-- Imagen flotante -->
    <img src="/assets/aamap.png" alt="Imagen flotante" class="floating-image">

    <!-- Contenedor centrado -->
    <div class="launcher-container">
	<h1>ESCOGE UNA OPCION</h1>
        <button onclick="window.location.href='/PAPELERIA/index.php'">PAPELERIA</button>
        <button onclick="window.location.href='/PREDIEM/index.php'">VIATICOS</button>
        <button onclick="window.location.href='/PROYECTOS/index.php'">CRM PROYECTOS</button>
        <button onclick="window.location.href='/ERP/index.php'">ERP PROYECTOS</button>
    </div>
</body>
</html>