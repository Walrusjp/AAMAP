<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: /login.php");
    exit();
}

require 'C:/xampp/htdocs/db_connect.php';

// Obtener categorías y proveedores para los select
$categorias = $conn->query("SELECT * FROM categorias_almacen")->fetch_all(MYSQLI_ASSOC);
$proveedores = $conn->query("SELECT * FROM proveedores WHERE activo = TRUE")->fetch_all(MYSQLI_ASSOC);

// Función para obtener el siguiente código disponible
function getNextCode($conn, $prefix) {
    $query = "SELECT codigo FROM inventario_almacen 
              WHERE codigo LIKE '$prefix%' 
              ORDER BY codigo DESC LIMIT 1";
    $result = $conn->query($query);
    
    if ($result->num_rows > 0) {
        $last_code = $result->fetch_assoc()['codigo'];
        $last_number = intval(substr($last_code, strlen($prefix) + 1));
        $new_number = $last_number + 1;
    } else {
        $new_number = 1;
    }
    
    return sprintf("%s-%03d", $prefix, $new_number);
}

// Mapeo de categorías a prefijos
$prefixes = [
    'Consumibles' => 'CON',
    'EPP' => 'EPP',
    'MP' => 'MP',
    'herramienta_menor' => 'HMEN',
    'herramienta_mayor' => 'HMAY',
    'miscelaneos' => 'MSC'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo = $_POST['codigo'];
    $descripcion = $_POST['descripcion'];
    $id_cat_alm = $_POST['id_cat_alm'];
    $existencia = $_POST['existencia'] ?? 0;
    $min_stock = $_POST['min_stock'] ?? 0;
    $max_stock = $_POST['max_stock'] ?? 0;
    $unidad_medida = $_POST['unidad_medida'];
    $id_pr = $_POST['id_pr'] ?? null;
    $activo = isset($_POST['activo']) ? 1 : 0;

    // Validar código único (por si acaso)
    $check_code = $conn->prepare("SELECT id_alm FROM inventario_almacen WHERE codigo = ?");
    $check_code->bind_param("s", $codigo);
    $check_code->execute();
    if ($check_code->get_result()->num_rows > 0) {
        echo "<script>alert('El código $codigo ya está registrado en el sistema');</script>";
    } else {
        // Insertar el artículo
        $sql = "INSERT INTO inventario_almacen 
                (codigo, descripcion, id_cat_alm, existencia, min_stock, max_stock, unidad_medida, id_pr) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssiiiiii", $codigo, $descripcion, $id_cat_alm, $existencia, $min_stock, $max_stock, $unidad_medida, $id_pr);

        if ($stmt->execute()) {
            $id_articulo = $stmt->insert_id;
            
            // Registrar movimiento inicial si hay existencia
            if ($existencia > 0) {
                $movimiento = "INSERT INTO movimientos_almacen 
                              (id_alm, tipo_mov, cantidad, id_usuario, notas) 
                              VALUES (?, 'ajuste', ?, ?, 'Carga inicial')";
                $stmt_mov = $conn->prepare($movimiento);
                $stmt_mov->bind_param("iii", $id_articulo, $existencia, $_SESSION['user_id']);
                $stmt_mov->execute();
            }
            
            echo "<script>alert('Artículo registrado exitosamente.'); window.location.href = 'ver_almacen.php';</script>";
            exit();
        } else {
            echo "<script>alert('Error al registrar el artículo: " . $stmt->error . "');</script>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar Artículo en Almacén</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="icon" href="/assets/logo.png" type="image/png">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>

<div class="container mt-4">
    <h1>Registrar Artículo en Almacén</h1>
    <a href="ver_almacen.php" class="btn btn-secondary mb-3">Regresar</a>
    <form method="POST" action="reg_articulo_alm.php">
        <div class="form-group">
            <label for="id_cat_alm">Categoría:</label>
            <select class="form-control" id="id_cat_alm" name="id_cat_alm" required>
                <option value="">Seleccionar categoría</option>
                <?php foreach ($categorias as $cat): 
                    // Determinar el prefijo para esta categoría
                    $prefix = '';
                    foreach ($prefixes as $catName => $pref) {
                        if (stripos($cat['categoria'], $catName) !== false) {
                            $prefix = $pref;
                            break;
                        }
                    }
                ?>
                    <option value="<?php echo $cat['id_cat_alm']; ?>" data-prefix="<?php echo $prefix; ?>">
                        <?php echo htmlspecialchars($cat['categoria']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="codigo">Código:</label>
            <input type="text" class="form-control" id="codigo" name="codigo" required>
        </div>
        <div class="form-group">
            <label for="descripcion">Descripción:</label>
            <textarea class="form-control" id="descripcion" name="descripcion" required></textarea>
        </div>
        <div class="form-group">
            <label for="unidad_medida">Unidad de Medida:</label>
            <input type="text" class="form-control" id="unidad_medida" name="unidad_medida" value="PZA">
        </div>
        <div class="form-group">
            <label for="existencia">Existencia Inicial:</label>
            <input type="number" class="form-control" id="existencia" name="existencia" min="0" value="0">
        </div>
        <div class="form-group">
            <label for="id_pr">Proveedor Principal:</label>
            <select class="form-control" id="id_pr" name="id_pr">
                <option value="">Sin proveedor</option>
                <?php foreach ($proveedores as $prov): ?>
                    <option value="<?php echo $prov['id_pr']; ?>">
                        <?php echo htmlspecialchars($prov['empresa']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="min_stock">Stock Mínimo:</label>
            <input type="number" class="form-control" id="min_stock" name="min_stock" min="0" value="0">
        </div>
        <div class="form-group">
            <label for="max_stock">Stock Máximo:</label>
            <input type="number" class="form-control" id="max_stock" name="max_stock" min="0" value="0">
        </div>
        <button type="submit" class="btn btn-primary">Registrar Artículo</button>
    </form>
</div>

<script>
$(document).ready(function() {
    // Cuando cambia la categoría
    $('#id_cat_alm').change(function() {
        var selectedOption = $(this).find('option:selected');
        var prefix = selectedOption.data('prefix');
        
        if (prefix) {
            // Mostrar el código base mientras se carga
            $('#codigo').val(prefix + '-000');
            
            // Obtener el siguiente código disponible via AJAX
            $.ajax({
                url: 'get_next_code.php',
                type: 'POST',
                data: { prefix: prefix },
                success: function(response) {
                    $('#codigo').val(response);
                },
                error: function() {
                    alert('Error al generar el código');
                    $('#codigo').val(prefix + '-001'); // Valor por defecto si falla
                }
            });
        } else {
            $('#codigo').val('');
        }
    });
    
    // Disparar el cambio al cargar si ya hay una categoría seleccionada
    if ($('#id_cat_alm').val()) {
        $('#id_cat_alm').trigger('change');
    }
});
</script>

</body>
</html>