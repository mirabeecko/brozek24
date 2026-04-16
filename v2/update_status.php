<?php
/**
 * update_status.php — Emergency Status Endpoint
 * Use from iOS Shortcuts or browser bookmarks.
 *
 * GET/POST params:
 *   key    (required) — secret defined below
 *   status (required) — "online" | "offline"
 *   slots  (optional) — integer 0-9
 */

define('SECRET_KEY', 'CHANGE_THIS_KEY_BEFORE_DEPLOY_2026');
define('STATUS_FILE', __DIR__ . '/status.json');

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('X-Content-Type-Options: nosniff');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if (!in_array($method, ['GET', 'POST'], true)) {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

$key    = $_REQUEST['key']    ?? '';
$status = $_REQUEST['status'] ?? '';
$slots  = $_REQUEST['slots']  ?? null;

if (!hash_equals(SECRET_KEY, $key)) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized.']);
    exit;
}

if (!in_array($status, ['online', 'offline'], true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid status. Use: online | offline.']);
    exit;
}

// Preserve existing fields
$existing = [];
if (file_exists(STATUS_FILE)) {
    $raw = file_get_contents(STATUS_FILE);
    $existing = json_decode($raw, true) ?: [];
}

$data = array_merge($existing, [
    'status'      => $status,
    'last_update' => date('c'),
]);

if ($slots !== null && is_numeric($slots)) {
    $data['slots_remaining'] = max(0, (int) $slots);
}

$json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
if (file_put_contents(STATUS_FILE, $json, LOCK_EX) === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Cannot write status.json. Check permissions.']);
    exit;
}

echo json_encode(['ok' => true, 'data' => $data]);
