<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json');

try {
    $conn = getDBConnection();
    // Get top users by total score and quizzes taken
    $query = "SELECT u.user_id, u.email, 
                     COUNT(DISTINCT qa.quiz_id) AS quizzes_taken,
                     COUNT(qa.attempt_id) AS attempts,
                     SUM(qa.score) AS total_score,
                     MAX(qa.score) AS best_score
              FROM users u
              LEFT JOIN quiz_attempts qa ON u.user_id = qa.user_id AND qa.status = 'completed'
              GROUP BY u.user_id
              ORDER BY total_score DESC, quizzes_taken DESC, best_score DESC
              LIMIT 20";
    
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $leaderboard = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Convert numeric values to proper types
    foreach ($leaderboard as &$row) {
        $row['quizzes_taken'] = (int)$row['quizzes_taken'];
        $row['attempts'] = (int)$row['attempts'];
        $row['total_score'] = (float)$row['total_score'];
        $row['best_score'] = (float)$row['best_score'];
    }
    
    echo json_encode(['success' => true, 'leaderboard' => $leaderboard]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
} 