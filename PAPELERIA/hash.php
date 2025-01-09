<?php
$plainPassword = "admin";
$hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);

echo "Hash: " . $hashedPassword . "<br>";

if (password_verify($plainPassword, $hashedPassword)) {
    echo "Contraseña verificada correctamente.";
} else {
    echo "La verificación de la contraseña falló.";
}
?>
