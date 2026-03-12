<?php
/**
 * Response Viewer — JewelFAQ
 * URL: /view-response.php?id=UUID&t=TOKEN
 *
 * Only accessible with a valid token derived from the consultation ID + secret salt.
 * Shows the response when available, or a "pending" message if not yet answered.
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

$id    = $_GET['id'] ?? '';
$token = $_GET['t']  ?? '';
$c     = null;
$valid = false;
$error = '';

if ($id && $token) {
    $c = get_consultation($id);
    if ($c) {
        if (!hash_equals(response_token($id), $token)) {
            $error = 'Invalid link.';
            $c     = null;
        } elseif (!in_array($c['status'] ?? '', ['paid', 'answered'], true)) {
            $error = 'This consultation has not been paid yet.';
            $c     = null;
        } else {
            $valid = true;
        }
    } else {
        $error = 'Consultation not found.';
    }
} else {
    $error = 'Incomplete link.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your response — JewelFAQ</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .response-body {
            background: var(--bg-body);
            border: 1.5px solid var(--border-subtle);
            border-radius: 12px;
            padding: 28px;
            font-size: 15px;
            line-height: 1.8;
            white-space: pre-wrap;
            word-break: break-word;
        }
        .pending-icon { font-size: 64px; margin-bottom: 24px; line-height: 1; }
        .meta-pill {
            display: inline-block;
            background: #f3f4f6;
            border-radius: 20px;
            padding: 4px 12px;
            font-size: 12px;
            color: #6b7280;
            margin-right: 8px;
            margin-bottom: 8px;
        }
    </style>
</head>
<body>

<header class="site-header">
    <div class="container header-container">
        <a href="index.html" class="logo">JewelFAQ</a>
    </div>
</header>

<main style="padding-top: 140px; padding-bottom: 120px;">
    <div class="container" style="max-width: 720px; margin: 0 auto;">

        <?php if (!$valid): ?>

            <div style="text-align: center;">
                <div style="font-size: 64px; margin-bottom: 24px; line-height: 1;">&#128274;</div>
                <h1 style="margin-bottom: 16px;">Access unavailable</h1>
                <p class="lead" style="margin-bottom: 32px;">
                    <?= htmlspecialchars($error) ?>
                </p>
                <a href="index.html" class="btn btn-secondary">&larr; Back to homepage</a>
            </div>

        <?php elseif ($c['response'] === null): ?>

            <div style="text-align: center;">
                <div class="pending-icon">&#9203;</div>
                <h1 style="margin-bottom: 16px;">Response being prepared</h1>
                <p class="lead" style="margin-bottom: 16px;">
                    Hi <strong><?= htmlspecialchars($c['name']) ?></strong>, your payment has been confirmed.
                </p>
                <p style="color: var(--text-muted); margin-bottom: 40px;">
                    Colin is working on your response. It will be ready within
                    <strong>24 hours</strong> of payment.<br>
                    Bookmark this page — your response will appear here.
                </p>

                <div class="bento-card" style="text-align: left; margin-bottom: 32px;">
                    <h3 style="margin-bottom: 16px; font-size: 16px;">Your submitted case</h3>
                    <div style="font-size: 13px; margin-bottom: 12px;">
                        <span class="meta-pill"><?= htmlspecialchars($c['form_type']) ?></span>
                        <span class="meta-pill"><?= htmlspecialchars(substr($c['created_at'], 0, 16)) ?></span>
                    </div>
                    <div style="background: #f9fafb; border-radius: 8px; padding: 16px; font-size: 14px; color: #374151; white-space: pre-wrap; word-break: break-word;">
                        <?= htmlspecialchars($c['message']) ?>
                    </div>
                </div>

                <p style="font-size: 13px; color: var(--text-muted);">
                    Bookmark this page. This URL is valid only for you.
                </p>
            </div>

        <?php else: ?>

            <div style="margin-bottom: 32px;">
                <h1 style="margin-bottom: 8px;">Your response is ready</h1>
                <p style="color: var(--text-muted); font-size: 14px; margin-bottom: 16px;">
                    Hi <strong><?= htmlspecialchars($c['name']) ?></strong> —
                    analysis by Colin, professional bench jeweller.
                </p>
                <div>
                    <span class="meta-pill"><?= htmlspecialchars($c['form_type']) ?></span>
                    <span class="meta-pill">Answered <?= htmlspecialchars(substr($c['response_date'] ?? '', 0, 16)) ?></span>
                </div>
            </div>

            <div style="margin-bottom: 32px;">
                <h2 style="font-size: 15px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: .5px; margin-bottom: 12px;">
                    Your case
                </h2>
                <div style="background: #f9fafb; border-radius: 10px; padding: 16px; font-size: 14px; color: #374151; white-space: pre-wrap; word-break: break-word;">
                    <?= htmlspecialchars($c['message']) ?>
                </div>
            </div>

            <div style="margin-bottom: 48px;">
                <h2 style="font-size: 15px; font-weight: 700; color: var(--accent-digital); text-transform: uppercase; letter-spacing: .5px; margin-bottom: 12px;">
                    Colin's response
                </h2>
                <div class="response-body">
                    <?= htmlspecialchars($c['response']) ?>
                </div>
            </div>

            <hr style="border: none; border-top: 1px solid var(--border-subtle); margin-bottom: 32px;">

            <p style="font-size: 13px; color: var(--text-muted); text-align: center; margin-bottom: 24px;">
                This response is personal and confidential. It has not been generated by AI.<br>
                If you have questions, contact Colin via WhatsApp: +44 74 1507 2425
            </p>
            <div style="text-align: center;">
                <a href="index.html" class="btn btn-secondary">&larr; Back to homepage</a>
            </div>

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
