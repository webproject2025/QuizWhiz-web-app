<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

try {
    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        throw new Exception('Invalid JSON data');
    }

    // Validate required fields
    if (empty($data['title']) || empty($data['category']) || empty($data['difficulty']) || empty($data['questions']) || !is_array($data['questions'])) {
        throw new Exception('Missing required quiz fields');
    }

    session_start();
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('User not authenticated');
    }
    $user_id = $_SESSION['user_id'];

    $conn = getDBConnection();
    $conn->begin_transaction();

    // Insert quiz
    $stmt = $conn->prepare("INSERT INTO quizzes (title, description, category, difficulty, created_by, total_questions, time_limit, is_published) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
    $desc = $data['description'] ?? '';
    $total_questions = count($data['questions']);
    $time_limit = $data['time_limit'] ?? 10;
    $stmt->bind_param("ssssiii", $data['title'], $desc, $data['category'], $data['difficulty'], $user_id, $total_questions, $time_limit);
    $stmt->execute();
    $quiz_id = $conn->insert_id;

    // Insert questions and answers
    $question_order = 1;
    foreach ($data['questions'] as $q) {
        if (empty($q['text']) || empty($q['type']) || empty($q['options']) || !is_array($q['options'])) {
            throw new Exception('Invalid question data');
        }
        $points = $q['points'] ?? 1;
        $stmt = $conn->prepare("INSERT INTO questions (quiz_id, question_text, question_type, points, question_order) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issii", $quiz_id, $q['text'], $q['type'], $points, $question_order);
        $stmt->execute();
        $question_id = $conn->insert_id;

        // Insert answers
        foreach ($q['options'] as $opt) {
            if (!isset($opt['text'])) continue;
            $is_correct = !empty($opt['is_correct']) ? 1 : 0;
            $stmt2 = $conn->prepare("INSERT INTO answers (question_id, answer_text, is_correct) VALUES (?, ?, ?)");
            $stmt2->bind_param("isi", $question_id, $opt['text'], $is_correct);
            $stmt2->execute();
        }
        $question_order++;
    }

    $conn->commit();
    echo json_encode(['success' => true, 'quiz_id' => $quiz_id]);

} catch (Exception $e) {
    if (isset($conn) && $conn->errno === 0 && $conn->in_transaction) {
        $conn->rollback();
    }
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
} 