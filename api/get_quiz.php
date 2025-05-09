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

    // Get quiz ID from request (support both 'id' and 'quiz_id' parameters)
    $quizId = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_GET['quiz_id']) ? (int)$_GET['quiz_id'] : 0);
    if (!$quizId) {
        http_response_code(400);
        echo json_encode(['error' => 'Quiz ID is required']);
        exit;
    }

    // Get database connection
    $conn = getDBConnection();
    
    // Start transaction
    $conn->begin_transaction();

    try {
        // Create new quiz attempt
        $userId = getCurrentUserId();
        $stmt = $conn->prepare("INSERT INTO quiz_attempts (user_id, quiz_id, start_time, status) VALUES (?, ?, NOW(), 'in_progress')");
        $stmt->bind_param("ii", $userId, $quizId);
        $stmt->execute();
        $attemptId = $conn->insert_id;

        // Get quiz details
        $stmt = $conn->prepare("SELECT * FROM quizzes WHERE id = ? AND status = 'published'");
        $stmt->bind_param("i", $quizId);
        $stmt->execute();
        $quiz = $stmt->get_result()->fetch_assoc();

        if (!$quiz) {
            throw new Exception("Quiz not found or not published");
        }

        // Get questions and answers
        $stmt = $conn->prepare("
            SELECT q.*, GROUP_CONCAT(
                JSON_OBJECT(
                    'id', a.id,
                    'text', a.answer_text,
                    'is_correct', a.is_correct
                )
            ) as answers
            FROM questions q
            LEFT JOIN answers a ON q.id = a.question_id
            WHERE q.quiz_id = ?
            GROUP BY q.id
            ORDER BY q.question_order
        ");
        $stmt->bind_param("i", $quizId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $questions = [];
        while ($row = $result->fetch_assoc()) {
            $answers = json_decode('[' . $row['answers'] . ']', true);
            // Remove correct answer information from client-side
            foreach ($answers as &$answer) {
                unset($answer['is_correct']);
            }
            $questions[] = [
                'id' => $row['id'],
                'text' => $row['question_text'],
                'type' => $row['question_type'],
                'answers' => $answers
            ];
        }

        // Commit transaction
        $conn->commit();

        // Return quiz data
        echo json_encode([
            'attempt_id' => $attemptId,
            'quiz' => [
                'id' => $quiz['id'],
                'title' => $quiz['title'],
                'description' => $quiz['description'],
                'time_limit' => $quiz['time_limit']
            ],
            'questions' => $questions
        ]);

    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }

} catch (Exception $e) {
    error_log("Error in get_quiz.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to load quiz',
        'message' => $e->getMessage()
    ]);
} 