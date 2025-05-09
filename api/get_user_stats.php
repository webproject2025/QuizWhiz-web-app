<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json');

try {
    $conn = getDBConnection();
    
    // Get current user
    session_start();
    if (!isset($_SESSION['user_id'])) {
        throw new Exception("User not authenticated");
    }
    $user_id = $_SESSION['user_id'];

    // Get user's most common category and difficulty
    $query = "SELECT 
                q.category,
                q.difficulty,
                COUNT(*) as count
              FROM quiz_attempts qa
              JOIN quizzes q ON qa.quiz_id = q.quiz_id
              WHERE qa.user_id = :user_id 
              AND qa.status = 'completed'
              GROUP BY q.category, q.difficulty
              ORDER BY count DESC
              LIMIT 1";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'stats' => [
            'most_common_category' => $stats['category'] ?? 'N/A',
            'most_common_difficulty' => $stats['difficulty'] ?? 'N/A'
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
} 