<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

try {
    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['quiz_id']) || !isset($data['answers'])) {
        throw new Exception("Missing required data");
    }

    $quiz_id = intval($data['quiz_id']);
    $answers = $data['answers'];

    // Get database connection
    $conn = getDBConnection();
    
    // Get current user
    session_start();
    if (!isset($_SESSION['user_id'])) {
        throw new Exception("User not authenticated");
    }
    $user_id = $_SESSION['user_id'];

    // Get quiz questions with correct answers
    $stmt = $conn->prepare("
        SELECT q.question_id, a.answer_text as correct_answer, q.points 
        FROM questions q
        JOIN answers a ON q.question_id = a.question_id
        WHERE q.quiz_id = :quizId AND a.is_correct = 1
    ");
    $stmt->bindParam(':quizId', $quiz_id, PDO::PARAM_INT);
    $stmt->execute();
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $total_points = 0;
    $earned_points = 0;
    $correct_answers = 0;
    $total_questions = count($questions);
    $questions_map = [];
    
    foreach ($questions as $question) {
        $questions_map[$question['question_id']] = $question;
        $total_points += $question['points'];
        
        if (isset($answers[$question['question_id']]) && 
            $answers[$question['question_id']] === $question['correct_answer']) {
            $earned_points += $question['points'];
            $correct_answers++;
        }
    }
    
    // Calculate score percentage
    $score_percentage = $total_points > 0 ? ($earned_points / $total_points) * 100 : 0;
    
    // Save quiz attempt
    $stmt = $conn->prepare("
        INSERT INTO quiz_attempts (user_id, quiz_id, score, end_time, status) 
        VALUES (:userId, :quizId, :score, NOW(), 'completed')
    ");
    $stmt->bindParam(':userId', $user_id, PDO::PARAM_INT);
    $stmt->bindParam(':quizId', $quiz_id, PDO::PARAM_INT);
    $stmt->bindParam(':score', $score_percentage, PDO::PARAM_STR);
    $stmt->execute();
    
    $attempt_id = $conn->lastInsertId();
    
    // Save individual answers
    $stmt = $conn->prepare("
        INSERT INTO user_answers (attempt_id, question_id, selected_answer_id, is_correct) 
        VALUES (:attemptId, :questionId, :answerId, :isCorrect)
    ");
    
    foreach ($answers as $question_id => $answer) {
        // Get the answer_id for the selected answer
        $stmt2 = $conn->prepare("
            SELECT answer_id 
            FROM answers 
            WHERE question_id = :questionId AND answer_text = :answerText
        ");
        $stmt2->bindParam(':questionId', $question_id, PDO::PARAM_INT);
        $stmt2->bindParam(':answerText', $answer, PDO::PARAM_STR);
        $stmt2->execute();
        $answer_row = $stmt2->fetch(PDO::FETCH_ASSOC);
        
        if ($answer_row) {
            $is_correct = isset($answers[$question_id]) && 
                         $answers[$question_id] === $questions_map[$question_id]['correct_answer'] ? 1 : 0;
            
            $stmt->bindParam(':attemptId', $attempt_id, PDO::PARAM_INT);
            $stmt->bindParam(':questionId', $question_id, PDO::PARAM_INT);
            $stmt->bindParam(':answerId', $answer_row['answer_id'], PDO::PARAM_INT);
            $stmt->bindParam(':isCorrect', $is_correct, PDO::PARAM_INT);
            $stmt->execute();
        }
    }
    
    // Return results
    echo json_encode([
        'success' => true,
        'score' => $score_percentage,
        'total_points' => $total_points,
        'earned_points' => $earned_points,
        'correct_answers' => $correct_answers,
        'total_questions' => $total_questions,
        'attempt_id' => $attempt_id
    ]);

} catch (Exception $e) {
    error_log("Error in submit_quiz.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
} 