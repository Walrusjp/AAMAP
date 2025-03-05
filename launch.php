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
    background: linear-gradient(65deg, #1e3c72, #2a5298);
    position: relative;
}

/* Navbar con fondo translúcido */
.navbar {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    background-color: rgba(255, 255, 255, 0.7); /* Fondo translúcido */
    padding: 10px 20px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    display: flex;
    align-items: center;
    justify-content: flex-start;
    z-index: 10;
}

.navbar img {
    height: 90px; /* Ajusta el tamaño del logo en la navbar */
}

/* Div principal centrado */
.launcher-container {
    background-color: rgba(255, 255, 255, 0.8); /* Fondo semi-transparente */
    padding: 30px;
    border-radius: 15px;
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3);
    text-align: center;
    width: 500px;
    backdrop-filter: blur(10px); /* Efecto de desenfoque */
}

/* Botones */
.launcher-container button {
    display: block;
    width: 100%;
    padding: 12px;
    margin: 15px 0;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    cursor: pointer;
    background-color: #0084D5;
    color: white;
    transition: background-color 0.3s ease, transform 0.2s ease;
}

.launcher-container button:hover {
    background-color: #0056b3;
    transform: translateY(-2px); /* Efecto de elevación al pasar el ratón */
}

/* Imagen flotante */
.floating-image {
    position: absolute;
    width: 250px; /* Ajusta el tamaño según prefieras */
    top: 20px;
    left: 20px; /* Puedes ajustar estas posiciones */
    cursor: pointer;
    filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.3)); /* Sombra para la imagen */
}

/* Efecto de luz detrás del logo */
.launcher-container::before {
    content: '';
    position: absolute;
    top: -20px;
    left: -20px;
    right: -20px;
    bottom: -20px;
    background: radial-gradient(circle, rgba(255, 255, 255, 0.2), rgba(255, 255, 255, 0));
    z-index: -1;
    border-radius: 25px;
}

/* Tipografía elegante */
.launcher-container h1 {
    font-size: 24px;
    color: #333;
    margin-bottom: 20px;
    font-weight: bold;
}

/* Iconos minimalistas */
.launcher-container .icon {
    width: 24px;
    height: 24px;
    margin-right: 10px;
    vertical-align: middle;
    fill: #007bff; /* Color de los iconos */
}
    </style>
</head>
<body>
    <!-- Navbar con logo -->
    <div class="navbar">
        <img src="/assets/aamap.png" alt="Logo AAMAP">
    </div>

    <!-- Contenedor centrado -->
    <div class="launcher-container">
        <h1>ESCOGE UNA OPCION</h1>
        <button onclick="window.location.href='/PAPELERIA/index.php'">PAPELERIA</button>
        <button onclick="window.location.href='/error404.html'">VIATICOS</button><!--/PREDIEM/index.php-->
        <button onclick="window.location.href='/PROYECTOS/index.php'">CRM PROYECTOS</button>
        <button onclick="window.location.href='/ERP/index.php'">ERP PROYECTOS</button>
    </div>
</body></html>