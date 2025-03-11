<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: /login.php");
    exit();
}

require 'C:/xampp/htdocs/db_connect.php';
require 'C:/xampp/htdocs/role.php';

// Obtener los proyectos desde la base de datos
$sql = "SELECT
            p.cod_fab AS proyecto_id,
            p.nombre AS proyecto_nombre,
            p.descripcion,
            p.etapa AS estatus,
            p.observaciones,
            p.fecha_entrega,
            c.nombre_comercial AS cliente_nombre -- Usar nombre comercial del cliente
        FROM proyectos AS p
        INNER JOIN clientes_p AS c ON p.id_cliente = c.id

        ORDER BY p.cod_fab ASC";

$result = $conn->query($sql);
$proyectos = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $proyectos[] = $row;
    }
}

// Función para actualizar el estatus del proyecto
function actualizarEstatusProyecto($conn, $proyecto_id, $nuevo_estatus, $observaciones = null) {
    if ($observaciones !== null) {
        $sql = "UPDATE proyectos SET etapa = ?, observaciones = ? WHERE cod_fab = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $nuevo_estatus, $observaciones, $proyecto_id);
    } else {
        $sql = "UPDATE proyectos SET etapa = ? WHERE cod_fab = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $nuevo_estatus, $proyecto_id);
    }
    
    if ($stmt->execute()) {
        return true; // Éxito al actualizar
    } else {
        return false; // Error al actualizar
    }

    $stmt->close();
}

// Variables para almacenar el mensaje y la redirección
$mensaje = "";
$redireccionar = false;

// Manejar la solicitud de aprobación de cotización
if (isset($_POST['aprobar_cotizacion'])) {
    $proyecto_id = $_POST['proyecto_id'];
    
    echo "<script>
            if (confirm('¿Estás seguro de que quieres aprobar esta cotización?')) {";
                if (actualizarEstatusProyecto($conn, $proyecto_id, 'aprobado')) {
                    $mensaje = "Cotización aprobada con éxito.";
                    $redireccionar = true;
                } else {
                    $mensaje = "Error al aprobar la cotización.";
                }
            echo "}
          </script>";
}

// Manejar la solicitud de rechazo de cotización
if (isset($_POST['rechazar_cotizacion'])) {
    $proyecto_id = $_POST['proyecto_id'];
    $observaciones = $_POST['observaciones'];
    
    echo "<script>
            if (confirm('¿Estás seguro de que quieres rechazar esta cotización?')) {";
                if (actualizarEstatusProyecto($conn, $proyecto_id, 'rechazado', $observaciones)) {
                    $mensaje = "Cotización rechazada.";
                    $redireccionar = true;
                } else {
                    $mensaje = "Error al rechazar la cotización.";
                }
            echo "}
          </script>";
}

//Manejar la solicitud de mandar a ERP
if (isset($_POST['en_proceso'])) {
    $proyecto_id = $_POST['proyecto_id'];

    echo "<script>
            if (confirm('¿Estás seguro de que quieres rechazar esta cotización?')) {";
                if (actualizarEstatusProyecto($conn, $proyecto_id, 'en proceso')) {
                    $mensaje = "Se mandó al ERP.";
                    $redireccionar = true;
                } else {
                    $mensaje = "Error al enviar cotización.";
                }
            echo "}
          </script>";

}

// Mostrar el mensaje y redirigir solo si es necesario
if ($mensaje !== "") {
    echo "<script>alert('" . $mensaje . "');</script>";
    if ($redireccionar) {
        header("Location: all_projects.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>CRM Proyectos</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="stprojects.css">
    <link rel="icon" href="/assets/logo.ico">
    <style>
        .proyectos-container {
            width: 100%; /* Asegura que el contenedor ocupe todo el ancho */
            padding: 0; /* Elimina el padding si es necesario */
            margin: 0; /* Elimina el margen si es necesario */
        }

        #proyectos-container {
            width: 100%; /* Asegura que el contenedor interno ocupe todo el ancho */
            padding: 0; /* Elimina el padding si es necesario */
            margin: 0; /* Elimina el margen si es necesario */
        }

        .proyecto-card {
            width: 100%; /* Asegura que la tarjeta ocupe todo el ancho */
            margin-bottom: 10px; /* Espaciado entre tarjetas */
        }

        .card {
            border: 1px solid #ddd; /* Borde para las tarjetas */
            border-radius: 8px; /* Bordes redondeados */
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); /* Sombra ligera */
        }

        .card-body {
            padding: 0.8rem; /* Espaciado interno */
        }

        .card-title {
            font-size: 1.25rem; /* Tamaño del título */
            font-weight: bold; /* Negrita */
        }

        .card-text {
            font-size: 1rem; /* Tamaño del texto */
        }
    </style>
</head>
<body>
<img src="/assets/grupo_aamap.png" style="width: 18%; position: absolute; top: 5px; left: 10px;">
<div class="sticky-header">
    <div class="container d-flex justify-content-between chompa">
        <p class="text-center">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</p>
        <div class="d-flex justify-content-center mb-3">
            <label for="filter" class="mr-2">Filtrar:</label>
            <select id="filter" class="form-control w-auto">
                <option value="todos">Todos</option>
                <option value="creado">Creado</option>
                <option value="aprobado">Aprobado</option>
                <option value="rechazado">No Concretado</option>
                <option value="en proceso,finalizado,facturacion">ERP</option>
            </select>
            <a href="new_project.php" class="btn btn-info chompa">Nueva Cotización</a>
            <a href="edit_project.php" class="btn btn-info chompa">Editar Cotización</a>
            <a href="ver_clientes.php" class="btn btn-info chompa">Clientes</a>
            <!--<a href="lista_cot.php" class="btn btn-info chompa">Cotizaciones</a>-->
            <?php if ($username == 'admin'): ?>
                <a href="delete_project.php" class="btn btn-danger chompa">Eliminar Proyecto</a>
            <?php endif; ?>
            <a href="/launch.php" class="btn btn-secondary chompa">Regresar</a>
            <!--<form method="POST" action="">
                <button type="submit" name="logout" class="btn btn-secondary chompa">Cerrar sesión</button>
            </form>-->
        </div>
    </div>
</div>

<div class="proyectos-container">
    <div id="proyectos-container">
        <?php if (!empty($proyectos)): ?>
            <br>
            <?php foreach ($proyectos as $proyecto): ?>
                <div class="proyecto-card w-100 mb-3" data-estatus="<?php echo htmlspecialchars($proyecto['estatus']); ?>">
                    <a href="ver_cot.php?id=<?php echo urlencode($proyecto['proyecto_id']); ?>" class="card-link" target="_blank">
                        <div class="card text-<?php 
                            echo ($proyecto['estatus'] == 'rechazado' ? 'danger' :  
                                  ($proyecto['estatus'] == 'aprobado' ? 'success' :  
                                  ($proyecto['estatus'] == 'creado' ? 'warning' : 'dark')));
                        ?>">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($proyecto['proyecto_id']); ?> || <?php echo htmlspecialchars($proyecto['proyecto_nombre']); ?></h5>
                                <p class="card-text">
                                    Cliente: <?php echo htmlspecialchars($proyecto['cliente_nombre']); ?><br>
                                    Nota: <?php echo htmlspecialchars($proyecto['descripcion']); ?><br>
                                </p>
                            </div>
                        </div>
                    </a>

                    <?php if ($proyecto['estatus'] == 'creado'): ?>
                        <button type="button" class="btn btn-success mt-2 btn-card" onclick="confirmarAccion('aprobar', '<?php echo htmlspecialchars($proyecto['proyecto_id']); ?>')">Aprobar Cot</button>
                        <button type="button" class="btn btn-danger mt-2 btn-card" onclick="confirmarAccion('rechazar', '<?php echo htmlspecialchars($proyecto['proyecto_id']); ?>')">No Concretar</button>
                    <?php endif; ?>

                    <!-- Pasar a CIS ERP -->
                    <?php if ($proyecto['estatus'] == 'aprobado'): ?>
                        <button type="button" class="btn btn-success mt-2 btn-card" onclick="confirmarAccion('cis', '<?php echo htmlspecialchars($proyecto['proyecto_id']); ?>')">Mandar a OF</button>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12">
                <p class="text-muted text-center">No hay proyectos disponibles.</p>
            </div>
        <?php endif; ?>
    </div>
</div>



<script>
    // Filtrar proyectos dinámicamente por estado
    document.getElementById('filter').addEventListener('change', function () {
        const selectedFilter = this.value;
        const proyectos = document.querySelectorAll('.proyecto-card');

        proyectos.forEach(function (proyecto) {
            if (selectedFilter === 'todos') {
                proyecto.style.display = 'block';
            } else {
                // Convertir la lista de estados en un array
                const estados = selectedFilter.split(','); 
                if (estados.includes(proyecto.dataset.estatus)) {
                    proyecto.style.display = 'block';
                } else {
                    proyecto.style.display = 'none';
                }
            }
        });
    });

    function confirmarAccion(accion, proyecto_id) {
        if (accion === 'aprobar') {
            if (confirm('¿Estás seguro de que quieres aprobar esta cotización?')) {
                // Enviar formulario de aprobación
                var form = document.createElement('form');
                form.method = 'POST';
                form.action = ''; // La misma página

                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'proyecto_id';
                input.value = proyecto_id;
                form.appendChild(input);

                input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'aprobar_cotizacion';
                input.value = '1'; 
                form.appendChild(input);

                document.body.appendChild(form);
                form.submit();
            }
        } else if (accion === 'rechazar') {
            var observaciones = prompt("Por favor, ingresa las observaciones para el rechazo:");
            if (observaciones !== null) { 
                // Enviar formulario de rechazo con observaciones
                var form = document.createElement('form');
                form.method = 'POST';
                form.action = ''; // La misma página

                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'proyecto_id';
                input.value = proyecto_id;
                form.appendChild(input);

                input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'rechazar_cotizacion';
                input.value = '1'; 
                form.appendChild(input);

                // Agregar input para las observaciones
                input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'observaciones';
                input.value = observaciones;
                form.appendChild(input);

                document.body.appendChild(form);
                form.submit();
            }
        } else if (accion === 'cis') {
            if (confirm('¿Estás seguro de que quieres mandar a producción el proyecto?')) {
                //Enviar el form de traspaso
                var form = document.createElement('form');
                form.method = 'POST';
                form.action = '';

                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'proyecto_id';
                input.value = proyecto_id;
                form.appendChild(input);

                input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'en_proceso';
                input.value = '1';
                form.appendChild(input);

                document.body.appendChild(form);
                form.submit();
            }
        }
    }
</script>

</body>
</html>