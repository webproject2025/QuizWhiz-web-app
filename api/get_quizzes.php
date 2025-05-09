<?php
require_once '../config/database.php';
require_once '../utils/auth.php';

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Get request data
$data = json_decode(file_get_contents('php://input'), true);
$page = isset($data['page']) ? (int)$data['page'] : 1;
$perPage = isset($data['per_page']) ? (int)$data['per_page'] : 6;
$tab = isset($data['tab']) ? $data['tab'] : 'all-quizzes';
$filters = isset($data['filters']) ? $data['filters'] : [];

// Calculate offset
$offset = ($page - 1) * $perPage;

try {
    $db = getDBConnection();
    $userId = getCurrentUserId();
    
    // Base query
    $baseQuery = "FROM quizzes q 
                  LEFT JOIN quiz_attempts qa ON q.quiz_id = qa.quiz_id AND qa.user_id = :user_id";
    
    // Where conditions based on tab
    $whereConditions = [];
    $params = [':user_id' => $userId];
    
    switch ($tab) {
        case 'created':
            $whereConditions[] = "q.created_by = :user_id";
            break;
        case 'completed':
            $whereConditions[] = "qa.status = 'completed'";
            break;
        case 'in-progress':
            $whereConditions[] = "qa.status = 'in_progress'";
            break;
        case 'favorites':
            $baseQuery .= " INNER JOIN user_favorites uf ON q.quiz_id = uf.quiz_id AND uf.user_id = :user_id";
            break;
    }
    
    // Add filter conditions
    if (!empty($filters['category']) && $filters['category'] !== 'all') {
        $whereConditions[] = "q.category = :category";
        $params[':category'] = $filters['category'];
    }
    
    if (!empty($filters['difficulty']) && $filters['difficulty'] !== 'all') {
        $whereConditions[] = "q.difficulty = :difficulty";
        $params[':difficulty'] = $filters['difficulty'];
    }
    
    if (!empty($filters['search'])) {
        $whereConditions[] = "(q.title LIKE :search OR q.description LIKE :search)";
        $params[':search'] = "%{$filters['search']}%";
    }
    
    // Combine where conditions
    $whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";
    
    // Order by clause
    $orderBy = "ORDER BY ";
    switch ($filters['sort'] ?? 'recent') {
        case 'oldest':
            $orderBy .= "q.created_at ASC";
            break;
        case 'popular':
            $orderBy .= "(SELECT COUNT(*) FROM quiz_attempts WHERE quiz_id = q.quiz_id) DESC";
            break;
        case 'highest':
            $orderBy .= "qa.score DESC";
            break;
        default: // recent
            $orderBy .= "q.created_at DESC";
    }
    
    // Get total count
    $countQuery = "SELECT COUNT(DISTINCT q.quiz_id) as total " . $baseQuery . " " . $whereClause;
    $stmt = $db->prepare($countQuery);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $totalQuizzes = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $totalPages = ceil($totalQuizzes / $perPage);
    
    // Get quizzes with simplified subqueries
    $query = "SELECT DISTINCT 
                q.*,
                qa.status,
                qa.score,
                (SELECT COUNT(*) FROM quiz_attempts WHERE quiz_id = q.quiz_id) as attempts,
                (SELECT COUNT(*) FROM user_answers ua 
                 INNER JOIN quiz_attempts qa2 ON ua.attempt_id = qa2.attempt_id 
                 WHERE qa2.quiz_id = q.quiz_id AND qa2.user_id = :user_id2) as answered_questions
              " . $baseQuery . " " . $whereClause . " " . $orderBy . " 
              LIMIT :limit OFFSET :offset";
    
    $stmt = $db->prepare($query);
    
    // Bind all parameters
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    // Bind the second user_id parameter for the subquery
    $stmt->bindValue(':user_id2', $userId);
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $quizzes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Process quiz data
    foreach ($quizzes as &$quiz) {
        // Calculate progress for in-progress quizzes
        if ($quiz['status'] === 'in_progress') {
            $quiz['progress'] = $quiz['total_questions'] > 0 
                ? round(($quiz['answered_questions'] / $quiz['total_questions']) * 100) 
                : 0;
        }
        
        // Remove sensitive data
        unset($quiz['answered_questions']);
    }
    
    echo json_encode([
        'quizzes' => $quizzes,
        'total_pages' => $totalPages,
        'current_page' => $page,
        'total_quizzes' => $totalQuizzes
    ]);
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("Server error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
} 