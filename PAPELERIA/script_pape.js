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