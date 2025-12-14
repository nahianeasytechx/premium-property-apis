<?php
session_start();
require_once './config.php';
header('Content-Type: application/javascript');

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
$jsonFile = './js/data.json';
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

// Helper function to prefix blog images
function prefixBlogImages($blog, $base_url) {
    if (isset($blog['image']) && !empty($blog['image'])) {
        $blog['image'] = rtrim($base_url, '/') . '/' . ltrim($blog['image'], '/');
    }
    return $blog;
}

//////////////////////////////////////////////////////////////////////////////////////
//////////////////////////// Handle the 'get-all-blogs' action //////////////////////
//////////////////////////////////////////////////////////////////////////////////////
if ($action == 'get-all-blogs') {

    $allBlogs = $data['allBlogs'] ?? [];
    foreach ($allBlogs as &$blog) {
        $blog = prefixBlogImages($blog, $base_url);
    }

    $response = [
        "success" => true,
        "message" => "All blogs loaded successfully.",
        "blogs" => $allBlogs
    ];

    echo json_encode($response, JSON_PRETTY_PRINT);
    exit();
}
//////////////////////////////////////////////////////////////////////////////////////

//////////////////////////////////////////////////////////////////////////////////////
//////////////////////// Handle 'get-blog-by-id' action //////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////
if ($action == 'get-blog-by-id') {
    $id = intval($_GET['id'] ?? 0);
    if (!$id) {
        echo json_encode([
            "success" => false,
            "message" => "Blog ID not specified!"
        ], JSON_PRETTY_PRINT);
        exit();
    }

    $found = null;
    foreach ($data['allBlogs'] as $blog) {
        if ($blog['id'] == $id) {
            $found = prefixBlogImages($blog, $base_url);
            break;
        }
    }

    if ($found) {
        echo json_encode([
            "success" => true,
            "message" => "Blog loaded successfully.",
            "blog" => $found
        ], JSON_PRETTY_PRINT);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Blog not found!"
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
