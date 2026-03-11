<?php
/**
 * Stripe Webhook Endpoint
 *
 * Configure in Stripe Dashboard > Developers > Webhooks:
 *   Endpoint URL : https://TUDOMINIO.com/webhook.php
 *   Events       : checkout.session.completed
 *
 * This is a backup handler — the primary confirmation happens in pago-exito.php.
 * Stripe guarantees delivery of webhook events even if the user closes the tab.
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/stripe_helper.php';

// Read raw request body (must happen before any output)
$payload    = file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

http_response_code(400);

if (!$payload || !$sig_header) {
    echo json_encode(['error' => 'Missing payload or signature']);
    exit;
}

$event = stripe_verify_webhook($payload, $sig_header);

if ($event === false) {
    echo json_encode(['error' => 'Invalid signature']);
    exit;
}

// Handle the event
if ($event['type'] === 'checkout.session.completed') {
    $session = $event['data']['object'];

    if (($session['payment_status'] ?? '') === 'paid') {
        $cid        = $session['metadata']['consultation_id'] ?? '';
        $session_id = $session['id'] ?? '';

        if ($cid) {
            $db           = get_db();
            $consultation = get_consultation($cid);

            if ($consultation && $consultation['payment_status'] !== 'paid') {
                $db->prepare("
                    UPDATE consultations
                    SET payment_status = 'paid', stripe_session_id = ?
                    WHERE id = ?
                ")->execute([$session_id, $cid]);

                // Notify Colin via email
                $tiers     = CONSULTATION_TIERS;
                $tier_name = $tiers[$consultation['tier']]['name'] ?? $consultation['tier'];
                $amount    = '£' . number_format($consultation['amount'] / 100, 2);
                $resp_url  = SITE_URL . '/ver-respuesta.php?id=' . $cid . '&t=' . response_token($cid);

                $subject = "💰 CONSULTA PAGADA (webhook) — {$consultation['name']} [{$tier_name} {$amount}]";
                $body    = "Nueva consulta pagada en JewelFAQ (confirmado vía webhook).\n\n"
                         . "NOMBRE  : {$consultation['name']}\n"
                         . "EMAIL   : {$consultation['email']}\n"
                         . "TIPO    : {$consultation['form_type']}\n"
                         . "NIVEL   : {$tier_name}\n"
                         . "IMPORTE : {$amount}\n\n"
                         . "CONSULTA:\n{$consultation['message']}\n\n"
                         . "Panel de admin:\n" . SITE_URL . "/admin.php\n\n"
                         . "URL de respuesta del cliente:\n" . $resp_url . "\n";

                $headers = "From: noreply@jewelfaq.com\r\n"
                         . "Content-Type: text/plain; charset=UTF-8\r\n";

                mail(CONTACT_EMAIL, $subject, $body, $headers);
            }
        }
    }
}

http_response_code(200);
echo json_encode(['received' => true]);
