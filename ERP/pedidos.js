let pedidos = [];

function addPedido() {
    const articulo = $('#articulo').val().trim();
    const cantidad = parseInt($('#cantidad').val(), 10);
    const precio = parseFloat($('#precio').val());

    if (!articulo || isNaN(cantidad) || isNaN(precio)) {
        alert('Todos los campos son obligatorios y deben tener valores válidos.');
        return;
    }

    pedidos.push({ articulo, cantidad, precio });
    updatePedidosTable();
    $('#articulo').val('');
    $('#cantidad').val('');
    $('#precio').val('');
}

function updatePedidosTable() {
    const tbody = $('#pedidosTable tbody');
    tbody.empty();
    pedidos.forEach((pedido, index) => {
        tbody.append(`<tr>
            <td>${pedido.articulo.replace(/"/g, '&quot;').replace(/'/g, '&#39;')}</td>
            <td>${pedido.cantidad}</td>
            <td>${pedido.precio}</td>
            <td><button class="btn btn-danger btn-sm" onclick="removePedido(${index})">Eliminar</button></td>
        </tr>`);
    });
    $('#pedidosInput').val(JSON.stringify(pedidos));
}

function removePedido(index) {
    pedidos.splice(index, 1);
    updatePedidosTable();
}
