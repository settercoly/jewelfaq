<?php
require_once __DIR__ . '/config.php';

/**
 * Returns a shared PDO connection to the SQLite database.
 * Creates the database and table on first use.
 */
function get_db(): PDO
{
    static $db = null;
    if ($db === null) {
        $data_dir = __DIR__ . '/data';
        if (!is_dir($data_dir)) {
            mkdir($data_dir, 0700, true);
        }
        $db = new PDO('sqlite:' . $data_dir . '/jewelfaq.db');
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $db->exec("
            CREATE TABLE IF NOT EXISTS consultations (
                id                TEXT PRIMARY KEY,
                name              TEXT NOT NULL,
                email             TEXT NOT NULL,
                message           TEXT NOT NULL,
                form_type         TEXT NOT NULL DEFAULT 'general',
                tier              TEXT NOT NULL,
                amount            INTEGER NOT NULL,
                stripe_session_id TEXT,
                payment_status    TEXT NOT NULL DEFAULT 'pending',
                response          TEXT,
                response_date     TEXT,
                created_at        TEXT NOT NULL DEFAULT (datetime('now'))
            )
        ");
    }
    return $db;
}

/** Generate a UUID v4 */
function generate_uuid(): string
{
    return sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

/** Derive a stable access token from a consultation ID */
function response_token(string $id): string
{
    return hash('sha256', $id . SECRET_SALT);
}

/** Fetch a consultation by ID */
function get_consultation(string $id): ?array
{
    $stmt = get_db()->prepare("SELECT * FROM consultations WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

/** Fetch a consultation by Stripe session ID */
function get_consultation_by_session(string $session_id): ?array
{
    $stmt = get_db()->prepare("SELECT * FROM consultations WHERE stripe_session_id = ?");
    $stmt->execute([$session_id]);
    return $stmt->fetch() ?: null;
}
