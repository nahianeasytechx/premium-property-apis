<?php
session_start();
require_once './config.php';
header('Content-Type: application/json');

// Custom JSON error handler
set_exception_handler(function ($exception) {
    $response = [
        "success" => false,
        "message" => $exception->getMessage()
    ];
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit();
});

// Helper function to verify authentication token
function verifyToken() {
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    
    if (empty($authHeader)) {
        return null;
    }
    
    // Extract token from "Bearer TOKEN" format
    if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        $token = $matches[1];
    } else {
        $token = $authHeader;
    }
    
    try {
        $decoded = json_decode(base64_decode($token), true);
        
        // Check if token is expired
        if (isset($decoded['exp']) && $decoded['exp'] < time()) {
            return null;
        }
        
        return $decoded;
    } catch (Exception $e) {
        return null;
    }
}

// Helper function to send unauthorized response
function sendUnauthorized($message = "Authentication required. Please log in.") {
    http_response_code(401);
    echo json_encode([
        "success" => false,
        "message" => $message,
        "code" => "UNAUTHORIZED"
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit();
}

// Receive the action type
$action = $_GET['action'] ?? '';
if ($action === '') {
    echo json_encode([
        "success" => false,
        "message" => "No action specified!"
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit();
}

// Load users JSON data
$usersFile = './js/user.json';
if (!file_exists($usersFile)) {
    echo json_encode([
        "success" => false,
        "message" => "Users data file not found!"
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit();
}

$usersData = json_decode(file_get_contents($usersFile), true);
if ($usersData === null) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid users JSON!"
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit();
}

// Get all users
$allUsers = $usersData['users'] ?? [];

//////////////////////////////////////////////////////////////////////////////////////
// PUBLIC ENDPOINT - Handle 'authenticate-user' action (for login) - NO AUTH REQUIRED
//////////////////////////////////////////////////////////////////////////////////////
if ($action === 'authenticate-user') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (!$email || !$password) {
        echo json_encode([
            "success" => false,
            "message" => "Email and password are required!"
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        exit();
    }
    
    $authenticatedUser = null;
    foreach ($allUsers as $user) {
        if (isset($user['email']) && strtolower($user['email']) === strtolower($email)) {
            if ($user['password'] === $password) {
                $authenticatedUser = $user;
                break;
            } else {
                echo json_encode([
                    "success" => false,
                    "message" => "Invalid password!"
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                exit();
            }
        }
    }
    
    if (!$authenticatedUser) {
        echo json_encode([
            "success" => false,
            "message" => "User not found!"
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        exit();
    }
    
    // Remove password for security
    unset($authenticatedUser['password']);
    
    // Generate a token
    $token = base64_encode(json_encode([
        'id' => $authenticatedUser['id'],
        'email' => $authenticatedUser['email'],
        'name' => $authenticatedUser['name'],
        'exp' => time() + (7 * 24 * 60 * 60) // 7 days
    ]));
    
    $response = [
        "success" => true,
        "message" => "Authentication successful.",
        "token" => $token,
        "user" => $authenticatedUser
    ];

    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit();
}

//////////////////////////////////////////////////////////////////////////////////////
// ALL ENDPOINTS BELOW REQUIRE AUTHENTICATION
//////////////////////////////////////////////////////////////////////////////////////

// Verify authentication for all protected endpoints
$authenticatedUser = verifyToken();
if (!$authenticatedUser) {
    sendUnauthorized();
}

//////////////////////////////////////////////////////////////////////////////////////
// Handle the 'get-all-users' action - PROTECTED
//////////////////////////////////////////////////////////////////////////////////////
if ($action === 'get-all-users') {
    // Return all users (without passwords for security)
    $usersWithoutPasswords = array_map(function($user) {
        unset($user['password']);
        return $user;
    }, $allUsers);
    
    $response = [
        "success" => true,
        "message" => "All users loaded successfully.",
        "users" => $usersWithoutPasswords
    ];

    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit();
}

//////////////////////////////////////////////////////////////////////////////////////
// Handle 'get-user-by-id' action - PROTECTED
//////////////////////////////////////////////////////////////////////////////////////
if ($action === 'get-user-by-id') {
    $id = $_GET['id'] ?? 0;
    
    if (!$id) {
        echo json_encode([
            "success" => false,
            "message" => "User ID not specified!"
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        exit();
    }
    
    // Users can only view their own profile unless they're admin
    // For now, let's allow viewing any user if authenticated
    // You can add role-based access control here
    
    $foundUser = null;
    foreach ($allUsers as $user) {
        if (isset($user['id']) && $user['id'] == $id) {
            $foundUser = $user;
            break;
        }
    }
    
    if (!$foundUser) {
        echo json_encode([
            "success" => false,
            "message" => "User not found!"
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        exit();
    }
    
    // Remove password for security
    unset($foundUser['password']);
    
    $response = [
        "success" => true,
        "message" => "User loaded successfully.",
        "user" => $foundUser
    ];

    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit();
}

//////////////////////////////////////////////////////////////////////////////////////
// Handle 'get-user-by-email' action - PROTECTED
//////////////////////////////////////////////////////////////////////////////////////
if ($action === 'get-user-by-email') {
    $email = $_GET['email'] ?? '';
    
    if (!$email) {
        echo json_encode([
            "success" => false,
            "message" => "Email not specified!"
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        exit();
    }
    
    $foundUser = null;
    foreach ($allUsers as $user) {
        if (isset($user['email']) && strtolower($user['email']) === strtolower($email)) {
            $foundUser = $user;
            break;
        }
    }
    
    if (!$foundUser) {
        echo json_encode([
            "success" => false,
            "message" => "User not found!"
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        exit();
    }
    
    // Remove password for security
    unset($foundUser['password']);
    
    $response = [
        "success" => true,
        "message" => "User loaded successfully.",
        "user" => $foundUser
    ];

    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit();
}

//////////////////////////////////////////////////////////////////////////////////////
// Handle 'get-users-by-property' action - PROTECTED
//////////////////////////////////////////////////////////////////////////////////////
if ($action === 'get-users-by-property') {
    $propertyId = $_GET['propertyId'] ?? 0;
    
    if (!$propertyId) {
        echo json_encode([
            "success" => false,
            "message" => "Property ID not specified!"
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        exit();
    }
    
    $propertyUsers = [];
    foreach ($allUsers as $user) {
        if (isset($user['savedProperties']) && in_array($propertyId, $user['savedProperties'])) {
            // Remove password for security
            $userWithoutPassword = $user;
            unset($userWithoutPassword['password']);
            $propertyUsers[] = $userWithoutPassword;
        }
    }
    
    $response = [
        "success" => true,
        "message" => "Users for property loaded successfully.",
        "propertyId" => (int)$propertyId,
        "users" => $propertyUsers
    ];

    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit();
}

//////////////////////////////////////////////////////////////////////////////////////
// Handle 'get-user-flats' action - PROTECTED
//////////////////////////////////////////////////////////////////////////////////////
if ($action === 'get-user-flats') {
    $userId = $_GET['userId'] ?? 0;
    
    if (!$userId) {
        echo json_encode([
            "success" => false,
            "message" => "User ID not specified!"
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        exit();
    }
    
    // Optional: Ensure users can only view their own flats
    // Uncomment to restrict access to own flats only
    // if ($authenticatedUser['id'] != $userId) {
    //     sendUnauthorized("You can only view your own flats.");
    // }
    
    $foundUser = null;
    foreach ($allUsers as $user) {
        if (isset($user['id']) && $user['id'] == $userId) {
            $foundUser = $user;
            break;
        }
    }
    
    if (!$foundUser) {
        echo json_encode([
            "success" => false,
            "message" => "User not found!"
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        exit();
    }
    
    // Get all flat details for this user
    $userFlats = [];
    if (isset($foundUser['flatDetails']) && is_array($foundUser['flatDetails'])) {
        foreach ($foundUser['flatDetails'] as $propertyId => $flatDetails) {
            $userFlats[] = [
                'propertyId' => (int)$propertyId,
                'flatDetails' => $flatDetails
            ];
        }
    }
    
    $response = [
        "success" => true,
        "message" => "User flats loaded successfully.",
        "userId" => (int)$userId,
        "flats" => $userFlats
    ];

    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit();
}

//////////////////////////////////////////////////////////////////////////////////////
// Handle 'get-current-user' action - Get logged-in user's info - PROTECTED
//////////////////////////////////////////////////////////////////////////////////////
if ($action === 'get-current-user') {
    $foundUser = null;
    foreach ($allUsers as $user) {
        if (isset($user['id']) && $user['id'] == $authenticatedUser['id']) {
            $foundUser = $user;
            break;
        }
    }
    
    if (!$foundUser) {
        echo json_encode([
            "success" => false,
            "message" => "User not found!"
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        exit();
    }
    
    // Remove password for security
    unset($foundUser['password']);
    
    $response = [
        "success" => true,
        "message" => "Current user loaded successfully.",
        "user" => $foundUser
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