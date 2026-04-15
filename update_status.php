<?php
/**
 * update_status.php — Emergency Status Endpoint
 *
 * Usage:
 *   GET/POST: update_status.php?key=SECRET&status=online
 *   GET/POST: update_status.php?key=SECRET&status=offline&slots=1
 *
 * Fields:
 *   key     (required) — secret key defined below
 *   status  (required) — "online" | "offline"
 *   slots   (optional) — integer, remaining emergency slots this week
 */

define('SECRET_KEY', 'CHANGE_THIS_KEY_BEFORE_DEPLOY_2026');
define('STATUS_FILE', __DIR__ . '/status.json');

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('X-Content-Type-Options: nosniff');

// Only allow GET and POST
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if (!in_array($method, ['GET', 'POST'], true)) {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

$key    = $_REQUEST['key']    ?? '';
$status = $_REQUEST['status'] ?? '';
$slots  = $_REQUEST['slots']  ?? null;

// Auth
if (!hash_equals(SECRET_KEY, $key)) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized.']);
    exit;
}

// Validate status
if (!in_array($status, ['online', 'offline'], true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid status. Allowed values: online, offline.']);
    exit;
}

// Build payload
$data = [
    'status'      => $status,
    'last_update' => date('c'),
];

if ($slots !== null && is_numeric($slots)) {
    $data['slots_remaining'] = max(0, (int) $slots);
}

// Read existing file to preserve fields we didn't update
if (file_exists(STATUS_FILE)) {
    $existing = json_decode(file_get_contents(STATUS_FILE), true);
    if (is_array($existing)) {
        // Preserve slots_remaining if not provided
        if (!isset($data['slots_remaining']) && isset($existing['slots_remaining'])) {
            $data['slots_remaining'] = $existing['slots_remaining'];
        }
        if (isset($existing['next_slot_hours'])) {
            $data['next_slot_hours'] = $existing['next_slot_hours'];
        }
    }
}

// Write
$json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

if (file_put_contents(STATUS_FILE, $json, LOCK_EX) === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Could not write status.json. Check file permissions.']);
    exit;
}

echo json_encode(['success' => true, 'data' => $data]);
