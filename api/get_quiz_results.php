<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

try {
    // Check if quiz_id is provided
    if (!isset($_GET['quiz_id'])) {
        throw new Exception("Quiz ID is required");
    }

    $quiz_id = intval($_GET['quiz_id']);

    // Get database connection
    $conn = getDBConnection();
    
    // Get current user
    session_start();
    if (!isset($_SESSION['user_id'])) {
        throw new Exception("User not authenticated");
    }
    $user_id = $_SESSION['user_id'];

    // Get the latest quiz attempt for this user and quiz
    $stmt = $conn->prepare("
        SELECT qa.*, q.title, q.total_questions
        FROM quiz_attempts qa
        JOIN quizzes q ON qa.quiz_id = q.quiz_id
        WHERE qa.quiz_id = :quizId 
        AND qa.user_id = :userId
        ORDER BY qa.end_time DESC
        LIMIT 1
    ");
    $stmt->bindParam(':quizId', $quiz_id, PDO::PARAM_INT);
    $stmt->bindParam(':userId', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $attempt = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$attempt) {
        throw new Exception("No attempt found for this quiz");
    }

    // Get correct answers count
    $stmt = $conn->prepare("
        SELECT COUNT(*) as correct_count
        FROM user_answers ua
        WHERE ua.attempt_id = :attemptId AND ua.is_correct = 1
    ");
    $stmt->bindParam(':attemptId', $attempt['attempt_id'], PDO::PARAM_INT);
    $stmt->execute();
    $correct_count = $stmt->fetch(PDO::FETCH_ASSOC)['correct_count'];

    // Calculate points
    $total_points = $attempt['total_questions'] * 10; // Assuming 10 points per question
    $earned_points = round(($attempt['score'] / 100) * $total_points);

    // Return results
    echo json_encode([
        'success' => true,
        'quiz' => [
            'title' => $attempt['title']
        ],
        'correct_answers' => $correct_count,
        'total_questions' => $attempt['total_questions'],
        'earned_points' => $earned_points,
        'total_points' => $total_points,
        'score' => $attempt['score']
    ]);

} catch (Exception $e) {
    error_log("Error in get_quiz_results.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
} 