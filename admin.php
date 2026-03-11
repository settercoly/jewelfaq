<?php
/**
 * Admin Panel — JewelFAQ
 * URL: /admin.php
 * Acceso protegido por contraseña (definida en config.php).
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/stripe_helper.php';

session_start();

// ── Authentication ────────────────────────────────────────────────────────────
$auth_error = '';

if (isset($_POST['admin_password'])) {
    if (password_verify($_POST['admin_password'], ADMIN_PASSWORD_HASH)) {
        $_SESSION['jewelfaq_admin'] = true;
        header('Location: admin.php');
        exit;
    }
    $auth_error = 'Contraseña incorrecta.';
}

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

$authenticated = !empty($_SESSION['jewelfaq_admin']);

// ── Handle response submission ────────────────────────────────────────────────
$success_msg = '';
$form_error  = '';

if ($authenticated && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['consultation_id'], $_POST['response'])) {
    $cid      = $_POST['consultation_id'];
    $response = trim($_POST['response']);

    if (strlen($response) < 5) {
        $form_error = 'La respuesta es demasiado corta.';
    } else {
        $consultation = get_consultation($cid);
        if ($consultation && $consultation['payment_status'] === 'paid') {
            get_db()->prepare("
                UPDATE consultations
                SET response = ?, response_date = datetime('now')
                WHERE id = ?
            ")->execute([$response, $cid]);

            // Email the client their response link
            $token    = response_token($cid);
            $resp_url = SITE_URL . '/ver-respuesta.php?id=' . $cid . '&t=' . $token;

            $subject = "Tu consulta JewelFAQ tiene respuesta";
            $body    = "Hola {$consultation['name']},\n\n"
                     . "Tu consulta ya tiene respuesta. Puedes verla en el siguiente enlace:\n\n"
                     . $resp_url . "\n\n"
                     . "Este enlace es personal. No lo compartas.\n\n"
                     . "Un saludo,\nColin — JewelFAQ\n";

            $headers = "From: noreply@jewelfaq.com\r\n"
                     . "Reply-To: " . CONTACT_EMAIL . "\r\n"
                     . "Content-Type: text/plain; charset=UTF-8\r\n";

            mail($consultation['email'], $subject, $body, $headers);

            $success_msg = "Respuesta guardada y email enviado a {$consultation['email']}.";
        } else {
            $form_error = 'Consulta no encontrada o no pagada.';
        }
    }
}

// ── Fetch consultations ───────────────────────────────────────────────────────
$pending_consultations  = [];
$answered_consultations = [];

if ($authenticated) {
    $db = get_db();

    $stmt = $db->query("
        SELECT * FROM consultations
        WHERE payment_status = 'paid' AND response IS NULL
        ORDER BY created_at DESC
    ");
    $pending_consultations = $stmt->fetchAll();

    $stmt = $db->query("
        SELECT * FROM consultations
        WHERE payment_status = 'paid' AND response IS NOT NULL
        ORDER BY response_date DESC
        LIMIT 20
    ");
    $answered_consultations = $stmt->fetchAll();
}

$tiers = CONSULTATION_TIERS;

function fmt_amount(int $pence): string
{
    return '£' . number_format($pence / 100, 2);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Admin — JewelFAQ</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body { background: #f0f0f2; }
        .admin-wrap { max-width: 900px; margin: 0 auto; padding: 40px 24px 120px; }
        .admin-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; }
        .card { background: #fff; border-radius: 16px; padding: 28px; margin-bottom: 24px; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
        .badge { display: inline-block; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; padding: 3px 8px; border-radius: 4px; }
        .badge-pending { background: #fef3c7; color: #92400e; }
        .badge-paid    { background: #d1fae5; color: #065f46; }
        .badge-done    { background: #e0e7ff; color: #3730a3; }
        .meta { font-size: 13px; color: #6b7280; margin-top: 6px; }
        .msg-box { background: #f3f3f3; border-radius: 8px; padding: 14px 16px; font-size: 14px; white-space: pre-wrap; word-break: break-word; margin: 12px 0; }
        .response-form textarea { width: 100%; min-height: 200px; padding: 12px; border: 1.5px solid #d1d5db; border-radius: 8px; font-size: 14px; font-family: inherit; resize: vertical; }
        .response-form textarea:focus { outline: none; border-color: #00674F; }
        .login-box { max-width: 400px; margin: 160px auto 0; }
        .login-box input[type=password] { width: 100%; padding: 12px 16px; border: 1.5px solid #d1d5db; border-radius: 8px; font-size: 15px; margin: 12px 0 16px; }
        .section-title { font-size: 18px; font-weight: 700; margin-bottom: 20px; color: #1D1D1F; }
        .empty-state { color: #6b7280; text-align: center; padding: 32px; }
        .success-bar { background: #d1fae5; color: #065f46; padding: 12px 20px; border-radius: 8px; margin-bottom: 24px; font-weight: 600; }
        .error-bar   { background: #fee2e2; color: #991b1b; padding: 12px 20px; border-radius: 8px; margin-bottom: 24px; font-weight: 600; }
        .answered-response { background: #f0fdf4; border: 1px solid #a7f3d0; border-radius: 8px; padding: 14px 16px; font-size: 14px; white-space: pre-wrap; word-break: break-word; margin-top: 12px; }
        summary { cursor: pointer; font-weight: 600; font-size: 14px; color: #374151; }
    </style>
</head>
<body>

<?php if (!$authenticated): ?>

    <!-- ── Login ── -->
    <div class="login-box">
        <div class="card">
            <h1 style="font-size: 24px; margin-bottom: 4px;">JewelFAQ Admin</h1>
            <p style="color: #6b7280; font-size: 14px; margin-bottom: 24px;">Panel de gestión de consultas</p>
            <?php if ($auth_error): ?>
                <div class="error-bar"><?= htmlspecialchars($auth_error) ?></div>
            <?php endif; ?>
            <form method="POST">
                <label style="font-size: 14px; font-weight: 600;">Contraseña</label>
                <input type="password" name="admin_password" autofocus placeholder="••••••••••">
                <button type="submit" class="btn btn-primary" style="width: 100%;">Entrar</button>
            </form>
        </div>
    </div>

<?php else: ?>

    <!-- ── Admin Panel ── -->
    <div class="admin-wrap">

        <div class="admin-header">
            <h1 style="font-size: 22px; font-weight: 700;">Panel Admin — JewelFAQ</h1>
            <a href="admin.php?logout=1" style="font-size: 13px; color: #6b7280;">Cerrar sesión</a>
        </div>

        <?php if ($success_msg): ?>
            <div class="success-bar"><?= htmlspecialchars($success_msg) ?></div>
        <?php endif; ?>
        <?php if ($form_error): ?>
            <div class="error-bar"><?= htmlspecialchars($form_error) ?></div>
        <?php endif; ?>

        <!-- ── Pending consultations ── -->
        <div class="section-title">
            Consultas pendientes de respuesta
            <?php if ($pending_consultations): ?>
                <span class="badge badge-pending"><?= count($pending_consultations) ?></span>
            <?php endif; ?>
        </div>

        <?php if (empty($pending_consultations)): ?>
            <div class="card empty-state">No hay consultas pendientes. 🎉</div>
        <?php endif; ?>

        <?php foreach ($pending_consultations as $c): ?>
            <?php
            $tier_name  = $tiers[$c['tier']]['name'] ?? $c['tier'];
            $token      = response_token($c['id']);
            $resp_url   = SITE_URL . '/ver-respuesta.php?id=' . $c['id'] . '&t=' . $token;
            ?>
            <div class="card">
                <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:8px;">
                    <div>
                        <strong style="font-size: 16px;"><?= htmlspecialchars($c['name']) ?></strong>
                        — <a href="mailto:<?= htmlspecialchars($c['email']) ?>"><?= htmlspecialchars($c['email']) ?></a>
                        <div class="meta">
                            <?= htmlspecialchars($c['form_type']) ?> ·
                            <span class="badge badge-paid"><?= htmlspecialchars($tier_name) ?></span>
                            · <?= fmt_amount($c['amount']) ?>
                            · <?= htmlspecialchars(substr($c['created_at'], 0, 16)) ?>
                        </div>
                    </div>
                </div>

                <div class="msg-box"><?= htmlspecialchars($c['message']) ?></div>

                <form class="response-form" method="POST">
                    <input type="hidden" name="consultation_id" value="<?= htmlspecialchars($c['id']) ?>">
                    <label style="font-size: 13px; font-weight: 600; display:block; margin-bottom: 6px;">
                        Tu respuesta (el cliente la verá en su enlace privado)
                    </label>
                    <textarea name="response" placeholder="Escribe aquí tu análisis técnico completo..."></textarea>
                    <button type="submit" class="btn btn-primary" style="margin-top: 12px;">
                        Guardar respuesta y notificar al cliente
                    </button>
                    <span style="font-size: 12px; color: #6b7280; margin-left: 12px;">
                        Se enviará email a <?= htmlspecialchars($c['email']) ?>
                    </span>
                </form>
            </div>
        <?php endforeach; ?>

        <!-- ── Answered consultations ── -->
        <div class="section-title" style="margin-top: 48px;">
            Últimas 20 consultas respondidas
            <?php if ($answered_consultations): ?>
                <span class="badge badge-done"><?= count($answered_consultations) ?></span>
            <?php endif; ?>
        </div>

        <?php if (empty($answered_consultations)): ?>
            <div class="card empty-state">Todavía no hay consultas respondidas.</div>
        <?php endif; ?>

        <?php foreach ($answered_consultations as $c): ?>
            <?php $tier_name = $tiers[$c['tier']]['name'] ?? $c['tier']; ?>
            <div class="card">
                <details>
                    <summary>
                        <?= htmlspecialchars($c['name']) ?> —
                        <?= htmlspecialchars($tier_name) ?> · <?= fmt_amount($c['amount']) ?> ·
                        Respondido <?= htmlspecialchars(substr($c['response_date'] ?? '', 0, 16)) ?>
                    </summary>
                    <div class="meta" style="margin-top: 12px;">
                        <?= htmlspecialchars($c['email']) ?> · <?= htmlspecialchars($c['form_type']) ?>
                    </div>
                    <p style="font-size: 13px; font-weight: 600; margin: 12px 0 4px;">Consulta:</p>
                    <div class="msg-box"><?= htmlspecialchars($c['message']) ?></div>
                    <p style="font-size: 13px; font-weight: 600; margin: 12px 0 4px;">Respuesta enviada:</p>
                    <div class="answered-response"><?= htmlspecialchars($c['response']) ?></div>
                </details>
            </div>
        <?php endforeach; ?>

    </div>

<?php endif; ?>

</body>
</html>
