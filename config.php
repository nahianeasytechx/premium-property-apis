<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header('Cache-Control: no-cache, no-store, must-revalidate'); // Prevent caching
header('Pragma: no-cache'); // HTTP 1.0
header('Expires: 0'); // Proxies

// your React app origin
//header("Access-Control-Allow-Origin: https://example.com/");

$allowed_origins = [
    "https://dpremiumhomes.com/",
    "http://localhost:5173/"
];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: $origin");
    header("Access-Control-Allow-Credentials: true"); // if needed
}


header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// Include functions file
include 'functions.php';

$web_url= "http://localhost/sites/premium-property-data/";
// $web_url= "";

?>