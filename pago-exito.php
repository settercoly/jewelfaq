<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/stripe_helper.php';

$session_id   = $_GET['session_id'] ?? '';
$consultation = null;
$error        = '';

if ($session_id) {
    $result = stripe_get_session($session_id);

    if ($result['ok'] && ($result['data']['payment_status'] ?? '') === 'paid') {
        $cid = $result['data']['metadata']['consultation_id'] ?? '';
        $consultation = get_consultation($cid);

        if ($consultation && $consultation['payment_status'] !== 'paid') {
            // Mark as paid
            get_db()->prepare("
                UPDATE consultations
                SET payment_status = 'paid', stripe_session_id = ?
                WHERE id = ?
            ")->execute([$session_id, $cid]);
            $consultation['payment_status'] = 'paid';

            // Email notification to Colin
            $tiers      = CONSULTATION_TIERS;
            $tier_name  = $tiers[$consultation['tier']]['name'] ?? $consultation['tier'];
            $amount_str = '£' . number_format($consultation['amount'] / 100, 2);
            $resp_url   = SITE_URL . '/ver-respuesta.php?id=' . $cid . '&t=' . response_token($cid);

            $subject = "💰 CONSULTA PAGADA — {$consultation['name']} [{$tier_name} {$amount_str}]";
            $body    = "Nueva consulta pagada en JewelFAQ.\n\n"
                     . "NOMBRE  : {$consultation['name']}\n"
                     . "EMAIL   : {$consultation['email']}\n"
                     . "TIPO    : {$consultation['form_type']}\n"
                     . "NIVEL   : {$tier_name}\n"
                     . "IMPORTE : {$amount_str}\n\n"
                     . "CONSULTA:\n{$consultation['message']}\n\n"
                     . "Para responder entra en el panel de admin:\n"
                     . SITE_URL . "/admin.php\n\n"
                     . "El cliente verá la respuesta en:\n"
                     . $resp_url . "\n";

            $headers = "From: noreply@jewelfaq.com\r\n"
                     . "Reply-To: {$consultation['email']}\r\n"
                     . "Content-Type: text/plain; charset=UTF-8\r\n";

            mail(CONTACT_EMAIL, $subject, $body, $headers);
        }
    } elseif (!$result['ok']) {
        $error = 'No se pudo verificar el pago con Stripe.';
    } elseif (($result['data']['payment_status'] ?? '') !== 'paid') {
        $error = 'El pago no ha sido completado todavía.';
    }
} else {
    $error = 'Falta el identificador de sesión de pago.';
}

$token        = $consultation ? response_token($consultation['id']) : '';
$response_url = $consultation
    ? SITE_URL . '/ver-respuesta.php?id=' . $consultation['id'] . '&t=' . $token
    : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pago confirmado — JewelFAQ</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .icon-big { font-size: 72px; margin-bottom: 24px; line-height: 1; }
        .url-box {
            background: var(--bg-body);
            border: 1px solid var(--border-subtle);
            border-radius: 8px;
            padding: 12px 16px;
            word-break: break-all;
            font-family: monospace;
            font-size: 13px;
            color: var(--text-muted);
            user-select: all;
        }
    </style>
</head>
<body>

<header class="site-header">
    <div class="container header-container">
        <a href="index.html" class="logo">JewelFAQ</a>
    </div>
</header>

<main style="padding-top: 160px; padding-bottom: 120px;">
    <div class="container" style="max-width: 640px; margin: 0 auto; text-align: center;">

        <?php if ($consultation): ?>

            <div class="icon-big">✅</div>
            <h1 style="margin-bottom: 16px;">Pago confirmado</h1>
            <p class="lead" style="margin-bottom: 40px;">
                Hola <strong><?= htmlspecialchars($consultation['name']) ?></strong>,
                hemos recibido tu consulta y tu pago.<br>
                Recibirás respuesta en menos de <strong>24 horas</strong>.
            </p>

            <div class="bento-card" style="text-align: left; margin-bottom: 32px;">
                <h3 style="margin-bottom: 8px;">Guarda tu enlace de respuesta</h3>
                <p style="color: var(--text-muted); font-size: 14px; margin-bottom: 16px;">
                    Cuando la respuesta esté lista la verás en esta URL.
                    Cópiala o añádela a marcadores ahora.
                </p>
                <div class="url-box"><?= htmlspecialchars($response_url) ?></div>
                <a href="<?= htmlspecialchars($response_url) ?>"
                   class="btn btn-primary"
                   style="display: block; text-align: center; width: 100%; margin-top: 16px;">
                    Ver mi respuesta
                </a>
                <p style="color: var(--text-muted); font-size: 12px; margin-top: 12px; text-align: center;">
                    También recibirás un email en
                    <strong><?= htmlspecialchars($consultation['email']) ?></strong>
                    cuando esté disponible.
                </p>
            </div>

            <a href="index.html" style="color: var(--text-muted); font-size: 14px;">
                ← Volver al inicio
            </a>

        <?php else: ?>

            <div class="icon-big">⚠️</div>
            <h1 style="margin-bottom: 16px;">No se pudo verificar el pago</h1>
            <p class="lead" style="margin-bottom: 32px;">
                <?= htmlspecialchars($error) ?>
            </p>
            <p style="color: var(--text-muted); margin-bottom: 32px;">
                Si has sido cobrado y este error persiste, escríbenos a
                <a href="mailto:<?= CONTACT_EMAIL ?>"><?= CONTACT_EMAIL ?></a>
                con el asunto "Verificación de pago" e indicando tu email.
            </p>
            <a href="index.html" class="btn btn-secondary">← Volver al inicio</a>

        <?php endif; ?>

    </div>
</main>

<footer class="site-footer">
    <div class="container footer-bottom">
        &copy; JewelFAQ™ — The Jewellery Crafters. Birmingham, UK.
    </div>
</footer>

<script src="assets/js/script.js"></script>
</body>
</html>
