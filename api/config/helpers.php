<?php
// CORS headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=utf-8");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Session management
function startSecureSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start([
            'cookie_httponly' => true,
            'cookie_secure' => isset($_SERVER['HTTPS']),
            'cookie_samesite' => 'Strict',
            'use_strict_mode' => true
        ]);
    }
}

// Generate unique application number
function generateApplicationNumber() {
    return 'APP-' . date('Y') . '-' . str_pad(random_int(1, 99999), 5, '0', STR_PAD_LEFT);
}

// Sanitize input
function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

// Validate email
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Validate Philippine phone number
function isValidPhone($phone) {
    $digits = preg_replace('/\D/', '', $phone);
    return strlen($digits) === 11 && substr($digits, 0, 2) === '09';
}

// JSON response helper
function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

// Error response
function sendError($message, $statusCode = 400) {
    sendResponse(['error' => $message], $statusCode);
}

// Success response
function sendSuccess($data = null, $message = 'Success') {
    sendResponse(['success' => true, 'message' => $message, 'data' => $data]);
}

// Get authenticated user ID from session
function getCurrentUserId() {
    startSecureSession();
    return $_SESSION['user_id'] ?? null;
}

// Check if user is logged in
function isLoggedIn() {
    $userId = getCurrentUserId();
    return !is_null($userId) && $userId > 0;
}

// Check user role
function hasRole($requiredRole) {
    startSecureSession();
    $userRole = $_SESSION['user_type'] ?? '';
    
    if ($requiredRole === 'admin') {
        return in_array($userRole, ['admin', 'super_admin']);
    }
    return $userRole === $requiredRole;
}

// Get user data from database
function getUserById($pdo, $userId) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetch();
}
?>