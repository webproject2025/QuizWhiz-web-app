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
    
    // First, get the quiz details
    $stmt = $conn->prepare("
        SELECT q.*, u.email as creator_email 
        FROM quizzes q 
        LEFT JOIN users u ON q.created_by = u.user_id 
        WHERE q.quiz_id = :quizId AND q.is_published = true
    ");
    $stmt->bindParam(':quizId', $quiz_id, PDO::PARAM_INT);
    $stmt->execute();
    $quiz = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$quiz) {
        throw new Exception("Quiz not found or not published");
    }
    
    // Get quiz questions
    $stmt = $conn->prepare("
        SELECT 
            q.question_id, 
            q.question_text, 
            q.question_type, 
            q.points,
            GROUP_CONCAT(a.answer_text) as options,
            MAX(CASE WHEN a.is_correct = 1 THEN a.answer_text END) as correct_answer
        FROM questions q
        LEFT JOIN answers a ON q.question_id = a.question_id
        WHERE q.quiz_id = :quizId
        GROUP BY q.question_id
        ORDER BY q.question_order
    ");
    $stmt->bindParam(':quizId', $quiz_id, PDO::PARAM_INT);
    $stmt->execute();
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($questions)) {
        throw new Exception("No questions found for this quiz");
    }

    // Process questions to format options as array
    foreach ($questions as &$question) {
        $question['options'] = $question['options'] ? explode(',', $question['options']) : [];
        // Remove correct_answer from client response for security
        unset($question['correct_answer']);
    }

    // Return quiz data with questions
    echo json_encode([
        'success' => true,
        'quiz' => [
            'id' => $quiz['quiz_id'],
            'title' => $quiz['title'],
            'description' => $quiz['description'],
            'category' => $quiz['category'],
            'difficulty' => $quiz['difficulty'],
            'time_limit' => $quiz['time_limit'],
            'total_questions' => $quiz['total_questions'],
            'creator' => $quiz['creator_email']
        ],
        'questions' => $questions
    ]);

} catch (Exception $e) {
    error_log("Error in get_quiz_questions.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
} 