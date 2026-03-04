<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Custom JSON error handler
set_exception_handler(function ($exception) {
    $response = [
        "success" => false,
        "message" => $exception->getMessage()
    ];
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit();
});

// Receive the action type
$action = $_GET['action'] ?? '';
if ($action === '') {
    echo json_encode([
        "success" => false,
        "message" => "No action specified!"
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit();
}

// Load JSON data
$jsonFile = './js/data.json';
if (!file_exists($jsonFile)) {
    echo json_encode([
        "success" => false,
        "message" => "Data file not found!"
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit();
}

$data = json_decode(file_get_contents($jsonFile), true);
if ($data === null) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid JSON!"
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit();
}

// Get base URL from JSON
$base_url = $data['baseUrl'] ?? '';

// Helper function to prefix all image URLs
function prefixImages($item, $base_url, $keys = null) {
    if (!$keys) {
        $keys = ['image', 'image2', 'image3', 'mapImage'];
    }
    
    if (is_array($item)) {
        foreach ($keys as $key) {
            if (isset($item[$key]) && !empty($item[$key]) && is_string($item[$key])) {
                // Check if URL is already prefixed to avoid double prefixing
                if (strpos($item[$key], 'http://') !== 0 && strpos($item[$key], 'https://') !== 0) {
                    $item[$key] = rtrim($base_url, '/') . '/' . ltrim($item[$key], '/');
                }
            }
        }
    }
    return $item;
}

// Helper function to prefix amenity images
function prefixAmenityImages($project, $base_url) {
    if (isset($project['amenities']) && is_array($project['amenities'])) {
        foreach ($project['amenities'] as &$amenity) {
            if (isset($amenity['img']) && !empty($amenity['img']) && is_string($amenity['img'])) {
                // Check if URL is already prefixed to avoid double prefixing
                if (strpos($amenity['img'], 'http://') !== 0 && strpos($amenity['img'], 'https://') !== 0) {
                    $amenity['img'] = rtrim($base_url, '/') . '/' . ltrim($amenity['img'], '/');
                }
            }
        }
    }
    return $project;
}

// NEW: Reusable function to generate filters for any property
function generateFilters($project) {
    $filters = [
        'bathroomCounts' => [],
        'availableSizes' => [],
        'balconyCounts' => [],
        'locations' => [],           // For 'location' field only
        'communities' => [],         // For 'community' field only
        'fullAddresses' => []
    ];
    
    // Extract specifications for filtering
    $specs = $project['specifications'] ?? [];
    
    foreach ($specs as $spec) {
        // Check if spec is an array and has 'label' key
        if (!is_array($spec) || !isset($spec['label']) || !isset($spec['value'])) {
            continue;
        }
        
        $label = trim($spec['label']);
        $value = trim($spec['value']);
        
        // Extract bathroom counts
        if ($label === 'Bathroom' && !empty($value) && is_string($value)) {
            $baths = array_map('trim', explode('/', $value));
            $filters['bathroomCounts'] = array_merge($filters['bathroomCounts'], $baths);
        }
        
        // Extract balcony counts
        if ($label === 'Balcony' && !empty($value) && is_string($value)) {
            $balconies = array_map('trim', explode('/', $value));
            $filters['balconyCounts'] = array_merge($filters['balconyCounts'], $balconies);
        }
        
        // Extract flat sizes from unit specifications
        if (stripos($label, 'Unit') === 0 && stripos($value, 'SFT') !== false) {
            // Extract numeric value before "SFT"
            $size = trim(explode('SFT', $value)[0]);
            if (is_numeric($size)) {
                $filters['availableSizes'][] = $size;
            }
        }
    }
    
    // Add location filters - SEPARATE into different arrays
    if (isset($project['location']) && !empty($project['location'])) {
        $filters['locations'][] = $project['location'];
    }
    if (isset($project['community']) && !empty($project['community'])) {
        $filters['communities'][] = $project['community'];
    }
    if (isset($project['fullLocation']) && !empty($project['fullLocation'])) {
        $filters['fullAddresses'][] = $project['fullLocation'];
    }
    
    // Clean up filter arrays - remove duplicates and empty values
    $filters['bathroomCounts'] = array_values(array_unique(array_filter($filters['bathroomCounts'])));
    $filters['availableSizes'] = array_values(array_unique(array_filter($filters['availableSizes'])));
    $filters['balconyCounts'] = array_values(array_unique(array_filter($filters['balconyCounts'])));
    $filters['locations'] = array_values(array_unique(array_filter($filters['locations'])));
    $filters['communities'] = array_values(array_unique(array_filter($filters['communities'])));
    $filters['fullAddresses'] = array_values(array_unique(array_filter($filters['fullAddresses'])));
    
    return $filters;
}

//////////////////////////////////////////////////////////////////////////////////////
// Handle the 'get-all-projects' action (For ProjectGrid component)
//////////////////////////////////////////////////////////////////////////////////////
if ($action === 'get-all-projects') {
    $allProjects = $data['allProperties'] ?? [];
    
    // Filter out canceled projects
    $filteredProjects = array_filter($allProjects, function($project) {
        $tag = isset($project['tag']) ? strtoupper($project['tag']) : '';
        return !in_array($tag, ['CANCELED', 'CANCELLED', 'CANCLED']);
    });
    
    // Process each project
    foreach ($filteredProjects as &$project) {
        $project = prefixImages($project, $base_url);
        $project = prefixAmenityImages($project, $base_url);
        
        // Generate filters using the reusable function
        $project['filters'] = generateFilters($project);
    }
    
    $response = [
        "success" => true,
        "message" => "All projects loaded successfully.",
        "communities" => $data['communities'] ?? [],
        "priceRanges" => $data['priceRanges'] ?? [],
        "allProperties" => array_values($filteredProjects)
    ];

    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit();
}

//////////////////////////////////////////////////////////////////////////////////////
// Handle 'get-projects-by-community' action
//////////////////////////////////////////////////////////////////////////////////////
if ($action === 'get-projects-by-community') {
    $community = $_GET['community'] ?? '';
    if (!$community) {
        echo json_encode([
            "success" => false,
            "message" => "Community not specified!"
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        exit();
    }

    $filtered = [];
    foreach ($data['allProperties'] as $project) {
        if (isset($project['community']) && strtolower($project['community']) === strtolower($community)) {
            $project = prefixImages($project, $base_url);
            $project = prefixAmenityImages($project, $base_url);
            
            // Generate filters using the reusable function
            $project['filters'] = generateFilters($project);
            
            $filtered[] = $project;
        }
    }

    $response = [
        "success" => true,
        "message" => "Filtered projects loaded successfully.",
        "projects" => $filtered
    ];

    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit();
}

//////////////////////////////////////////////////////////////////////////////////////
// Handle 'get-property-by-id' action (For PropertyDetailsPage)
//////////////////////////////////////////////////////////////////////////////////////
if ($action === 'get-property-by-id') {
    $id = $_GET['id'] ?? 0;
    
    if (!$id) {
        echo json_encode([
            "success" => false,
            "message" => "Property ID not specified!"
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        exit();
    }
    
    $foundProperty = null;
    foreach ($data['allProperties'] as $project) {
        if (isset($project['id']) && $project['id'] == $id) {
            $foundProperty = $project;
            break;
        }
    }
    
    if (!$foundProperty) {
        echo json_encode([
            "success" => false,
            "message" => "Property not found!"
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        exit();
    }
    
    // Process the property with all image prefixes
    $foundProperty = prefixImages($foundProperty, $base_url);
    $foundProperty = prefixAmenityImages($foundProperty, $base_url);
    
    // Generate filters using the reusable function
    $foundProperty['filters'] = generateFilters($foundProperty);
    
    $response = [
        "success" => true,
        "message" => "Property loaded successfully.",
        "property" => $foundProperty
    ];

    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit();
}

//////////////////////////////////////////////////////////////////////////////////////
// Handle wrong/invalid action
//////////////////////////////////////////////////////////////////////////////////////
echo json_encode([
    "success" => false,
    "message" => "Invalid action specified!"
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
exit();
?>