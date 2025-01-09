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
        $mail->Username = 'papeleria.aamap@gmail.com'; // Tu correo
        $mail->Password = 'ckhndpsabfmpgqve'; // Contraseña o contraseña de aplicación
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;

        // Configuración del correo
        $mail->setFrom('papeleria.aamap@gmail.com', 'PAPELERIA AAMAP');
        $mail->addAddress($to);

        $mail->isHTML(false); // Enviar como texto plano
        $mail->Subject = $subject;
        $mail->Body = $body;

        $mail->send();
        echo "<script>alert('Pedido guardado y correo enviado correctamente.');</script>";
    } catch (Exception $e) {
        echo "<script>alert('Error al enviar el correo: {$mail->ErrorInfo}');</script>";
    }
}
