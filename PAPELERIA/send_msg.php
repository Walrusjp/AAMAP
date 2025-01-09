<?php
// Asegúrate de que el archivo autoload.php esté en la ruta correcta
require_once 'C:/xampp/htdocs/PAPELERIA/vendor/autoload.php'; // Cambia la ruta según corresponda
use Twilio\Rest\Client;

// Tus credenciales de Twilio
$sid    = "AC652b1ed2b45515730b77413ee1bd279c"; // Reemplaza con tu Account SID
$token  = "aced3d82be8f8d62f96bea93fb4f288a"; // Reemplaza con tu Auth Token

// Crear el cliente de Twilio
$twilio = new Client($sid, $token);

// Enviar el mensaje de WhatsApp
$message = $twilio->messages
    ->create(
        "whatsapp:+5217831010939", // Número de destino (en este caso, un número de WhatsApp válido)
        [
            "from" => "whatsapp:+14155238886", // Número de Twilio en el sandbox
            "body" => "¡Hola! Este es un mensaje de prueba desde Twilio."
        ]
    );

// Imprimir el SID del mensaje para verificar que fue enviado correctamente
print($message->sid);
?>
