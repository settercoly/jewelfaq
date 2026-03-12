<?php
require_once __DIR__ . '/config.php';

/**
 * Generic Stripe REST API request via cURL.
 */
function stripe_request(string $method, string $endpoint, array $data = []): array
{
    $url = 'https://api.stripe.com/v1/' . $endpoint;

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERPWD        => STRIPE_SECRET_KEY . ':',
        CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    }

    $response  = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_err  = curl_error($ch);
    curl_close($ch);

    if ($curl_err) {
        return ['ok' => false, 'error' => $curl_err, 'data' => null];
    }

    return [
        'ok'   => $http_code >= 200 && $http_code < 300,
        'code' => $http_code,
        'data' => json_decode($response, true),
    ];
}

/**
 * Create a Stripe Checkout Session for a consultation.
 */
function stripe_create_checkout(
    string $consultation_id,
    string $tier_key,
    string $email,
    string $name
): array {
    $tier = CONSULTATION_TIERS[$tier_key];

    return stripe_request('POST', 'checkout/sessions', [
        'payment_method_types[]'                               => 'card',
        'line_items[0][price_data][currency]'                  => 'gbp',
        'line_items[0][price_data][product_data][name]'        => $tier['name'] . ' — JewelFAQ',
        'line_items[0][price_data][product_data][description]' => $tier['description'],
        'line_items[0][price_data][unit_amount]'               => $tier['amount'],
        'line_items[0][quantity]'                              => 1,
        'mode'                                                 => 'payment',
        'customer_email'                                       => $email,
        'success_url'                                          => SITE_URL . '/success.php?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url'                                           => SITE_URL . '/cancel.php',
        'metadata[consultation_id]'                            => $consultation_id,
        'metadata[customer_name]'                              => $name,
    ]);
}

/**
 * Generate a Stripe payment link for a specific consultation (admin flow).
 * Returns ['url' => string, 'session_id' => string].
 * Throws RuntimeException on Stripe error.
 */
function generate_payment_link(string $consultation_id): array
{
    require_once __DIR__ . '/db.php';
    $c    = get_consultation($consultation_id);
    $tier = CONSULTATION_TIERS['estandar'];

    $result = stripe_request('POST', 'checkout/sessions', [
        'payment_method_types[]'                               => 'card',
        'line_items[0][price_data][currency]'                  => 'gbp',
        'line_items[0][price_data][product_data][name]'        => 'JewelFAQ Consultation',
        'line_items[0][price_data][product_data][description]' => $tier['description'],
        'line_items[0][price_data][unit_amount]'               => $tier['amount'],
        'line_items[0][quantity]'                              => 1,
        'mode'                                                 => 'payment',
        'success_url'                                          => SITE_URL . '/success.php?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url'                                           => SITE_URL . '/cancel.php',
        'metadata[consultation_id]'                            => $consultation_id,
        'metadata[customer_name]'                              => $c ? $c['name'] : '',
    ]);

    if (!$result['ok']) {
        throw new RuntimeException('Stripe error: ' . ($result['data']['error']['message'] ?? 'unknown'));
    }

    return [
        'url'        => $result['data']['url'],
        'session_id' => $result['data']['id'],
    ];
}

/**
 * Retrieve a Checkout Session from Stripe (to verify payment).
 */
function stripe_get_session(string $session_id): array
{
    return stripe_request('GET', 'checkout/sessions/' . urlencode($session_id));
}

/**
 * Refund a Stripe payment via session ID.
 * Returns ['ok' => bool, 'error' => ?string, 'refund_id' => ?string].
 */
function refund_stripe_payment(string $stripe_session_id): array
{
    // Get the session to find the payment_intent
    $session = stripe_request('GET', 'checkout/sessions/' . urlencode($stripe_session_id));
    if (!$session['ok'] || !isset($session['data']['payment_intent'])) {
        return ['ok' => false, 'error' => 'Session not found or no payment intent', 'refund_id' => null];
    }

    $payment_intent_id = $session['data']['payment_intent'];

    // Get the payment intent to find the charge
    $intent = stripe_request('GET', 'payment_intents/' . urlencode($payment_intent_id));
    if (!$intent['ok'] || !isset($intent['data']['charges']['data'][0]['id'])) {
        return ['ok' => false, 'error' => 'Payment intent not found or no charge', 'refund_id' => null];
    }

    $charge_id = $intent['data']['charges']['data'][0]['id'];

    // Create the refund
    $result = stripe_request('POST', 'refunds', [
        'charge' => $charge_id,
    ]);

    if (!$result['ok']) {
        $error = $result['data']['error']['message'] ?? 'Unknown refund error';
        return ['ok' => false, 'error' => $error, 'refund_id' => null];
    }

    return [
        'ok'        => true,
        'error'     => null,
        'refund_id' => $result['data']['id'],
    ];
}

/**
 * Verify a Stripe webhook signature.
 * Returns the decoded event array, or false if invalid.
 */
function stripe_verify_webhook(string $payload, string $sig_header): array|false
{
    $parts      = explode(',', $sig_header);
    $timestamp  = null;
    $signatures = [];

    foreach ($parts as $part) {
        [$k, $v] = explode('=', $part, 2);
        if ($k === 't')  $timestamp    = $v;
        if ($k === 'v1') $signatures[] = $v;
    }

    if (!$timestamp || empty($signatures)) {
        return false;
    }

    // Reject replays older than 5 minutes
    if (abs(time() - (int) $timestamp) > 300) {
        return false;
    }

    $signed   = $timestamp . '.' . $payload;
    $expected = hash_hmac('sha256', $signed, STRIPE_WEBHOOK_SECRET);

    foreach ($signatures as $sig) {
        if (hash_equals($expected, $sig)) {
            return json_decode($payload, true);
        }
    }

    return false;
}
