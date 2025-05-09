<?php
session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

function getCurrentUserEmail() {
    return $_SESSION['user_email'] ?? null;
}

function loginUser($userId, $email) {
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_email'] = $email;
}

function logoutUser() {
    session_destroy();
}

function requireLogin() {
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
} 