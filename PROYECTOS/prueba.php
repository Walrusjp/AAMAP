<?php
// Importar PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// Cargar el autoloader de Composer
require 'vendor/autoload.php';

// Función para enviar correos
function send_email($to, $subject, $body) {
    $mail = new PHPMailer(true); // Habilitar excepciones

    try {
        // Configuración del servidor SMTP
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; // Servidor SMTP de Google
        $mail->SMTPAuth = true;
        $mail->Username = 'aamapcop@gmail.com'; // Tu correo de Google Workspace
        $mail->Password = 'uqxgtjehyfjgqxad'; // Tu contraseña normal (sin 2FA)
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Usar SSL
        $mail->Port = 587; // Puerto para SSL

        // Configuración del correo
        $mail->setFrom('aamapcop@gmail.com', 'Proyecto en proceso'); // Correo y nombre del remitente
        $mail->addAddress($to); // Correo del destinatario
        $mail->isHTML(true); // Enviar como HTML
        $mail->Subject = $subject; // Asunto del correo
        $mail->Body = $body; // Cuerpo del correo en HTML
        $mail->AltBody = strip_tags($body); // Cuerpo alternativo en texto plano

        // Enviar el correo
        $mail->send();
        echo "<script>alert('Correo enviado correctamente.');</script>";
    } catch (Exception $e) {
        echo "<script>alert('Error al enviar el correo: {$mail->ErrorInfo}');</script>";
    }
}

// Datos para el correo de prueba
$to = 'sistemas@aamap.net'; // Correo del destinatario
$subject = 'Prueba de envío de correo'; // Asunto del correo
$body = '
    <h1>¡Hola!</h1>
    <p>Este es un correo de prueba enviado desde <strong>PHPMailer</strong>.</p>
    <p>Fecha y hora: ' . date('Y-m-d H:i:s') . '</p>
';

// Enviar el correo
send_email($to, $subject, $body);