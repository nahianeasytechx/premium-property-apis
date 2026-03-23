<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");
header('Cache-Control: no-cache, no-store, must-revalidate');

header('Pragma: no-cache');
header('Expires: 0');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Optional: whitelist specific origins instead of wildcard *
$allowed_origins = [
    "http://localhost:5173",
    "https://premium-api.dvalleybd.com",
    "https://api.dpremiumhomes.com/",
    "https://www.dpremiumhomes.com"
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: $origin");
}

// Include functions file
include 'functions.php';

$web_url = "http://localhost/sites/premium-property-data/";
// $web_url = "";
?>