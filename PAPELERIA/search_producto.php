<?php
//session_start();
include 'db_connect.php';
//include 'role.php';

//ocultar productos a op
$productos_ocultos = ['PROD000', 'PROD001', 'PROD002', 'PROD003'];

// Habilitar la visualización de errores para depuración
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Si se recibe el parámetro 'query', actúa como una API JSON
if (isset($_GET['query'])) {
    header('Content-Type: application/json');

    $query = $_GET['query'];
    
    // Consultar productos que coincidan con el texto ingresado
    $sql = "SELECT id, descripcion, imagen FROM productos WHERE id LIKE ? LIMIT 10";
    $stmt = $conn->prepare($sql);
    $likeQuery = "%$query%";
    $stmt->bind_param("s", $likeQuery);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $products = [];
    while ($row = $result->fetch_assoc()) {
        // Si el producto está en la lista de productos ocultos, lo omitimos
        if (in_array($row['id'], $productos_ocultos)) {
            continue;
        }
        
        // Añadir el producto al array de productos
        $products[] = $row;
    }

    $stmt->close();

    // Devolver el resultado en formato JSON
    echo json_encode($products);
    exit;
}

// Si no se recibe 'query', mostrar la página de prueba
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <style type="text/css">
        #suggestions {
            position: absolute;
            z-index: 1000;
            width: calc(50% - 2px);
            border: 1px solid #ccc;
            background: rgba(255, 255, 255, 0.4);
            box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.2);
            font-size: 14px;
            max-height: 200px;
            overflow-y: auto;
        }
        #suggestions div:hover {
            background-color: #f0f0f0;
        }

        #suggestions img {
            width: 40px;
            height: 40px;
            margin-right: 10px;
            object-fit: cover;
            border: 1px solid #ccc;
            border-radius: 3px;
        }

    </style>
</head>
<body>
    <!--<h1>Prueba de Búsqueda de Productos</h1>
    <input type="text" id="product_code" onkeyup="searchProduct(this.value)" placeholder="Buscar producto...">
    <div id="suggestions" style="display: none;"></div>-->

    <script>
        function searchProduct(query) {
            const suggestionsDiv = document.getElementById("suggestions");

            if (query.length === 0) {
                suggestionsDiv.style.display = "none";
                suggestionsDiv.innerHTML = "";
                return;
            }

            // Llamada AJAX para obtener resultados
            fetch(`search_producto.php?query=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.length > 0) {
                        let suggestions = "";
                        data.forEach(product => {
                            suggestions += `
                                <div style="padding: 5px; cursor: pointer; display: flex; align-items: center;" 
                                     onclick="selectProduct('${product.id}')">
                                    <img src="${product.imagen}" alt="${product.descripcion}" style="width: 40px; height: 40px; margin-right: 10px; object-fit: cover; border: 1px solid #ccc; border-radius: 3px;">
                                    <div>
                                        <strong>${product.id}</strong> - ${product.descripcion}
                                    </div>
                                </div>`;
                        });
                        suggestionsDiv.innerHTML = suggestions;
                        suggestionsDiv.style.display = "block";
                    } else {
                        suggestionsDiv.innerHTML = "<div style='padding: 5px;'>No se encontraron productos</div>";
                        suggestionsDiv.style.display = "block";
                    }
                })
                .catch(error => {
                    console.error("Error:", error);
                });
        }

        function selectProduct(productCode) {
            document.getElementById("product_code").value = productCode;
            document.getElementById("suggestions").style.display = "none";
            document.getElementById("suggestions").innerHTML = "";
        }
    </script>
</body>
</html>
