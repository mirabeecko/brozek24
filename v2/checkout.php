<?php
/**
 * checkout.php — Stripe Checkout backend
 * ─────────────────────────────────────────────────────────────
 * NASTAVENÍ (2 kroky):
 *
 * 1. Stripe Dashboard → Products → vytvořte každou službu jako
 *    "Product" a zkopírujte "Price ID" (začíná price_...)
 *    do pole $PRICE_IDS níže.
 *
 * 2. Stripe Dashboard → Developers → API keys → Secret key
 *    dosaďte do $STRIPE_SECRET.
 *
 * Volání: POST /checkout.php    body: { "service": "web24h" }
 *         GET  /checkout.php?service=web24h
 *
 * Nevyžaduje Composer — používá nativní PHP HTTP funkce.
 * ─────────────────────────────────────────────────────────────
 */

define('STRIPE_SECRET', 'sk_live_REPLACE_WITH_YOUR_KEY');
define('YOUR_DOMAIN',   'https://brozek24.cz');
define('SUCCESS_URL',   YOUR_DOMAIN . '/dekujeme.html');
define('CANCEL_URL',    YOUR_DOMAIN . '/#services');

/* Price IDs ze Stripe Dashboard (Products → Price ID) */
$PRICE_IDS = [
    'web24h'      => 'price_REPLACE_WEB24H',
    'audit'       => 'price_REPLACE_AUDIT',
    'identity'    => 'price_REPLACE_IDENTITY',
    'konzultace'  => 'price_REPLACE_KONZULTACE',
    'eshop'       => 'price_REPLACE_ESHOP',
];

/* ── Routing ──────────────────────────────────── */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: ' . YOUR_DOMAIN);

$service = $_GET['service'] ?? '';

if (empty($service)) {
    $body = file_get_contents('php://input');
    $json = json_decode($body, true);
    $service = $json['service'] ?? '';
}

if (!isset($PRICE_IDS[$service])) {
    http_response_code(400);
    echo json_encode(['error' => 'Neznámá služba: ' . htmlspecialchars($service)]);
    exit;
}

$priceId = $PRICE_IDS[$service];

/* ── Stripe Checkout Session via HTTP ──────────── */
$postData = http_build_query([
    'payment_method_types[]'          => 'card',
    'line_items[0][price]'            => $priceId,
    'line_items[0][quantity]'         => 1,
    'mode'                            => 'payment',
    'success_url'                     => SUCCESS_URL . '?session_id={CHECKOUT_SESSION_ID}',
    'cancel_url'                      => CANCEL_URL,
    'locale'                          => 'cs',
    'billing_address_collection'      => 'auto',
    'customer_creation'               => 'always',
    'payment_intent_data[description]'=> 'Emergency Marketing — ' . $service,
]);

$context = stream_context_create([
    'http' => [
        'method'  => 'POST',
        'header'  => implode("\r\n", [
            'Authorization: Basic ' . base64_encode(STRIPE_SECRET . ':'),
            'Content-Type: application/x-www-form-urlencoded',
            'Content-Length: ' . strlen($postData),
            'Stripe-Version: 2024-04-10',
        ]),
        'content'         => $postData,
        'ignore_errors'   => true,
    ],
]);

$response = file_get_contents(
    'https://api.stripe.com/v1/checkout/sessions',
    false,
    $context
);

if ($response === false) {
    http_response_code(502);
    echo json_encode(['error' => 'Chyba komunikace se Stripe.']);
    exit;
}

$result = json_decode($response, true);

/* Stripe vrátí chybu jako objekt s klíčem "error" */
if (isset($result['error'])) {
    http_response_code(422);
    echo json_encode(['error' => $result['error']['message'] ?? 'Stripe chyba.']);
    exit;
}

/* Redirect URL pro frontend */
echo json_encode([
    'url'        => $result['url'],
    'session_id' => $result['id'],
]);
