<?php
/**
 * Response Viewer — JewelFAQ
 * URL: /ver-respuesta.php?id=UUID&t=TOKEN
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
        // Verify token and payment
        if (!hash_equals(response_token($id), $token)) {
            $error = 'Enlace no válido.';
            $c     = null;
        } elseif ($c['payment_status'] !== 'paid') {
            $error = 'Esta consulta no ha sido pagada.';
            $c     = null;
        } else {
            $valid = true;
        }
    } else {
        $error = 'Consulta no encontrada.';
    }
} else {
    $error = 'Enlace incompleto.';
}

$tiers     = CONSULTATION_TIERS;
$tier_name = $c ? ($tiers[$c['tier']]['name'] ?? $c['tier']) : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tu respuesta — JewelFAQ</title>
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

            <!-- ── Invalid / unpaid ── -->
            <div style="text-align: center;">
                <div style="font-size: 64px; margin-bottom: 24px; line-height: 1;">🔒</div>
                <h1 style="margin-bottom: 16px;">Acceso no disponible</h1>
                <p class="lead" style="margin-bottom: 32px;">
                    <?= htmlspecialchars($error) ?>
                </p>
                <a href="index.html" class="btn btn-secondary">← Volver al inicio</a>
            </div>

        <?php elseif ($c['response'] === null): ?>

            <!-- ── Paid, response pending ── -->
            <div style="text-align: center;">
                <div class="pending-icon">⏳</div>
                <h1 style="margin-bottom: 16px;">Respuesta en preparación</h1>
                <p class="lead" style="margin-bottom: 16px;">
                    Hola <strong><?= htmlspecialchars($c['name']) ?></strong>, tu pago ha sido confirmado.
                </p>
                <p style="color: var(--text-muted); margin-bottom: 40px;">
                    Estoy trabajando en tu respuesta. La recibirás en menos de
                    <strong>24 horas</strong> desde que realizaste el pago.<br>
                    También recibirás un email en
                    <strong><?= htmlspecialchars($c['email']) ?></strong> cuando esté lista.
                </p>

                <div class="bento-card" style="text-align: left; margin-bottom: 32px;">
                    <h3 style="margin-bottom: 16px; font-size: 16px;">Tu consulta enviada</h3>
                    <div style="font-size: 13px; margin-bottom: 12px;">
                        <span class="meta-pill"><?= htmlspecialchars($c['form_type']) ?></span>
                        <span class="meta-pill"><?= htmlspecialchars($tier_name) ?></span>
                        <span class="meta-pill"><?= htmlspecialchars(substr($c['created_at'], 0, 16)) ?></span>
                    </div>
                    <div style="background: #f9fafb; border-radius: 8px; padding: 16px; font-size: 14px; color: #374151; white-space: pre-wrap; word-break: break-word;">
                        <?= htmlspecialchars($c['message']) ?>
                    </div>
                </div>

                <p style="font-size: 13px; color: var(--text-muted);">
                    Guarda esta página en marcadores. URL válida solo para ti.
                </p>
            </div>

        <?php else: ?>

            <!-- ── Response available ── -->
            <div style="margin-bottom: 32px;">
                <h1 style="margin-bottom: 8px;">Tu respuesta está lista</h1>
                <p style="color: var(--text-muted); font-size: 14px; margin-bottom: 16px;">
                    Hola <strong><?= htmlspecialchars($c['name']) ?></strong> —
                    análisis realizado por Colin, joyero profesional.
                </p>
                <div>
                    <span class="meta-pill"><?= htmlspecialchars($c['form_type']) ?></span>
                    <span class="meta-pill"><?= htmlspecialchars($tier_name) ?></span>
                    <span class="meta-pill">Respondido <?= htmlspecialchars(substr($c['response_date'] ?? '', 0, 16)) ?></span>
                </div>
            </div>

            <div style="margin-bottom: 32px;">
                <h2 style="font-size: 15px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: .5px; margin-bottom: 12px;">
                    Tu consulta
                </h2>
                <div style="background: #f9fafb; border-radius: 10px; padding: 16px; font-size: 14px; color: #374151; white-space: pre-wrap; word-break: break-word;">
                    <?= htmlspecialchars($c['message']) ?>
                </div>
            </div>

            <div style="margin-bottom: 48px;">
                <h2 style="font-size: 15px; font-weight: 700; color: var(--accent-digital); text-transform: uppercase; letter-spacing: .5px; margin-bottom: 12px;">
                    Respuesta de Colin
                </h2>
                <div class="response-body">
                    <?= htmlspecialchars($c['response']) ?>
                </div>
            </div>

            <hr style="border: none; border-top: 1px solid var(--border-subtle); margin-bottom: 32px;">

            <p style="font-size: 13px; color: var(--text-muted); text-align: center; margin-bottom: 24px;">
                Esta respuesta es personal y confidencial. No ha sido generada por IA.<br>
                Si necesitas aclaraciones puedes escribir a
                <a href="mailto:<?= CONTACT_EMAIL ?>"><?= CONTACT_EMAIL ?></a>.
            </p>
            <div style="text-align: center;">
                <a href="index.html" class="btn btn-secondary">← Volver al inicio</a>
            </div>

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
