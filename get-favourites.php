<?php
session_start();
require_once './config.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }

set_exception_handler(function ($e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit();
});

// ── Token verification ────────────────────────────────────────────────────────
function verifyToken() {
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    if (empty($authHeader)) return null;
    if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $m)) $token = $m[1];
    else $token = $authHeader;
    try {
        $decoded = json_decode(base64_decode($token), true);
        if (isset($decoded['exp']) && $decoded['exp'] < time()) return null;
        return $decoded;
    } catch (Exception $e) { return null; }
}

function sendUnauthorized($msg = "Authentication required.") {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => $msg, "code" => "UNAUTHORIZED"], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit();
}

// ── JSON file helpers ─────────────────────────────────────────────────────────
function getFavouritesPath() { return './js/favourites.json'; }

function loadFavourites() {
    $path = getFavouritesPath();
    if (!file_exists($path)) return [];
    $data = json_decode(file_get_contents($path), true);
    return is_array($data) ? $data : [];
}

function saveFavourites($data) {
    file_put_contents(getFavouritesPath(), json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

function getUserFavourites($userId) {
    $all = loadFavourites();
    return isset($all[(string)$userId]) ? $all[(string)$userId] : [];
}

function setUserFavourites($userId, $ids) {
    $all = loadFavourites();
    $all[(string)$userId] = array_values(array_unique(array_map('intval', $ids)));
    saveFavourites($all);
    return $all[(string)$userId];
}

// ── Load data ─────────────────────────────────────────────────────────────────
function loadProperties() {
    $f = './js/data.json';
    if (!file_exists($f)) return [];
    $d = json_decode(file_get_contents($f), true);
    return $d['allProperties'] ?? [];
}

function loadUsers() {
    $f = './js/user.json';
    if (!file_exists($f)) return [];
    $d = json_decode(file_get_contents($f), true);
    return $d['users'] ?? [];
}

function findProperty($id, $props) {
    foreach ($props as $p) { if ((int)$p['id'] === (int)$id) return $p; }
    return null;
}

function findUser($id, $users) {
    foreach ($users as $u) { if ((int)$u['id'] === (int)$id) return $u; }
    return null;
}

// ── Auth check ────────────────────────────────────────────────────────────────
$authenticatedUser = verifyToken();
if (!$authenticatedUser) sendUnauthorized();

// ── Route ─────────────────────────────────────────────────────────────────────
$action = $_GET['action'] ?? '';
$allProperties = loadProperties();
$allUsers = loadUsers();

// ── GET: get-favourites ───────────────────────────────────────────────────────
if ($action === 'get-favourites') {
    $userId = (int)($_GET['userId'] ?? 0);
    if (!$userId) { echo json_encode(["success"=>false,"message"=>"User ID required."], JSON_PRETTY_PRINT); exit(); }
    if (!findUser($userId, $allUsers)) { echo json_encode(["success"=>false,"message"=>"User not found."], JSON_PRETTY_PRINT); exit(); }

    $ids = getUserFavourites($userId);
    $properties = array_values(array_filter(array_map(fn($id) => findProperty($id, $allProperties), $ids)));

    echo json_encode([
        "success"             => true,
        "message"             => "Favourites loaded.",
        "userId"              => $userId,
        "favouriteIds"        => $ids,         // ← React stores this in localStorage
        "favouriteProperties" => $properties,
        "count"               => count($ids),
        "localStorageKey"     => "propertyWishlist", // hint for client
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit();
}

// ── POST: toggle-favourite ────────────────────────────────────────────────────
if ($action === 'toggle-favourite') {
    $data = json_decode(file_get_contents('php://input'), true);
    $userId     = (int)($data['userId']     ?? $_POST['userId']     ?? 0);
    $propertyId = (int)($data['propertyId'] ?? $_POST['propertyId'] ?? 0);

    if (!$userId || !$propertyId) { echo json_encode(["success"=>false,"message"=>"userId and propertyId required."], JSON_PRETTY_PRINT); exit(); }
    if (!findUser($userId, $allUsers)) { echo json_encode(["success"=>false,"message"=>"User not found."], JSON_PRETTY_PRINT); exit(); }
    if (!findProperty($propertyId, $allProperties)) { echo json_encode(["success"=>false,"message"=>"Property not found."], JSON_PRETTY_PRINT); exit(); }

    $ids   = getUserFavourites($userId);
    $index = array_search($propertyId, $ids);
    $done  = $index === false ? "added" : "removed";
    if ($index === false) $ids[] = $propertyId;
    else array_splice($ids, $index, 1);
    $ids = setUserFavourites($userId, $ids);

    echo json_encode([
        "success"         => true,
        "action"          => $done,
        "userId"          => $userId,
        "propertyId"      => $propertyId,
        "favouriteIds"    => $ids,   // ← React syncs localStorage with this
        "count"           => count($ids),
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit();
}

// ── POST: add-favourite ───────────────────────────────────────────────────────
if ($action === 'add-favourite') {
    $data = json_decode(file_get_contents('php://input'), true);
    $userId     = (int)($data['userId']     ?? $_POST['userId']     ?? 0);
    $propertyId = (int)($data['propertyId'] ?? $_POST['propertyId'] ?? 0);

    if (!$userId || !$propertyId) { echo json_encode(["success"=>false,"message"=>"userId and propertyId required."], JSON_PRETTY_PRINT); exit(); }
    if (!findUser($userId, $allUsers)) { echo json_encode(["success"=>false,"message"=>"User not found."], JSON_PRETTY_PRINT); exit(); }
    if (!findProperty($propertyId, $allProperties)) { echo json_encode(["success"=>false,"message"=>"Property not found."], JSON_PRETTY_PRINT); exit(); }

    $ids = getUserFavourites($userId);
    $alreadyIn = in_array($propertyId, $ids);
    if (!$alreadyIn) $ids = setUserFavourites($userId, array_merge($ids, [$propertyId]));

    echo json_encode([
        "success"      => true,
        "message"      => $alreadyIn ? "Already in favourites." : "Added to favourites.",
        "userId"       => $userId,
        "propertyId"   => $propertyId,
        "favouriteIds" => $ids,  // ← React syncs localStorage with this
        "count"        => count($ids),
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit();
}

// ── DELETE: remove-favourite ──────────────────────────────────────────────────
if ($action === 'remove-favourite') {
    $data = json_decode(file_get_contents('php://input'), true);
    $userId     = (int)($data['userId']     ?? $_GET['userId']     ?? 0);
    $propertyId = (int)($data['propertyId'] ?? $_GET['propertyId'] ?? 0);

    if (!$userId || !$propertyId) { echo json_encode(["success"=>false,"message"=>"userId and propertyId required."], JSON_PRETTY_PRINT); exit(); }

    $ids   = getUserFavourites($userId);
    $index = array_search($propertyId, $ids);
    if ($index === false) { echo json_encode(["success"=>false,"message"=>"Property not in favourites.","code"=>"NOT_FOUND"], JSON_PRETTY_PRINT); exit(); }

    array_splice($ids, $index, 1);
    $ids = setUserFavourites($userId, $ids);

    echo json_encode([
        "success"      => true,
        "message"      => "Removed from favourites.",
        "userId"       => $userId,
        "propertyId"   => $propertyId,
        "favouriteIds" => $ids,  // ← React syncs localStorage with this
        "count"        => count($ids),
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit();
}

// ── DELETE: clear-favourites ──────────────────────────────────────────────────
if ($action === 'clear-favourites') {
    $data   = json_decode(file_get_contents('php://input'), true);
    $userId = (int)($data['userId'] ?? $_GET['userId'] ?? 0);

    if (!$userId) { echo json_encode(["success"=>false,"message"=>"userId required."], JSON_PRETTY_PRINT); exit(); }

    setUserFavourites($userId, []);

    echo json_encode([
        "success"      => true,
        "message"      => "All favourites cleared.",
        "userId"       => $userId,
        "favouriteIds" => [],
        "count"        => 0,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit();
}

// ── GET: check-favourite ──────────────────────────────────────────────────────
if ($action === 'check-favourite') {
    $userId     = (int)($_GET['userId']     ?? 0);
    $propertyId = (int)($_GET['propertyId'] ?? 0);
    if (!$userId || !$propertyId) { echo json_encode(["success"=>false,"message"=>"userId and propertyId required."], JSON_PRETTY_PRINT); exit(); }

    $ids = getUserFavourites($userId);
    echo json_encode([
        "success"      => true,
        "userId"       => $userId,
        "propertyId"   => $propertyId,
        "isFavourite"  => in_array($propertyId, $ids),
        "favouriteIds" => $ids,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit();
}

// ── GET: get-all-favourites ───────────────────────────────────────────────────
if ($action === 'get-all-favourites') {
    echo json_encode(["success"=>true,"favourites"=>loadFavourites()], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit();
}

echo json_encode(["success"=>false,"message"=>"Invalid action."], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
exit();
?>


