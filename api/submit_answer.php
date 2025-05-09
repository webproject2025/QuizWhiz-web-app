<?php
require_once '../config/database.php';
require_once '../config/auth.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['attempt_id']) || !isset($data['question_id']) || !isset($data['answer_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

try {
    $db = getDBConnection();
    
    // Start transaction
    $db->beginTransaction();
    
    // Verify attempt belongs to user
    $stmt = $db->prepare("
        SELECT * FROM quiz_attempts 
        WHERE attempt_id = ? AND user_id = ? AND status = 'in_progress'
    ");
    $stmt->execute([$data['attempt_id'], $_SESSION['user_id']]);
    $attempt = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$attempt) {
        throw new Exception('Invalid attempt or attempt already completed');
    }
    
    // Check if answer is correct
    $stmt = $db->prepare("
        SELECT is_correct 
        FROM answers 
        WHERE answer_id = ? AND question_id = ?
    ");
    $stmt->execute([$data['answer_id'], $data['question_id']]);
    $answer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$answer) {
        throw new Exception('Invalid answer');
    }
    
    // Record user's answer
    $stmt = $db->prepare("
        INSERT INTO user_answers (
            attempt_id, 
            question_id, 
            selected_answer_id, 
            is_correct
        ) VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([
        $data['attempt_id'],
        $data['question_id'],
        $data['answer_id'],
        $answer['is_correct']
    ]);
    
    // Update attempt score if answer is correct
    if ($answer['is_correct']) {
        $stmt = $db->prepare("
            UPDATE quiz_attempts 
            SET score = score + 1 
            WHERE attempt_id = ?
        ");
        $stmt->execute([$data['attempt_id']]);
    }
    
    // Commit transaction
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'is_correct' => $answer['is_correct']
    ]);
    
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
} 