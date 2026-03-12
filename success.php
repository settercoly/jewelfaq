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
        $cid          = $result['data']['metadata']['consultation_id'] ?? '';
        $consultation = get_consultation($cid);

        if ($consultation && $consultation['payment_status'] !== 'paid') {
            get_db()->prepare("
                UPDATE consultations
                SET payment_status = 'paid',
                    status = 'paid',
                    stripe_session_id = ?
                WHERE id = ?
            ")->execute([$session_id, $cid]);
            $consultation['payment_status'] = 'paid';
            $consultation['status']         = 'paid';
        }
    } elseif (!$result['ok']) {
        $error = 'Could not verify payment with Stripe.';
    } elseif (($result['data']['payment_status'] ?? '') !== 'paid') {
        $error = 'Payment has not been completed yet.';
    }
} else {
    $error = 'Missing payment session identifier.';
}

$token        = $consultation ? response_token($consultation['id']) : '';
$response_url = $consultation
    ? SITE_URL . '/view-response.php?id=' . $consultation['id'] . '&t=' . $token
    : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment confirmed — JewelFAQ</title>
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

            <div class="icon-big">&#9989;</div>
            <h1 style="margin-bottom: 16px;">Payment confirmed</h1>
            <p class="lead" style="margin-bottom: 40px;">
                Hi <strong><?= htmlspecialchars($consultation['name']) ?></strong>,
                your payment has been received.<br>
                You will receive Colin's written response within <strong>24 hours</strong>.
            </p>

            <div class="bento-card" style="text-align: left; margin-bottom: 32px;">
                <h3 style="margin-bottom: 8px;">Save your response link</h3>
                <p style="color: var(--text-muted); font-size: 14px; margin-bottom: 16px;">
                    When your response is ready it will appear at this URL. Bookmark it now.
                </p>
                <div class="url-box"><?= htmlspecialchars($response_url) ?></div>
                <a href="<?= htmlspecialchars($response_url) ?>"
                   class="btn btn-primary"
                   style="display: block; text-align: center; width: 100%; margin-top: 16px; box-sizing: border-box;">
                    View my response
                </a>
            </div>

            <a href="index.html" style="color: var(--text-muted); font-size: 14px;">
                &larr; Back to homepage
            </a>

        <?php else: ?>

            <div class="icon-big">&#9888;&#65039;</div>
            <h1 style="margin-bottom: 16px;">Could not verify payment</h1>
            <p class="lead" style="margin-bottom: 32px;">
                <?= htmlspecialchars($error) ?>
            </p>
            <p style="color: var(--text-muted); margin-bottom: 32px;">
                If you were charged and this error persists, please contact Colin via WhatsApp:
                <a href="tel:+447415072425">+44 74 1507 2425</a>
            </p>
            <a href="index.html" class="btn btn-secondary">&larr; Back to homepage</a>

        <?php endif; ?>

    </div>
</main>

<footer class="site-footer">
    <div class="container footer-bottom">
        &copy; JewelFAQ&#8482; &mdash; Trademark of The Jewellery Crafters. Birmingham, UK.
    </div>
</footer>

<script src="assets/js/script.js"></script>
</body>
</html>
