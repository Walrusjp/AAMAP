<?php
$plainPassword = "admin";
$hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);

echo "Hash: " . $hashedPassword . "<br>";

if (password_verify($plainPassword, $hashedPassword)) {
    echo "Contrase�a verificada correctamente.";
} else {
    echo "La verificaci�n de la contrase�a fall�.";
}
?>
