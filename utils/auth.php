<?php
session_start();

/**
 * Check if a user is currently logged in
 * @return bool True if user is logged in, false otherwise
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get the current user's ID
 * @return int|null The user's ID if logged in, null otherwise
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get the current user's email
 * @return string|null The user's email if logged in, null otherwise
 */
function getCurrentUserEmail() {
    return $_SESSION['user_email'] ?? null;
}

/**
 * Set the user's session data after successful login
 * @param int $userId The user's ID
 * @param string $email The user's email
 */
function setUserSession($userId, $email) {
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_email'] = $email;
}

/**
 * Clear the user's session data on logout
 */
function clearUserSession() {
    unset($_SESSION['user_id']);
    unset($_SESSION['user_email']);
    session_destroy();
}

/**
 * Require user to be logged in
 * Redirects to login page if not logged in
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /QuizWhiz-web-app/pages/login.html');
        exit;
    }
} 