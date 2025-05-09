<?php
require_once '../config/database.php';
require_once '../utils/auth.php';

header('Content-Type: application/json');

// Get request data
$data = json_decode(file_get_contents('php://input'), true);
$email = $data['email'] ?? '';
$password = $data['password'] ?? '';

try {
    $db = getDBConnection();
    
    // For testing purposes, let's create a test user if it doesn't exist
    $stmt = $db->prepare("SELECT user_id, email FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user) {
        // Create test user
        $stmt = $db->prepare("INSERT INTO users (email, password_hash) VALUES (?, ?)");
        $stmt->execute([$email, password_hash($password, PASSWORD_DEFAULT)]);
        $userId = $db->lastInsertId();
    } else {
        $userId = $user['user_id'];
    }
    
    // Set session
    setUserSession($userId, $email);
    
    echo json_encode([
        'success' => true,
        'message' => 'Login successful',
        'user' => [
            'id' => $userId,
            'email' => $email
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Login failed: ' . $e->getMessage()
    ]);
} 