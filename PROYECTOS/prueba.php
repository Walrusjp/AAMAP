<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Select estilo menú con Bootstrap</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <style>
    /* Soporte para submenú en dropdown Bootstrap */
    .dropdown-submenu > .dropdown-menu {
    display: none;
    margin-top: 0;
    }

    .dropdown-submenu:hover > .dropdown-menu {
    display: block;
    }
  </style>
</head>
<body class="p-5">

<div class="dropdown">
  <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="almacenDropdown" data-bs-toggle="dropdown" aria-expanded="false">
    Almacén
  </button>
  <ul class="dropdown-menu">
    <li><a class="dropdown-item" href="#" data-value="todos" data-url="/productos/todos">Todos</a></li>
    <li><a class="dropdown-item" href="#" data-value="directo" data-url="/productos?tipo=directo">Directas</a></li>
    <li><a class="dropdown-item" href="#" data-value="en proceso" data-url="/productos?estado=enproceso">Proyectos</a></li>
    <li><a class="dropdown-item" href="#" data-value="en proceso,directo" data-url="/productos?estado=enproceso&tipo=directo">En proceso</a></li>
    <li><a class="dropdown-item" href="#" data-value="finalizado" data-url="/productos/finalizados">Finalizados</a></li>

    <!-- Submenú Facturación -->
    <li class="dropdown-submenu position-relative">
      <a class="dropdown-item dropdown-toggle" href="#">Facturación</a>
      <ul class="dropdown-menu position-absolute start-100 top-0">
        <li><a class="dropdown-item" href="#" data-value="facturacion1" data-url="/facturacion/compras">Compras</a></li>
        <li><a class="dropdown-item" href="#" data-value="facturacion2" data-url="/facturacion/ventas">Ventas</a></li>
      </ul>
    </li>
  </ul>
</div>

  <script>
    const dropdownItems = document.querySelectorAll('.dropdown-item');
    const dropdownButton = document.getElementById('dropdownMenuButton');
    const hiddenInput = document.getElementById('valorSeleccionado');

    dropdownItems.forEach(item => {
      item.addEventListener('click', function (e) {
        e.preventDefault();
        const texto = this.textContent;
        const valor = this.getAttribute('data-value');
        dropdownButton.textContent = texto;
        hiddenInput.value = valor;
      });
    });

    // Ejemplo: mostrar el valor seleccionado al enviar el formulario
    document.getElementById('miFormulario').addEventListener('submit', function (e) {
      e.preventDefault();
      alert("Valor enviado: " + hiddenInput.value);
    });
  </script>

</body>
</html>
