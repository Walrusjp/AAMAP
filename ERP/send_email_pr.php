<?php
// send_email.php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'vendor/autoload.php'; // Asegúrate de que PHPMailer está instalado

function send_email_order($to, $subject, $body) {
    $mail = new PHPMailer(true);

    try {
        // Configuración del servidor SMTP
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'aamapcop@gmail.com'; // Tu correo
        $mail->Password = 'uqxgtjehyfjgqxad'; // Contraseña o contraseña de aplicación
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Configuración del correo
        $mail->setFrom('aamapcop@gmail.com', 'INICIO DE PROYECTO');
        $mail->addAddress($to);

        $mail->isHTML(true); // Enviar como texto plano
        $mail->Subject = $subject;
        $mail->Body = $body;

        // Evitar que Gmail marque el correo como spam
        $mail->addCustomHeader('X-Mailer', 'PHP/' . phpversion()); 
        $mail->addCustomHeader('Precedence', 'bulk'); 

        $mail->send();
    } catch (Exception $e) {
        echo "<script>alert('Error al enviar el correo: {$mail->ErrorInfo}');</script>";
    }
}

?>