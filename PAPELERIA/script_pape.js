document.addEventListener("DOMContentLoaded", () => {
    // Función para actualizar el carrito
    const updateCart = (action, productId) => {
        fetch("cart_handler.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
            },
            body: `action=${action}&product_id=${productId}`,
        })
        .then((response) => response.json())
        .then((cart) => {
            // Actualiza la cantidad mostrada para el producto específico
            document.getElementById(`cart-quantity-${productId}`).textContent = cart[productId] || 0;
        })
        .catch((error) => console.error("Error:", error));
    };

    // Manejar clic en el botón "Añadir al carrito"
    document.querySelectorAll(".add-to-cart").forEach((button) => {
        button.addEventListener("click", () => {
            const productId = button.getAttribute('data-id');
            updateCart("add", productId);
        });
    });

    // Manejar clic en el botón "Quitar del carrito"
    document.querySelectorAll(".remove-from-cart").forEach((button) => {
        button.addEventListener("click", () => {
            const productId = button.getAttribute('data-id');
            updateCart("remove", productId);
        });
    });
});

function closeModal() {
    const modal = document.getElementById('welcomeModal');
    modal.classList.remove('show'); // Quita la clase "show" para la animación de salida
    setTimeout(() => {
        modal.style.display = 'none'; // Oculta completamente el modal después de la animación
    }, 500); // 500ms coincide con la duración de la transición
}


function searchProduct(query) {
    const suggestionsDiv = document.getElementById("suggestions");

    if (query.trim().length === 0) {
        // Si no hay texto, ocultar las sugerencias
        suggestionsDiv.style.display = "none";
        suggestionsDiv.innerHTML = "";
        return;
    }

    // Llamada AJAX para buscar resultados
    fetch(`search_producto.php?query=${encodeURIComponent(query)}`)
        .then(response => response.json())
        .then(data => {
            if (data.length > 0) {
                let suggestions = "";
                data.forEach(product => {
                    suggestions += `
                        <div style="padding: 5px; cursor: pointer; display: flex; align-items: center;" 
                             onclick="selectProduct('${product.id}')">
                            <img src="${product.imagen}" alt="${product.descripcion}" 
                                 style="width: 40px; height: 40px; margin-right: 10px; object-fit: cover; border: 1px solid #ccc; border-radius: 3px;">
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
            console.error("Error en la búsqueda:", error);
        });
}

function selectProduct(productCode) {
    // Puedes implementar lo que suceda al seleccionar un producto
    console.log("Producto seleccionado:", productCode);
}
