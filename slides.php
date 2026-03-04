<?php
session_start();
require_once './config.php';
header('Content-Type: application/json'); // Changed to application/json

// Custom JSON error handler
set_exception_handler(function ($exception) {
    $response = [
        "success" => false,
        "message" => $exception->getMessage()
    ];
    echo json_encode($response, JSON_PRETTY_PRINT);
    exit();
});

// Receive the action type
$action = $_GET['action'] ?? '';
if ($action == '') {
    echo json_encode([
        "success" => false,
        "message" => "No action specified!"
    ], JSON_PRETTY_PRINT);
    exit();
}

// Load JSON data
$jsonFile = './js/slider.json';
if (!file_exists($jsonFile)) {
    echo json_encode([
        "success" => false,
        "message" => "Data file not found!"
    ], JSON_PRETTY_PRINT);
    exit();
}

$data = json_decode(file_get_contents($jsonFile), true);
if ($data === null) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid JSON!"
    ], JSON_PRETTY_PRINT);
    exit();
}

// Get base URL from JSON
$base_url = $data['baseUrl'] ?? '';

// Helper function to prefix slide images
function prefixSlideImages($slide, $base_url) {
    if (isset($slide['image']) && !empty($slide['image'])) {
        // Remove any leading slash and combine with base URL
        $imagePath = ltrim($slide['image'], '/');
        $slide['image'] = rtrim($base_url, '/') . '/' . $imagePath;
    }
    return $slide;
}

//////////////////////////////////////////////////////////////////////////////////////
//////////////////////////// Handle the 'get-all-slides' action /////////////////////
//////////////////////////////////////////////////////////////////////////////////////
if ($action == 'get-all-slides') {

    $allSlides = $data['slides'] ?? [];
    
    // Process each slide to add base URL to images
    foreach ($allSlides as $key => $slide) {
        $allSlides[$key] = prefixSlideImages($slide, $base_url);
    }

    $response = [
        "success" => true,
        "message" => "All slides loaded successfully.",
        "slides" => $allSlides
    ];

    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit();
}
//////////////////////////////////////////////////////////////////////////////////////

// Handle the 'get-slide-by-id' action (optional - if you need to get a single slide)
if ($action == 'get-slide-by-id') {
    $id = intval($_GET['id'] ?? 0);
    if (!$id) {
        echo json_encode([
            "success" => false,
            "message" => "Slide ID not specified!"
        ], JSON_PRETTY_PRINT);
        exit();
    }

    $found = null;
    foreach ($data['slides'] as $index => $slide) {
        // If your slides have IDs, use that. Otherwise use the array index
        if (($slide['id'] ?? $index) == $id) {
            $found = prefixSlideImages($slide, $base_url);
            break;
        }
    }

    if ($found) {
        echo json_encode([
            "success" => true,
            "message" => "Slide loaded successfully.",
            "slide" => $found
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Slide not found!"
        ], JSON_PRETTY_PRINT);
    }
    exit();
}
//////////////////////////////////////////////////////////////////////////////////////

// Handle wrong/invalid action
echo json_encode([
    "success" => false,
    "message" => "Invalid action specified!"
], JSON_PRETTY_PRINT);
exit();
?>