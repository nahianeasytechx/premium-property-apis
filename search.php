<?php
session_start();
require_once './config.php';
header('Content-Type: application/json');

// Custom JSON error handler
set_exception_handler(function ($exception) {
    echo json_encode([
        "success" => false,
        "message" => $exception->getMessage()
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit();
});

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

$base_url = $data['baseUrl'] ?? '';

// ── Helper functions ──────────────────────────────────────────────

function prefixImages($item, $base_url, $keys = null) {
    if (!$keys) $keys = ['image', 'image2', 'image3', 'mapImage'];
    if (is_array($item)) {
        foreach ($keys as $key) {
            if (isset($item[$key]) && !empty($item[$key]) && is_string($item[$key])) {
                if (strpos($item[$key], 'http://') !== 0 && strpos($item[$key], 'https://') !== 0) {
                    $item[$key] = rtrim($base_url, '/') . '/' . ltrim($item[$key], '/');
                }
            }
        }
    }
    return $item;
}

function prefixAmenityImages($project, $base_url) {
    if (isset($project['amenities']) && is_array($project['amenities'])) {
        foreach ($project['amenities'] as &$amenity) {
            if (isset($amenity['img']) && !empty($amenity['img']) && is_string($amenity['img'])) {
                if (strpos($amenity['img'], 'http://') !== 0 && strpos($amenity['img'], 'https://') !== 0) {
                    $amenity['img'] = rtrim($base_url, '/') . '/' . ltrim($amenity['img'], '/');
                }
            }
        }
    }
    return $project;
}

function generateFilters($project) {
    $filters = [
        'bathroomCounts'  => [],
        'availableSizes'  => [],
        'balconyCounts'   => [],
        'locations'       => [],
        'communities'     => [],
        'fullAddresses'   => []
    ];

    foreach ($project['specifications'] ?? [] as $spec) {
        if (!is_array($spec) || !isset($spec['label'], $spec['value'])) continue;
        $label = trim($spec['label']);
        $value = trim($spec['value']);

        if ($label === 'Bathroom' && !empty($value)) {
            $filters['bathroomCounts'] = array_merge($filters['bathroomCounts'], array_map('trim', explode('/', $value)));
        }
        if ($label === 'Balcony' && !empty($value)) {
            $filters['balconyCounts'] = array_merge($filters['balconyCounts'], array_map('trim', explode('/', $value)));
        }
        if (stripos($label, 'Unit') === 0 && stripos($value, 'SFT') !== false) {
            $size = trim(explode('SFT', $value)[0]);
            if (is_numeric($size)) $filters['availableSizes'][] = $size;
        }
    }

    if (!empty($project['location']))     $filters['locations'][]     = $project['location'];
    if (!empty($project['community']))    $filters['communities'][]   = $project['community'];
    if (!empty($project['fullLocation'])) $filters['fullAddresses'][] = $project['fullLocation'];

    foreach ($filters as $key => $arr) {
        $filters[$key] = array_values(array_unique(array_filter($arr)));
    }
    return $filters;
}

// ── Get filter values from URL ──────────────────────────────────────

$q          = trim($_GET['q'] ?? '');
$communities = isset($_GET['community']) && $_GET['community'] !== '' 
    ? array_map('trim', explode(',', $_GET['community'])) 
    : [];
$priceRanges = isset($_GET['priceRange']) && $_GET['priceRange'] !== '' 
    ? array_map('trim', explode(',', $_GET['priceRange'])) 
    : [];
$tag        = trim($_GET['tag'] ?? '');
$bedrooms   = trim($_GET['bedrooms'] ?? '');
$minSize    = (int)($_GET['minSize'] ?? 0);
$maxSize    = (int)($_GET['maxSize'] ?? 0);
$bathrooms  = trim($_GET['bathrooms'] ?? '');
$location   = trim($_GET['location'] ?? '');
$page       = max(1, (int)($_GET['page'] ?? 1));
$limit      = min(50, max(1, (int)($_GET['limit'] ?? 12)));

$allProperties = $data['allProperties'] ?? [];
$results = [];

// ── Search logic ───────────────────────────────────────────────────

foreach ($allProperties as $project) {

    // Skip cancelled projects
    $projectTag = strtoupper(trim($project['tag'] ?? ''));
    if (in_array($projectTag, ['CANCELED', 'CANCELLED', 'CANCLED'])) continue;

    // Tag filter
    if ($tag !== '') {
        if (strtolower($tag) === 'available') {
            if (stripos($projectTag, 'SOLD') !== false) continue;
        } else {
            if (stripos($projectTag, strtoupper($tag)) === false) continue;
        }
    }

    // Community multi-filter (comma-separated)
    if (!empty($communities)) {
        $matched = false;
        foreach ($communities as $c) {
            if (strcasecmp(trim($project['community'] ?? ''), $c) === 0) {
                $matched = true;
                break;
            }
        }
        if (!$matched) continue;
    }

    // Price range multi-filter (comma-separated)
    if (!empty($priceRanges)) {
        $matched = false;
        foreach ($priceRanges as $p) {
            if (strcasecmp(trim($project['priceRange'] ?? ''), $p) === 0) {
                $matched = true;
                break;
            }
        }
        if (!$matched) continue;
    }

    // Location filter
    if ($location !== '') {
        $locHaystack = strtolower(($project['location'] ?? '') . ' ' . ($project['fullLocation'] ?? ''));
        if (stripos($locHaystack, $location) === false) continue;
    }

    // Bedrooms filter
    if ($bedrooms !== '') {
        $bedroomMatched = false;
        $typesField = strtolower($project['types'] ?? '');
        if (strpos($typesField, $bedrooms) !== false) $bedroomMatched = true;
        if (!$bedroomMatched) {
            foreach ($project['specifications'] ?? [] as $spec) {
                if (isset($spec['label']) && strtolower($spec['label']) === 'bedroom') {
                    $vals = array_map('trim', explode('/', $spec['value']));
                    if (in_array($bedrooms, $vals)) { $bedroomMatched = true; break; }
                }
            }
        }
        if (!$bedroomMatched) continue;
    }

    // Bathrooms filter
    if ($bathrooms !== '') {
        $bathMatched = false;
        foreach ($project['specifications'] ?? [] as $spec) {
            if (isset($spec['label']) && strtolower($spec['label']) === 'bathroom') {
                $vals = array_map('trim', explode('/', $spec['value']));
                if (in_array($bathrooms, $vals)) { $bathMatched = true; break; }
            }
        }
        if (!$bathMatched) continue;
    }

    // Size filter
    if ($minSize > 0 || $maxSize > 0) {
        $sizeMatched = false;
        foreach ($project['specifications'] ?? [] as $spec) {
            if (!isset($spec['label'], $spec['value'])) continue;
            if (stripos($spec['label'], 'Unit') === 0 && stripos($spec['value'], 'SFT') !== false) {
                $size = (int)trim(explode('SFT', $spec['value'])[0]);
                $aboveMin = ($minSize === 0 || $size >= $minSize);
                $belowMax = ($maxSize === 0 || $size <= $maxSize);
                if ($aboveMin && $belowMax) { $sizeMatched = true; break; }
            }
        }
        if (!$sizeMatched) continue;
    }

    // Free-text query
    if ($q !== '') {
        $haystack = strtolower(implode(' ', [
            $project['name'] ?? '',
            $project['location'] ?? '',
            $project['community'] ?? '',
            $project['types'] ?? '',
            $project['description'] ?? '',
            $project['fullLocation'] ?? '',
            $project['tag'] ?? '',
            $project['priceRange'] ?? '',
        ]));
        $keywords = array_filter(array_map('trim', explode(' ', strtolower($q))));
        $allFound = true;
        foreach ($keywords as $kw) {
            if (strpos($haystack, $kw) === false) { $allFound = false; break; }
        }
        if (!$allFound) continue;
    }

    // Passed all filters
    $project = prefixImages($project, $base_url);
    $project = prefixAmenityImages($project, $base_url);
    $project['filters'] = generateFilters($project);
    $results[] = $project;
}

// ── Pagination ─────────────────────────────────────────────────────
$total      = count($results);
$totalPages = (int)ceil($total / $limit);
$offset     = ($page - 1) * $limit;
$paginated  = array_slice($results, $offset, $limit);

// ── Response ───────────────────────────────────────────────────────
echo json_encode([
    "success"      => true,
    "message"      => "Search completed successfully.",
    "query"        => [
        "q"          => $q,
        "community"  => $communities,
        "priceRange" => $priceRanges,
        "tag"        => $tag,
        "bedrooms"   => $bedrooms,
        "bathrooms"  => $bathrooms,
        "minSize"    => $minSize ?: null,
        "maxSize"    => $maxSize ?: null,
        "location"   => $location,
        "page"       => $page,
        "limit"      => $limit,
    ],
    "pagination"   => [
        "total"      => $total,
        "page"       => $page,
        "limit"      => $limit,
        "totalPages" => $totalPages,
        "hasNext"    => $page < $totalPages,
        "hasPrev"    => $page > 1,
    ],
    "communities"  => $data['communities']  ?? [],
    "priceRanges"  => $data['priceRanges']  ?? [],
    "properties"   => $paginated
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
exit();
?>