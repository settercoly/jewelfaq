<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

$name      = trim($_POST['name'] ?? '');
$phone     = trim($_POST['phone'] ?? '');
$message   = trim($_POST['mensaje'] ?? '');
$form_type = trim($_POST['form_type'] ?? 'general');

if ($name === '' || $phone === '' || $message === '') {
    http_response_code(400);
    exit('Required fields missing.');
}

$id = generate_uuid();

$db = get_db();
$stmt = $db->prepare("
    INSERT INTO consultations (id, name, phone, message, form_type, status, payment_status)
    VALUES (?, ?, ?, ?, ?, 'pending_review', 'pending')
");
$stmt->execute([$id, $name, $phone, $message, $form_type]);

// Send notification email to Colin
$subject = "New JewelFAQ Case: $name — $form_type";
$email_body = "A new consultation request has been submitted:\n\n";
$email_body .= "Name: $name\n";
$email_body .= "WhatsApp: $phone\n";
$email_body .= "Type: $form_type\n";
$email_body .= "Case ID: $id\n\n";
$email_body .= "Message:\n$message\n\n";
$email_body .= "Review and respond in the admin panel: " . SITE_URL . "/admin.php";

$headers = "From: noreply@" . parse_url(SITE_URL, PHP_URL_HOST) . "\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

@mail(CONTACT_EMAIL, $subject, $email_body, $headers);

header('Location: thank-you.html');
exit;
