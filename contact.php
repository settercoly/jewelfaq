<?php
// contact.php

$to = "ciufuliciboy2@gmail.com";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Función de saneamiento
    function clean_input($data)
    {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }

    $form_type = isset($_POST['form_type']) ? clean_input($_POST['form_type']) : 'General';
    $name = isset($_POST['name']) ? clean_input($_POST['name']) : 'No especificado';
    $email = isset($_POST['email']) ? clean_input($_POST['email']) : 'No especificado';
    $mensaje = isset($_POST['mensaje']) ? clean_input($_POST['mensaje']) : 'Sin mensaje';

    $subject = "Consulta JewelFAQ - [" . strtoupper($form_type) . "] de $name";

    $message_body = "NUEVO CASO DESDE JEWELFAQ.COM\n";
    $message_body .= "======================================\n";
    $message_body .= "ÁREA: " . strtoupper($form_type) . "\n";
    $message_body .= "NOMBRE: $name\n";
    $message_body .= "EMAIL: $email\n";
    $message_body .= "======================================\n\n";
    $message_body .= "MENSAJE:\n$mensaje\n\n";
    $message_body .= "======================================\n";
    $message_body .= "Nota del Sistema: Esta es una consulta generada desde www.jewelfaq.com\n";

    $headers = "From: system@jewelfaq.com\r\n";
    $headers .= "Reply-To: $email\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    if (mail($to, $subject, $message_body, $headers)) {
        $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index.html';
        $referer_clean = strtok($referer, '?');
        header("Location: " . $referer_clean . "?status=success");
        exit;
    }
    else {
        echo "<div style='background-color:#F5F5F7; color:#1D1D1F; font-family: sans-serif; text-align: center; margin-top: 100px; padding: 40px;'>";
        echo "<h2 style='color: #ef4444;'>El entorno local no puede enviar emails</h2>";
        echo "<p>Esto es un aviso de PHP. La orden está bien programada, pero XAMPP/Localhost requiere configuración SMTP externa.</p>";
        echo "<p><strong>Datos capturados:</strong> $form_type de $name ($email)</p>";
        echo "<br><a href='javascript:history.back()' style='color:#00674F; text-decoration: none; font-weight: bold;'>← Volver a la web</a>";
        echo "</div>";
    }
}
else {
    header("Location: index.html");
    exit;
}
?>