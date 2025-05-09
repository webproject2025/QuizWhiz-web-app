<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/auth.php';

header('Content-Type: application/json');

try {
    // Check if user is logged in
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }

    // Get database connection
    $conn = getDBConnection();
    
    // Get user details
    $userId = getCurrentUserId();
    $stmt = $conn->prepare("SELECT user_id, email FROM users WHERE user_id = :userId");
    $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception("User not found");
    }

    // Return user data
    echo json_encode([
        'success' => true,
        'user' => [
            'id' => $user['user_id'],
            'email' => $user['email']
        ]
    ]);

} catch (Exception $e) {
    error_log("Error in get_current_user.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
} 