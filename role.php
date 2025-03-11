<?php
if (isset($_SESSION['username'])) {
    $username = $_SESSION['username'];

    // Modifica la consulta para incluir nombre y apellido
    $query = $conn->prepare("SELECT role, nombre, apellido FROM users WHERE username = ?");
    $query->bind_param("s", $username);
    $query->execute();
    $result = $query->get_result();
    $user = $result->fetch_assoc();

    if ($user) {
        $role = $user['role']; // Obtener el rol
        $nombre = $user['nombre']; // Obtener el nombre
        $apellido = $user['apellido']; // Obtener el apellido

        // Ahora puedes usar $role, $nombre y $apellido en tu lógica
       // echo "Rol: " . $role . "<br>";
        //echo "Nombre: " . $nombre . "<br>";
        //echo "Apellido: " . $apellido . "<br>";
    } else {
        // Manejar el caso en que el usuario no existe
        echo "Usuario no encontrado.";
    }
} else {
    // Manejar el caso en que no hay sesión iniciada
    echo "No has iniciado sesión.";
}
?>