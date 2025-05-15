<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: /login.php");
    exit();
}

require 'C:/xampp/htdocs/db_connect.php';
require 'C:/xampp/htdocs/role.php';
require 'send_email.php';

// Obtener los proyectos desde la base de datos
$sql = "SELECT
    p.cod_fab AS proyecto_id,
    p.nombre AS proyecto_nombre,
    p.descripcion,
    p.etapa AS estatus,
    p.observaciones,
    p.fecha_entrega,
    c.nombre_comercial AS cliente_nombre
FROM proyectos AS p
INNER JOIN clientes_p AS c ON p.id_cliente = c.id
WHERE p.etapa != 'directo'
ORDER BY 
    FIELD(p.etapa, 'creado', 'aprobado', 'rechazado', 'en proceso', 'facturacion', 'finalizado'),
    p.cod_fab ASC";

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
    
    $result = $stmt->execute();
    $stmt->close();
    
    return $result;
}

// Función para insertar orden de fabricación
function insertarOrdenFab($conn, $proyecto_id) {
    $sql_insert = "INSERT INTO orden_fab 
        (of_created, id_proyecto, id_cliente, updated_at)
        SELECT 
            NOW(), 
            p.cod_fab, 
            p.id_cliente,
            NOW()
        FROM proyectos p
        WHERE p.cod_fab = ?";
    $stmt_insert = $conn->prepare($sql_insert);
    $stmt_insert->bind_param("s", $proyecto_id);
    $result = $stmt_insert->execute();
    $stmt_insert->close();
    return $result;
}

// Variables para almacenar el mensaje y la redirección
$mensaje = "";
$redireccionar = false;

// Manejar la solicitud de mandar a ERP sin notificación (solo admin)
if (isset($_POST['en_proceso_sin_correo'])) {
    $proyecto_id = $_POST['proyecto_id'];
    $conn->begin_transaction();
    
    try {
        if (actualizarEstatusProyecto($conn, $proyecto_id, 'en proceso') && insertarOrdenFab($conn, $proyecto_id)) {
            $conn->commit();
            $mensaje = "Proyecto enviado a OF sin notificación";
            $redireccionar = true;
        } else {
            throw new Exception("Error al procesar la orden de fabricación");
        }
    } catch (Exception $e) {
        $conn->rollback();
        $mensaje = "Error: " . $e->getMessage();
    }
    
    echo "<script>alert('".addslashes($mensaje)."'); window.location.href='all_projects.php';</script>";
    exit();
}

// Manejar la solicitud de aprobación de cotización
if (isset($_POST['aprobar_cotizacion'])) {
    $proyecto_id = $_POST['proyecto_id'];
    
    echo "<script>
            if (confirm('¿Estás seguro de que quieres aprobar esta cotización?')) {";
                $conn->begin_transaction();
                try {
                    if (actualizarEstatusProyecto($conn, $proyecto_id, 'aprobado') && insertarOrdenFab($conn, $proyecto_id)) {
                        $conn->commit();
                        $mensaje = "Cotización aprobada con éxito.";
                        $redireccionar = true;
                    } else {
                        throw new Exception("Error al aprobar la cotización");
                    }
                } catch (Exception $e) {
                    $conn->rollback();
                    $mensaje = $e->getMessage();
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

// Manejar la solicitud de mandar a ERP
if (isset($_POST['en_proceso'])) {
    $proyecto_id = $_POST['proyecto_id'];
    $conn->begin_transaction();

    try {
        if (actualizarEstatusProyecto($conn, $proyecto_id, 'en proceso') && insertarOrdenFab($conn, $proyecto_id)) {
            // Obtener datos para el correo
            $proyecto_data = $proyectos[array_search($proyecto_id, array_column($proyectos, 'proyecto_id'))];
            
            $sql_partidas = "SELECT descripcion, cantidad, unidad_medida 
                           FROM partidas 
                           WHERE cod_fab = ? LIMIT 20";
            $stmt = $conn->prepare($sql_partidas);
            $stmt->bind_param("s", $proyecto_id);
            $stmt->execute();
            $partidas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            // Configuración del correo
            $to = 'cop.aamap@aamap.net';
            $subject = "Nuevo Proyecto en ERP: " . $proyecto_data['proyecto_nombre'];
            
            $body = "<h3>Se inicia proyecto {$proyecto_data['proyecto_id']}: {$proyecto_data['proyecto_nombre']}</h3>";
            $body .= "<p><strong>Cliente:</strong> {$proyecto_data['cliente_nombre']}</p>";
            $body .= "<p><strong>Entrega:</strong> {$proyecto_data['fecha_entrega']}</p>";
            
            if (!empty($partidas)) {
                $body .= "<h4>Partidas:</h4><table border='1' cellpadding='5'>";
                $body .= "<tr><th>#</th><th>Descripción</th><th>Cantidad</th><th>Unidad</th></tr>";
                
                foreach ($partidas as $i => $partida) {
                    $body .= "<tr>
                        <td>".($i+1)."</td>
                        <td>{$partida['descripcion']}</td>
                        <td>{$partida['cantidad']}</td>
                        <td>{$partida['unidad_medida']}</td>
                    </tr>";
                }
                $body .= "</table>";
            }

            $body .= "<p>Favor de gestionar las actividades correspondientes.</p>";

            // Envío con manejo de errores
            try {
                send_email_order($to, $subject, $body);
                $mensaje = "Proyecto enviado a ERP y notificado correctamente";
                $conn->commit();
            } catch (Exception $e) {
                $body_simple = "<p>Se inicia proyecto {$proyecto_data['proyecto_id']}. Ver detalles en sistema.</p>";
                send_email_order($to, $subject, $body_simple);
                $mensaje = "Proyecto enviado (notificación básica)";
                $conn->commit();
            }
        } else {
            throw new Exception("Error al procesar la orden de fabricación");
        }
    } catch (Exception $e) {
        $conn->rollback();
        $mensaje = "Error: " . $e->getMessage();
    }

    echo "<script>alert('".addslashes($mensaje)."'); window.location.href='all_projects.php';</script>";
    exit();
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
</head>
<body>
<div class="navbar" style="display: flex; align-items: center; justify-content: space-between; padding: 0px; background-color: #f8f9fa; position: relative;">
    <!-- Logo -->
    <img src="/assets/grupo_aamap.webp" alt="Logo AAMAP" style="width: 18%; position: absolute; top: 25px; left: 10px;">

    <!-- Contenedor de elementos alineados a la derecha -->
    <div class="sticky-header" style="width: 100%;">
        <div class="container" style="display: flex; justify-content: flex-end; align-items: center;">
        <div style="position: absolute; top: 90px; left: 600px;"><p style="font-size: 2.5em; font-family: 'Verdana';"><b>C R M</b></p></div>
            <!-- Filtro y botones -->
            <div style="display: flex; align-items: center; gap: 0px;">
                <!-- Filtro -->
                <div style="display: flex; align-items: center;">
                    <label for="filter" style="margin-right: 5px; margin-top: 5px;">Filtrar:</label>
                    <select id="filter" class="form-control" style="width: auto;">
                        <option value="todos">Todos</option>
                        <option value="creado">Creado</option>
                        <option value="aprobado">Aprobado</option>
                        <option value="rechazado">No Concretado</option>
                        <option value="en proceso,finalizado,facturacion">ERP</option>
                    </select>
                </div>

                <?php if ($role == 'admin'): ?>
                <!-- Botones -->
                <a href="new_project.php" class="btn btn-info chompa">Nueva Cotización</a>
                <a href="edit_project.php" class="btn btn-info chompa">Editar Cotización</a>
                <?php endif; ?>
                <a href="ver_clientes.php" class="btn btn-info chompa">Clientes</a>
                <?php if ($username == 'admin'): ?>
                    <a href="delete_project.php" class="btn btn-danger chompa"><img src="/assets/delete.ico" style="width: 30px; height: auto; alt=""></a>
                <?php endif; ?>
                <a href="/launch.php" class="btn btn-secondary chompa">Regresar</a>
            </div>
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

                    <?php if ($role == 'admin'): ?>
                    <?php if ($proyecto['estatus'] == 'creado'): ?>
                        <button type="button" class="btn btn-success mt-2 btn-card" onclick="confirmarAccion('aprobar', '<?php echo htmlspecialchars($proyecto['proyecto_id']); ?>')">Aprobar Cot</button>
                        <button type="button" class="btn btn-danger mt-2 btn-card" onclick="confirmarAccion('rechazar', '<?php echo htmlspecialchars($proyecto['proyecto_id']); ?>')">No Concretar</button>
                    <?php endif; ?>

                    <!-- Pasar a CIS ERP -->
                    <?php if ($proyecto['estatus'] == 'aprobado'): ?>
                        <div class="d-flex">
                            <button type="button" class="btn btn-success mt-2 btn-card" onclick="confirmarAccion('cis', '<?php echo htmlspecialchars($proyecto['proyecto_id']); ?>')">Mandar a OF</button>
                            <?php if ($_SESSION['username'] == 'admin'): ?>
                                <button type="button" class="btn btn-secondary mt-2 btn-card ml-2" onclick="confirmarAccion('cis_sin_correo', '<?php echo htmlspecialchars($proyecto['proyecto_id']); ?>')">OF sin Correo</button>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
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
        const acciones = {
            'aprobar': {
                confirmacion: '¿Estás seguro de que quieres aprobar esta cotización?',
                formData: {
                    'proyecto_id': proyecto_id,
                    'aprobar_cotizacion': '1'
                }
            },
            'rechazar': {
                confirmacion: 'Por favor, ingresa las observaciones para el rechazo:',
                necesitaInput: true,
                formData: (observaciones) => ({
                    'proyecto_id': proyecto_id,
                    'rechazar_cotizacion': '1',
                    'observaciones': observaciones
                })
            },
            'cis': {
                confirmacion: '¿Estás seguro de que quieres mandar a OF?',
                formData: {
                    'proyecto_id': proyecto_id,
                    'en_proceso': '1'
                }
            },
            'cis_sin_correo': {
                confirmacion: '¿Estás seguro de que quieres mandar a OF sin enviar notificación?',
                formData: {
                    'proyecto_id': proyecto_id,
                    'en_proceso_sin_correo': '1'
                }
            }
        };

        const config = acciones[accion];
        
        if (!config) return;

        if (config.necesitaInput) {
            const observaciones = prompt(config.confirmacion);
            if (observaciones !== null) {
                enviarFormulario(typeof config.formData === 'function' 
                    ? config.formData(observaciones) 
                    : config.formData);
            }
        } else if (confirm(config.confirmacion)) {
            enviarFormulario(config.formData);
        }
    }

    function enviarFormulario(data) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '';
        
        Object.entries(data).forEach(([name, value]) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = name;
            input.value = value;
            form.appendChild(input);
        });
        
        document.body.appendChild(form);
        form.submit();
    }
</script>

</body>
</html>