<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/auth.php';

header('Content-Type: application/json');

try {
    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        throw new Exception('Invalid request data');
    }
    
    $email = $data['email'] ?? '';
    $password = $data['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        throw new Exception('Email and password are required');
    }
    
    // Get database connection
    $conn = getDBConnection();
    
    // Check if email already exists
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        throw new Exception('Email already exists');
    }
    
    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert new user
    $stmt = $conn->prepare("INSERT INTO users (email, password_hash) VALUES (?, ?)");
    $stmt->bind_param("ss", $email, $hashedPassword);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to create user');
    }
    
    $userId = $conn->insert_id;
    
    // Login the user
    loginUser($userId, $email);
    
    // Return success response
    echo json_encode([
        'success' => true,
        'user' => [
            'id' => $userId,
            'email' => $email
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Signup error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
} 