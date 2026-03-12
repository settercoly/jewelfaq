<?php
/**
 * Stripe Webhook Endpoint
 *
 * Configure in Stripe Dashboard > Developers > Webhooks:
 *   Endpoint URL : https://yourdomain.com/webhook.php
 *   Events       : checkout.session.completed
 *
 * Stripe guarantees delivery even if the user closes the browser tab.
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/stripe_helper.php';

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
                    SET payment_status = 'paid',
                        status = 'paid',
                        stripe_session_id = ?
                    WHERE id = ?
                ")->execute([$session_id, $cid]);
            }
        }
    }
}

http_response_code(200);
echo json_encode(['received' => true]);
