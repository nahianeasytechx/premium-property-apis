<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }

// ── Parse body ────────────────────────────────────────────────────────────────
$data   = json_decode(file_get_contents('php://input'), true);
$userId = (int)($data['userId'] ?? $_GET['userId'] ?? 0);

if (!$userId) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "User ID required."]);
    exit();
}

// ── Verify user exists ────────────────────────────────────────────────────────
$usersFile = './js/user.json';
if (!file_exists($usersFile)) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Users file not found."]);
    exit();
}

$usersData = json_decode(file_get_contents($usersFile), true);
$allUsers  = $usersData['users'] ?? [];
$userExists = false;

foreach ($allUsers as $u) {
    if ((int)$u['id'] === $userId) { $userExists = true; break; }
}

if (!$userExists) {
    http_response_code(404);
    echo json_encode(["success" => false, "message" => "User not found."]);
    exit();
}

// ── Log deletion request ──────────────────────────────────────────────────────
$requestsFile = './js/deletion_requests.json';
$requests     = [];

if (file_exists($requestsFile)) {
    $existing = json_decode(file_get_contents($requestsFile), true);
    if (is_array($existing)) $requests = $existing;
}

// Skip if already pending
foreach ($requests as $r) {
    if ((int)$r['userId'] === $userId && $r['status'] === 'pending') {
        http_response_code(200);
        echo json_encode(["success" => true, "message" => "Delete Request Sent Successfully. You will be notified Soon."]);
        exit();
    }
}

$requests[] = [
    "userId"      => $userId,
    "requestedAt" => date('Y-m-d H:i:s'),
    "status"      => "pending"
];

file_put_contents($requestsFile, json_encode($requests, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

// ── Respond ───────────────────────────────────────────────────────────────────
http_response_code(200);
echo json_encode(["success" => true, "message" => "Delete Request Sent Successfully. You will be notified Soon."]);
exit();
?>