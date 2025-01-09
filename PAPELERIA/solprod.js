// Función para agregar producto
function addProduct() {
    const productCode = document.getElementById('product_code').value;
    const quantity = document.getElementById('quantity').value;
    const errorDiv = document.getElementById('error_message');

    // Validaciones
    if (!productCode || !quantity || quantity <= 0) {
        errorDiv.textContent = "Debe ingresar un código de producto válido y una cantidad mayor a 0.";
        return;
    }

    // Limpiar mensajes de error
    errorDiv.textContent = "";

    // Enviar los datos por AJAX
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'Solicitar_producto.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

    xhr.onload = function() {
        if (xhr.status === 200) {
            const response = JSON.parse(xhr.responseText);
            if (response.success) {
                // Actualizar la tabla de productos añadidos sin recargar la página
                updateProductTable(response.products);
                // Limpiar los inputs
                document.getElementById('product_code').value = '';
                document.getElementById('quantity').value = '';
            } else {
                // Mostrar mensaje de error
                errorDiv.textContent = response.error;
            }
        }
    };

    // Enviar datos al servidor
    xhr.send(`action=add_product&product_code=${productCode}&quantity=${quantity}`);
}

// Función para eliminar producto de la lista
function removeProduct(productCode) {
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'Solicitar_producto.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

    xhr.onload = function() {
        if (xhr.status === 200) {
            const response = JSON.parse(xhr.responseText);
            if (response.success) {
                // Actualizar la tabla de productos añadidos sin recargar la página
                updateProductTable(response.products);
            }
        }
    };

    // Enviar solicitud para eliminar el producto
    xhr.send(`action=remove_product&product_code=${productCode}`);
}

// Función para actualizar la tabla de productos
function updateProductTable(products) {
    const tableBody = document.getElementById('product_table_body');
    tableBody.innerHTML = ''; // Limpiar la tabla

    products.forEach(product => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${product.codigo}</td>
            <td>${product.descripcion}</td>
            <td>${product.cantidad}</td>
            <td>
                <button type="button" onclick="removeProduct('${product.codigo}')" class="btn btn-danger">Borrar</button>
            </td>
        `;
        tableBody.appendChild(row);
    });
}