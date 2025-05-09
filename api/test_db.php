<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/database.php';

header('Content-Type: application/json');

try {
    $db = getDBConnection();
    
    // Test database connection
    echo json_encode([
        'status' => 'Database connection successful',
        'tables' => [
            'users' => $db->query("SELECT COUNT(*) as count FROM users")->fetch()['count'],
            'quizzes' => $db->query("SELECT COUNT(*) as count FROM quizzes")->fetch()['count'],
            'questions' => $db->query("SELECT COUNT(*) as count FROM questions")->fetch()['count'],
            'answers' => $db->query("SELECT COUNT(*) as count FROM answers")->fetch()['count']
        ],
        'sample_quiz' => $db->query("SELECT * FROM quizzes LIMIT 1")->fetch(),
        'sample_question' => $db->query("SELECT * FROM questions LIMIT 1")->fetch(),
        'sample_answer' => $db->query("SELECT * FROM answers LIMIT 1")->fetch()
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
} 