<?php
// slider-api.php
session_start();
require_once './config.php';

// IMPORTANT: No whitespace or HTML before this!
header('Content-Type: application/json');

// Error handler to ensure JSON output even on errors
function sendJsonResponse($success, $message, $data = null) {
    $response = [
        "success" => $success,
        "message" => $message
    ];
    if ($data !== null) {
        $response = array_merge($response, $data);
    }
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit();
}

// Custom error handler
set_exception_handler(function ($exception) {
    sendJsonResponse(false, $exception->getMessage());
});

// Catch any PHP errors/warnings
set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    sendJsonResponse(false, "PHP Error: $errstr");
});

// Receive the action type
$action = $_GET['action'] ?? '';
if ($action == '') {
    sendJsonResponse(false, "No action specified!");
}

// Load JSON data
$jsonFile = __DIR__ . '/js/slider.json'; // Use absolute path
if (!file_exists($jsonFile)) {
    sendJsonResponse(false, "Data file not found!");
}

$jsonContent = file_get_contents($jsonFile);
if ($jsonContent === false) {
    sendJsonResponse(false, "Failed to read data file!");
}

$data = json_decode($jsonContent, true);
if ($data === null) {
    sendJsonResponse(false, "Invalid JSON: " . json_last_error_msg());
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

    sendJsonResponse(true, "All slides loaded successfully.", [
        "slides" => $allSlides
    ]);
}
//////////////////////////////////////////////////////////////////////////////////////

// Handle the 'get-slide-by-id' action
if ($action == 'get-slide-by-id') {
    $id = $_GET['id'] ?? 0;
    
    // Handle both numeric and string IDs
    $found = null;
    foreach ($data['slides'] as $index => $slide) {
        $slideId = $slide['id'] ?? $index;
        if ($slideId == $id) { // Loose comparison to handle string/numeric
            $found = prefixSlideImages($slide, $base_url);
            break;
        }
    }

    if ($found) {
        sendJsonResponse(true, "Slide loaded successfully.", [
            "slide" => $found
        ]);
    } else {
        sendJsonResponse(false, "Slide not found!");
    }
}
//////////////////////////////////////////////////////////////////////////////////////

// Handle wrong/invalid action
sendJsonResponse(false, "Invalid action specified!");
?>