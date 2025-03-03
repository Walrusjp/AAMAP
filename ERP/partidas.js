let partidas = [];

function addPartida() {
    const nombre = $('#nombre').val().trim();
    const mac = $('#mac').is(':checked');
    const man = $('#man').is(':checked');
    const com = $('#com').is(':checked');

    if (!nombre) {
        alert('El nombre de la partida es obligatorio.');
        return;
    }

    partidas.push({ nombre, mac, man, com });
    updatePartidasTable();
    $('#nombre').val('');
    $('#mac').prop('checked', false);
    $('#man').prop('checked', false);
    $('#com').prop('checked', false);
}

function updatePartidasTable() {
    const tbody = $('#partidasTable tbody');
    tbody.empty();
    partidas.forEach((partida, index) => {
        tbody.append(`<tr>
            <td>${partida.nombre.replace(/"/g, '&quot;').replace(/'/g, '&#39;')}</td>
            <td>${partida.mac ? '✔' : ''}</td>
            <td>${partida.man ? '✔' : ''}</td>
            <td>${partida.com ? '✔' : ''}</td>
            <td><button class="btn btn-danger btn-sm" onclick="removePartida(${index})">Eliminar</button></td>
        </tr>`);
    });
    $('#partidasInput').val(JSON.stringify(partidas));
}

function removePartida(index) {
    partidas.splice(index, 1);
    updatePartidasTable();
}
