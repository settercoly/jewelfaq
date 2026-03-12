<?php
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

header('Location: thank-you.html');
exit;
