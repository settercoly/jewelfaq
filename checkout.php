<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/stripe_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.html');
    exit;
}

// ── Sanitise input ───────────────────────────────────────────────────────────
function clean(string $v): string
{
    return htmlspecialchars(trim(stripslashes($v)));
}

$name      = clean($_POST['name']      ?? '');
$email     = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
$message   = clean($_POST['mensaje']   ?? '');
$form_type = clean($_POST['form_type'] ?? 'general');
$tier_key  = clean($_POST['tier']      ?? 'estandar');

// Validate
$errors = [];
if (strlen($name) < 2)                          $errors[] = 'Name is required.';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
if (strlen($message) < 10)                      $errors[] = 'Please describe your case (minimum 10 characters).';
if (!array_key_exists($tier_key, CONSULTATION_TIERS)) $tier_key = 'estandar';

if ($errors) {
    $ref = $_SERVER['HTTP_REFERER'] ?? 'index.html';
    $ref = strtok($ref, '?');
    header('Location: ' . $ref . '?error=' . urlencode(implode(' ', $errors)));
    exit;
}

// ── Store pending consultation in DB ─────────────────────────────────────────
$db   = get_db();
$id   = generate_uuid();
$tier = CONSULTATION_TIERS[$tier_key];

$db->prepare("
    INSERT INTO consultations (id, name, email, message, form_type, tier, amount, payment_status)
    VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')
")->execute([$id, $name, $email, $message, $form_type, $tier_key, $tier['amount']]);

// ── Create Stripe Checkout Session ───────────────────────────────────────────
$result = stripe_create_checkout($id, $tier_key, $email, $name);

if (!$result['ok'] || empty($result['data']['url'])) {
    // Stripe call failed — clean up and show error
    $db->prepare("DELETE FROM consultations WHERE id = ?")->execute([$id]);
    $ref = $_SERVER['HTTP_REFERER'] ?? 'index.html';
    $ref = strtok($ref, '?');
    $err = urlencode('Payment system error. Please try again.');
    header("Location: $ref?error=$err");
    exit;
}

// Save the Stripe session ID
$db->prepare("UPDATE consultations SET stripe_session_id = ? WHERE id = ?")
   ->execute([$result['data']['id'], $id]);

// ── Redirect user to Stripe Checkout ─────────────────────────────────────────
header('Location: ' . $result['data']['url']);
exit;
