<?php
/**
 * Admin Panel — JewelFAQ
 * URL: /admin.php
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
    $auth_error = 'Incorrect password.';
}

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

$authenticated = !empty($_SESSION['jewelfaq_admin']);

// ── Handle actions ────────────────────────────────────────────────────────────
$action_msg   = '';
$action_error = '';

if ($authenticated && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $db     = get_db();
    $action = $_POST['action'] ?? '';

    if ($action === 'accept') {
        $cid = $_POST['consultation_id'] ?? '';
        $c   = get_consultation($cid);
        if ($c && $c['status'] === 'pending_review') {
            $link = generate_payment_link($cid);
            $db->prepare("
                UPDATE consultations
                SET status = 'awaiting_payment',
                    stripe_session_id = ?,
                    payment_link = ?,
                    tier = 'estandar',
                    amount = 5000
                WHERE id = ?
            ")->execute([$link['session_id'], $link['url'], $cid]);
            header('Location: admin.php?tab=awaiting_payment&flash=accepted');
            exit;
        }
        $action_error = 'Case not found or already processed.';

    } elseif ($action === 'reject') {
        $cid = $_POST['consultation_id'] ?? '';
        $c   = get_consultation($cid);
        if ($c && $c['status'] === 'pending_review') {
            $db->prepare("UPDATE consultations SET status = 'rejected' WHERE id = ?")->execute([$cid]);
            header('Location: admin.php?tab=rejected&flash=rejected');
            exit;
        }
        $action_error = 'Case not found or already processed.';

    } elseif ($action === 'respond') {
        $cid      = $_POST['consultation_id'] ?? '';
        $response = trim($_POST['response'] ?? '');
        if (strlen($response) < 5) {
            $action_error = 'Response is too short.';
        } else {
            $c = get_consultation($cid);
            if ($c && $c['status'] === 'paid') {
                $db->prepare("
                    UPDATE consultations
                    SET response = ?, response_date = datetime('now'), status = 'answered'
                    WHERE id = ?
                ")->execute([$response, $cid]);
                header('Location: admin.php?tab=answered&flash=responded');
                exit;
            }
            $action_error = 'Case not found or not in paid status.';
        }
    }
}

// ── Fetch consultations ───────────────────────────────────────────────────────
$rows = [];
if ($authenticated) {
    $db   = get_db();
    $stmt = $db->query("SELECT * FROM consultations ORDER BY created_at DESC");
    $all  = $stmt->fetchAll();
    foreach ($all as $c) {
        $rows[$c['status']][] = $c;
    }
}

$tab = $_GET['tab'] ?? 'pending_review';
$flash = $_GET['flash'] ?? '';

function wa_link(string $phone, string $text): string
{
    $digits = preg_replace('/\D/', '', $phone);
    if (!str_starts_with($digits, '44') && !str_starts_with($digits, '1')) {
        $digits = '44' . ltrim($digits, '0');
    }
    return 'https://wa.me/' . $digits . '?text=' . rawurlencode($text);
}

function count_tab(array $rows, string $key): int
{
    return count($rows[$key] ?? []);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel — JewelFAQ</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body { background: #f0f0f2; }
        .admin-wrap { max-width: 960px; margin: 0 auto; padding: 40px 24px 120px; }
        .admin-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px; }
        .card { background: #fff; border-radius: 16px; padding: 28px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
        .badge { display: inline-block; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; padding: 3px 8px; border-radius: 4px; }
        .badge-new      { background: #fef3c7; color: #92400e; }
        .badge-waiting  { background: #dbeafe; color: #1e40af; }
        .badge-paid     { background: #d1fae5; color: #065f46; }
        .badge-answered { background: #e0e7ff; color: #3730a3; }
        .badge-rejected { background: #fee2e2; color: #991b1b; }
        .meta { font-size: 13px; color: #6b7280; margin-top: 6px; line-height: 1.6; }
        .msg-box { background: #f3f3f3; border-radius: 8px; padding: 14px 16px; font-size: 14px; white-space: pre-wrap; word-break: break-word; margin: 12px 0; }
        .link-box { background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 8px; padding: 12px 16px; font-size: 13px; font-family: monospace; word-break: break-all; margin: 12px 0; }
        .response-form textarea { width: 100%; min-height: 200px; padding: 12px; border: 1.5px solid #d1d5db; border-radius: 8px; font-size: 14px; font-family: inherit; resize: vertical; box-sizing: border-box; }
        .response-form textarea:focus { outline: none; border-color: #00674F; }
        .login-box { max-width: 400px; margin: 160px auto 0; }
        .login-box input[type=password] { width: 100%; padding: 12px 16px; border: 1.5px solid #d1d5db; border-radius: 8px; font-size: 15px; margin: 12px 0 16px; box-sizing: border-box; }
        .section-title { font-size: 18px; font-weight: 700; margin: 0 0 20px; color: #1D1D1F; display: flex; align-items: center; gap: 10px; }
        .empty-state { color: #6b7280; text-align: center; padding: 32px; }
        .flash-ok  { background: #d1fae5; color: #065f46; padding: 12px 20px; border-radius: 8px; margin-bottom: 24px; font-weight: 600; }
        .flash-err { background: #fee2e2; color: #991b1b; padding: 12px 20px; border-radius: 8px; margin-bottom: 24px; font-weight: 600; }
        .answered-response { background: #f0fdf4; border: 1px solid #a7f3d0; border-radius: 8px; padding: 14px 16px; font-size: 14px; white-space: pre-wrap; word-break: break-word; margin-top: 12px; }
        summary { cursor: pointer; font-weight: 600; font-size: 14px; color: #374151; }
        .tabs { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 32px; }
        .tab-btn { padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 600; text-decoration: none; border: none; cursor: pointer; transition: background .15s; }
        .tab-btn.active { background: #1D1D1F; color: #FFF; }
        .tab-btn:not(.active) { background: #fff; color: #374151; box-shadow: 0 1px 3px rgba(0,0,0,.12); }
        .btn-sm { padding: 6px 14px; font-size: 13px; }
        .btn-accept { background: #00674F; color: #fff; border: none; border-radius: 8px; padding: 8px 16px; font-size: 13px; font-weight: 600; cursor: pointer; }
        .btn-accept:hover { background: #005540; }
        .btn-reject { background: #fff; color: #dc2626; border: 1.5px solid #fca5a5; border-radius: 8px; padding: 8px 16px; font-size: 13px; font-weight: 600; cursor: pointer; }
        .btn-reject:hover { background: #fff5f5; }
        .btn-wa { background: #25D366; color: #fff; border: none; border-radius: 8px; padding: 8px 16px; font-size: 13px; font-weight: 600; text-decoration: none; display: inline-block; }
    </style>
</head>
<body>

<?php if (!$authenticated): ?>

    <!-- ── Login ── -->
    <div class="login-box">
        <div class="card">
            <h1 style="font-size: 24px; margin-bottom: 4px;">JewelFAQ Admin</h1>
            <p style="color: #6b7280; font-size: 14px; margin-bottom: 24px;">Consultation management panel</p>
            <?php if ($auth_error): ?>
                <div class="flash-err"><?= htmlspecialchars($auth_error) ?></div>
            <?php endif; ?>
            <form method="POST">
                <label style="font-size: 14px; font-weight: 600;">Password</label>
                <input type="password" name="admin_password" autofocus placeholder="••••••••••">
                <button type="submit" class="btn btn-primary" style="width: 100%;">Log in</button>
            </form>
        </div>
    </div>

<?php else: ?>

    <!-- ── Admin Panel ── -->
    <div class="admin-wrap">

        <div class="admin-header">
            <h1 style="font-size: 22px; font-weight: 700;">Admin Panel — JewelFAQ</h1>
            <a href="admin.php?logout=1" style="font-size: 13px; color: #6b7280;">Log out</a>
        </div>

        <?php if ($flash === 'accepted'): ?>
            <div class="flash-ok">Case accepted — payment link generated. Send it via WhatsApp.</div>
        <?php elseif ($flash === 'rejected'): ?>
            <div class="flash-ok">Case rejected.</div>
        <?php elseif ($flash === 'responded'): ?>
            <div class="flash-ok">Response saved. Case marked as answered.</div>
        <?php endif; ?>
        <?php if ($action_error): ?>
            <div class="flash-err"><?= htmlspecialchars($action_error) ?></div>
        <?php endif; ?>

        <!-- ── Tabs ── -->
        <div class="tabs">
            <?php
            $tab_defs = [
                'pending_review'    => ['label' => 'Pending Review',        'badge' => 'badge-new'],
                'awaiting_payment'  => ['label' => 'Awaiting Payment',      'badge' => 'badge-waiting'],
                'paid'              => ['label' => 'Paid — Needs Response', 'badge' => 'badge-paid'],
                'answered'          => ['label' => 'Answered',              'badge' => 'badge-answered'],
                'rejected'          => ['label' => 'Rejected',              'badge' => 'badge-rejected'],
            ];
            foreach ($tab_defs as $key => $def):
                $count  = count_tab($rows, $key);
                $active = $tab === $key ? ' active' : '';
            ?>
            <a href="admin.php?tab=<?= $key ?>" class="tab-btn<?= $active ?>">
                <?= $def['label'] ?>
                <?php if ($count > 0): ?>
                    <span class="badge <?= $def['badge'] ?>" style="margin-left:4px;"><?= $count ?></span>
                <?php endif; ?>
            </a>
            <?php endforeach; ?>
        </div>

        <?php
        $current = $rows[$tab] ?? [];
        ?>

        <!-- ═══════════════════════════════════════════════
             TAB: PENDING REVIEW
        ═══════════════════════════════════════════════ -->
        <?php if ($tab === 'pending_review'): ?>
            <div class="section-title">Pending Review</div>
            <?php if (empty($current)): ?>
                <div class="card empty-state">No cases pending review. &#127881;</div>
            <?php endif; ?>
            <?php foreach ($current as $c): ?>
                <div class="card">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:8px;">
                        <div>
                            <strong style="font-size: 16px;"><?= htmlspecialchars($c['name']) ?></strong>
                            <div class="meta">
                                <?= htmlspecialchars($c['form_type']) ?> ·
                                WhatsApp: <?= htmlspecialchars($c['phone'] ?? '—') ?> ·
                                Received: <?= htmlspecialchars(substr($c['created_at'], 0, 16)) ?>
                            </div>
                        </div>
                        <span class="badge badge-new">New</span>
                    </div>

                    <div class="msg-box"><?= htmlspecialchars($c['message']) ?></div>

                    <div style="display: flex; gap: 12px; flex-wrap: wrap; margin-top: 8px;">
                        <!-- Accept -->
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="accept">
                            <input type="hidden" name="consultation_id" value="<?= htmlspecialchars($c['id']) ?>">
                            <button type="submit" class="btn-accept">&#10003; Accept — generate payment link</button>
                        </form>
                        <!-- Reject -->
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Reject this case?');">
                            <input type="hidden" name="action" value="reject">
                            <input type="hidden" name="consultation_id" value="<?= htmlspecialchars($c['id']) ?>">
                            <button type="submit" class="btn-reject">&#10007; Reject</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>

        <!-- ═══════════════════════════════════════════════
             TAB: AWAITING PAYMENT
        ═══════════════════════════════════════════════ -->
        <?php elseif ($tab === 'awaiting_payment'): ?>
            <div class="section-title">Awaiting Payment</div>
            <?php if (empty($current)): ?>
                <div class="card empty-state">No cases awaiting payment.</div>
            <?php endif; ?>
            <?php foreach ($current as $c): ?>
                <?php
                $payment_url = $c['payment_link'] ?? '';
                $wa_text     = "Hi {$c['name']}! Your JewelFAQ case has been accepted. Please pay securely here: {$payment_url}";
                $wa_url      = $c['phone'] ? wa_link($c['phone'], $wa_text) : '';
                ?>
                <div class="card">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:8px;">
                        <div>
                            <strong style="font-size: 16px;"><?= htmlspecialchars($c['name']) ?></strong>
                            <div class="meta">
                                <?= htmlspecialchars($c['form_type']) ?> ·
                                WhatsApp: <?= htmlspecialchars($c['phone'] ?? '—') ?> ·
                                Received: <?= htmlspecialchars(substr($c['created_at'], 0, 16)) ?>
                            </div>
                        </div>
                        <span class="badge badge-waiting">Awaiting Payment</span>
                    </div>

                    <div class="msg-box"><?= htmlspecialchars($c['message']) ?></div>

                    <p style="font-size: 13px; font-weight: 600; margin: 12px 0 4px;">Payment link (£50):</p>
                    <div class="link-box"><?= htmlspecialchars($payment_url ?: '—') ?></div>

                    <?php if ($wa_url && $payment_url): ?>
                        <a href="<?= htmlspecialchars($wa_url) ?>" target="_blank" class="btn-wa">
                            &#128172; Send payment link via WhatsApp
                        </a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

        <!-- ═══════════════════════════════════════════════
             TAB: PAID — NEEDS RESPONSE
        ═══════════════════════════════════════════════ -->
        <?php elseif ($tab === 'paid'): ?>
            <div class="section-title">Paid — Awaiting Response</div>
            <?php if (empty($current)): ?>
                <div class="card empty-state">No paid cases awaiting response.</div>
            <?php endif; ?>
            <?php foreach ($current as $c): ?>
                <?php
                $token        = response_token($c['id']);
                $response_url = SITE_URL . '/view-response.php?id=' . $c['id'] . '&t=' . $token;
                $wa_text      = "Hi {$c['name']}! Your JewelFAQ response is ready. View it here: {$response_url}";
                $wa_url       = $c['phone'] ? wa_link($c['phone'], $wa_text) : '';
                ?>
                <div class="card">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:8px;">
                        <div>
                            <strong style="font-size: 16px;"><?= htmlspecialchars($c['name']) ?></strong>
                            <div class="meta">
                                <?= htmlspecialchars($c['form_type']) ?> ·
                                WhatsApp: <?= htmlspecialchars($c['phone'] ?? '—') ?> ·
                                Paid: <?= htmlspecialchars(substr($c['created_at'], 0, 16)) ?>
                            </div>
                        </div>
                        <span class="badge badge-paid">Paid</span>
                    </div>

                    <div class="msg-box"><?= htmlspecialchars($c['message']) ?></div>

                    <div class="response-form" style="margin-top: 16px;">
                        <form method="POST">
                            <input type="hidden" name="action" value="respond">
                            <input type="hidden" name="consultation_id" value="<?= htmlspecialchars($c['id']) ?>">
                            <label style="font-size: 13px; font-weight: 600; display:block; margin-bottom: 6px;">
                                Your response (client will see it via their private link)
                            </label>
                            <textarea name="response" placeholder="Write your full technical analysis here…"></textarea>
                            <div style="display: flex; align-items: center; gap: 16px; margin-top: 12px; flex-wrap: wrap;">
                                <button type="submit" class="btn btn-primary">Save response</button>
                                <?php if ($wa_url): ?>
                                    <a href="<?= htmlspecialchars($wa_url) ?>" target="_blank" class="btn-wa" style="font-size: 13px;">
                                        &#128172; Send view link via WhatsApp
                                    </a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>

        <!-- ═══════════════════════════════════════════════
             TAB: ANSWERED
        ═══════════════════════════════════════════════ -->
        <?php elseif ($tab === 'answered'): ?>
            <div class="section-title">Answered</div>
            <?php if (empty($current)): ?>
                <div class="card empty-state">No answered cases yet.</div>
            <?php endif; ?>
            <?php foreach ($current as $c): ?>
                <div class="card">
                    <details>
                        <summary>
                            <?= htmlspecialchars($c['name']) ?> &mdash;
                            <?= htmlspecialchars($c['form_type']) ?> &middot;
                            Answered <?= htmlspecialchars(substr($c['response_date'] ?? '', 0, 16)) ?>
                        </summary>
                        <div class="meta" style="margin-top: 12px;">
                            WhatsApp: <?= htmlspecialchars($c['phone'] ?? '—') ?>
                        </div>
                        <p style="font-size: 13px; font-weight: 600; margin: 12px 0 4px;">Case:</p>
                        <div class="msg-box"><?= htmlspecialchars($c['message']) ?></div>
                        <p style="font-size: 13px; font-weight: 600; margin: 12px 0 4px;">Response sent:</p>
                        <div class="answered-response"><?= htmlspecialchars($c['response']) ?></div>
                    </details>
                </div>
            <?php endforeach; ?>

        <!-- ═══════════════════════════════════════════════
             TAB: REJECTED
        ═══════════════════════════════════════════════ -->
        <?php elseif ($tab === 'rejected'): ?>
            <div class="section-title">Rejected</div>
            <?php if (empty($current)): ?>
                <div class="card empty-state">No rejected cases.</div>
            <?php endif; ?>
            <?php foreach ($current as $c): ?>
                <div class="card">
                    <details>
                        <summary>
                            <?= htmlspecialchars($c['name']) ?> &mdash;
                            <?= htmlspecialchars($c['form_type']) ?> &middot;
                            Received <?= htmlspecialchars(substr($c['created_at'], 0, 16)) ?>
                        </summary>
                        <div class="meta" style="margin-top: 12px;">
                            WhatsApp: <?= htmlspecialchars($c['phone'] ?? '—') ?>
                        </div>
                        <div class="msg-box"><?= htmlspecialchars($c['message']) ?></div>
                    </details>
                </div>
            <?php endforeach; ?>

        <?php endif; ?>

    </div>

<?php endif; ?>

</body>
</html>
