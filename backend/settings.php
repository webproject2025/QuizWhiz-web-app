
<?php
// Start session to maintain user state
session_start();

// Database connection settings
$host = "localhost";
$username = "root";
$password = "";
$database = "quizwhiz";

// Initialize variables for form data
$user = [
    'display_name' => 'Alex Johnson',
    'email' => 'alex.johnson@example.com',
    'bio' => 'I love taking quizzes and learning new things every day! Science enthusiast and trivia lover.',
    'avatar' => '😎',
    'stats' => [
        'quizzes_completed' => 127,
        'average_score' => 78,
        'best_category' => 'Science'
    ]
];

$appearance = [
    'theme' => 'light',
    'color_accent' => 'blue',
    'font_size' => 16
];

$quiz_preferences = [
    'default_category' => 'science',
    'default_difficulty' => 'medium',
    'questions_per_quiz' => 10,
    'show_timer' => true,
    'show_progress' => true,
    'immediate_feedback' => true,
    'allow_skipping' => false
];

$notifications = [
    'email_quiz_results' => true,
    'email_leaderboard' => true,
    'email_new_quizzes' => true,
    'email_newsletter' => false,
    'browser_quiz_results' => true,
    'browser_leaderboard' => false,
    'browser_new_quizzes' => true
];

$account = [
    'save_quiz_history' => true,
    'share_activity' => true,
    'personalized_content' => true,
    'analytics_cookies' => false
];

// Connect to database
try {
    $conn = new mysqli($host, $username, $password, $database);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    // Set character set
    $conn->set_charset("utf8mb4");
    
    // Check if user is logged in
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        
        // Fetch user data from database
        $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
        $stmt->bind_param("s", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user_data = $result->fetch_assoc();
            
            // Update user data
            $user['display_name'] = $user_data['display_name'];
            $user['email'] = $user_data['email'];
            $user['bio'] = $user_data['bio'];
            $user['avatar'] = $user_data['avatar'] ?: '😎';
            
            // Fetch user stats
            $stmt = $conn->prepare("SELECT COUNT(*) as quizzes_completed, AVG(score) as average_score FROM quiz_attempts WHERE user_id = ?");
            $stmt->bind_param("s", $user_id);
            $stmt->execute();
            $stats_result = $stmt->get_result();
            
            if ($stats_result->num_rows > 0) {
                $stats_data = $stats_result->fetch_assoc();
                $user['stats']['quizzes_completed'] = $stats_data['quizzes_completed'];
                $user['stats']['average_score'] = round($stats_data['average_score']);
            }
            
            // Fetch best category
            $stmt = $conn->prepare("SELECT category, AVG(score) as avg_score FROM quiz_attempts 
                                   JOIN quizzes ON quiz_attempts.quiz_id = quizzes.quiz_id 
                                   WHERE user_id = ? GROUP BY category ORDER BY avg_score DESC LIMIT 1");
            $stmt->bind_param("s", $user_id);
            $stmt->execute();
            $category_result = $stmt->get_result();
            
            if ($category_result->num_rows > 0) {
                $category_data = $category_result->fetch_assoc();
                $user['stats']['best_category'] = $category_data['category'];
            }
            
            // Fetch user settings
            $stmt = $conn->prepare("SELECT * FROM user_settings WHERE user_id = ?");
            $stmt->bind_param("s", $user_id);
            $stmt->execute();
            $settings_result = $stmt->get_result();
            
            if ($settings_result->num_rows > 0) {
                $settings_data = $settings_result->fetch_assoc();
                
                // Update appearance settings


$appearance['theme'] = $settings_data['theme'];
                $appearance['color_accent'] = $settings_data['color_accent'];
                $appearance['font_size'] = $settings_data['font_size'];
                
                // Update quiz preferences
                $quiz_preferences['default_category'] = $settings_data['default_category'];
                $quiz_preferences['default_difficulty'] = $settings_data['default_difficulty'];
                $quiz_preferences['questions_per_quiz'] = $settings_data['questions_per_quiz'];
                $quiz_preferences['show_timer'] = (bool)$settings_data['show_timer'];
                $quiz_preferences['show_progress'] = (bool)$settings_data['show_progress'];
                $quiz_preferences['immediate_feedback'] = (bool)$settings_data['immediate_feedback'];
                $quiz_preferences['allow_skipping'] = (bool)$settings_data['allow_skipping'];
                
                // Update notification settings
                $notifications['email_quiz_results'] = (bool)$settings_data['email_quiz_results'];
                $notifications['email_leaderboard'] = (bool)$settings_data['email_leaderboard'];
                $notifications['email_new_quizzes'] = (bool)$settings_data['email_new_quizzes'];
                $notifications['email_newsletter'] = (bool)$settings_data['email_newsletter'];
                $notifications['browser_quiz_results'] = (bool)$settings_data['browser_quiz_results'];
                $notifications['browser_leaderboard'] = (bool)$settings_data['browser_leaderboard'];
                $notifications['browser_new_quizzes'] = (bool)$settings_data['browser_new_quizzes'];
                
                // Update account settings
                $account['save_quiz_history'] = (bool)$settings_data['save_quiz_history'];
                $account['share_activity'] = (bool)$settings_data['share_activity'];
                $account['personalized_content'] = (bool)$settings_data['personalized_content'];
                $account['analytics_cookies'] = (bool)$settings_data['analytics_cookies'];
            }
        }
    } else {
        // For demo purposes, we'll use the default values
        // In a real application, redirect to login page
        // header("Location: login.php");
        // exit;
    }
    
    // Handle form submissions
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $response = ['status' => 'success', 'message' => 'Settings saved successfully'];
        
        // Check which form was submitted
        if (isset($_POST['form_type'])) {
            $form_type = $_POST['form_type'];
            
            switch ($form_type) {
                case 'profile':
                    handleProfileForm($conn, $user_id ?? null);
                    break;
                    
                case 'appearance':
                    handleAppearanceForm($conn, $user_id ?? null);
                    break;
                    
                case 'quiz_preferences':
                    handleQuizPreferencesForm($conn, $user_id ?? null);
                    break;
                    
                case 'notifications':
                    handleNotificationsForm($conn, $user_id ?? null);
                    break;
                    
                case 'account':
                    handleAccountForm($conn, $user_id ?? null);
                    break;
                    
                default:
                    $response = ['status' => 'error', 'message' => 'Invalid form type'];
            }
        } else {
            $response = ['status' => 'error', 'message' => 'Form type not specified'];
        }
        
        // Return JSON response for AJAX requests
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            header('Content-Type: application/json');
            echo json_encode($response);
            exit;
        }
    }
    
} catch (Exception $e) {
    // Log error
    error_log("Database error: " . $e->getMessage());

ezedin, [5/9/2025 3:57 AM]
// For AJAX requests, return error
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
        exit;
    }
}

// Function to handle profile form submission
function handleProfileForm($conn, $user_id) {
    if (!$user_id) return;
    
    $display_name = filter_input(INPUT_POST, 'display_name', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $bio = filter_input(INPUT_POST, 'bio', FILTER_SANITIZE_STRING);
    
    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Invalid email format");
    }
    
    // Update user data in database
    $stmt = $conn->prepare("UPDATE users SET display_name = ?, email = ?, bio = ? WHERE user_id = ?");
    $stmt->bind_param("ssss", $display_name, $email, $bio, $user_id);
    $stmt->execute();
    
    // Handle avatar upload if provided
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = $_FILES['avatar']['type'];
        
        if (in_array($file_type, $allowed_types)) {
            $upload_dir = '../uploads/avatars/';
            
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_name = $user_id . '_' . time() . '.' . pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
            $target_file = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $target_file)) {
                $avatar_path = 'uploads/avatars/' . $file_name;
                
                $stmt = $conn->prepare("UPDATE users SET avatar = ? WHERE user_id = ?");
                $stmt->bind_param("ss", $avatar_path, $user_id);
                $stmt->execute();
            }
        }
    }
}

// Function to handle appearance form submission
function handleAppearanceForm($conn, $user_id) {
    if (!$user_id) return;
    
    $theme = filter_input(INPUT_POST, 'theme', FILTER_SANITIZE_STRING);
    $color_accent = filter_input(INPUT_POST, 'color_accent', FILTER_SANITIZE_STRING);
    $font_size = filter_input(INPUT_POST, 'font_size', FILTER_VALIDATE_INT);
    
    // Validate theme
    $allowed_themes = ['light', 'dark', 'system'];
    if (!in_array($theme, $allowed_themes)) {
        $theme = 'light';
    }
    
    // Validate color accent
    $allowed_colors = ['blue', 'purple', 'green', 'red', 'orange'];
    if (!in_array($color_accent, $allowed_colors)) {
        $color_accent = 'blue';
    }
    
    // Validate font size
    if ($font_size < 12 || $font_size > 20) {
        $font_size = 16;
    }
    
    // Update settings in database
    $stmt = $conn->prepare("UPDATE user_settings SET theme = ?, color_accent = ?, font_size = ? WHERE user_id = ?");
    $stmt->bind_param("ssis", $theme, $color_accent, $font_size, $user_id);
    $stmt->execute();
}

// Function to handle quiz preferences form submission
function handleQuizPreferencesForm($conn, $user_id) {
    if (!$user_id) return;
    
    $default_category = filter_input(INPUT_POST, 'default_category', FILTER_SANITIZE_STRING);
    $default_difficulty = filter_input(INPUT_POST, 'default_difficulty', FILTER_SANITIZE_STRING);
    $questions_per_quiz = filter_input(INPUT_POST, 'questions_per_quiz', FILTER_VALIDATE_INT);
    $show_timer = isset($_POST['show_timer']) ? 1 : 0;
    $show_progress = isset($_POST['show_progress']) ? 1 : 0;
    $immediate_feedback = isset($_POST['immediate_feedback']) ? 1 : 0;
    $allow_skipping = isset($_POST['allow_skipping']) ? 1 : 0;
    
    // Validate category
    $allowed_categories = ['any', 'science', 'history', 'math', 'literature', 'geography'];
    if (!in_array($default_category, $allowed_categories)) {
        $default_category = 'any';
    }
    
    // Validate difficulty
    $allowed_difficulties = ['easy', 'medium', 'hard', 'any'];
    if (!in_array($default_difficulty, $allowed_difficulties)) {
        $default_difficulty = 'medium';
    }
    
    // Validate questions per quiz
    $allowed_questions = [5, 10, 15, 20];
    if (!in_array($questions_per_quiz, $allowed_questions)) {
        $questions_per_quiz = 10;
    }
    
    // Update settings in database
    $stmt = $conn->prepare("UPDATE user_settings SET 
                           default_category = ?, 
                           default_difficulty = ?, 
                           questions_per_quiz = ?, 
                           show_timer = ?, 
                           show_progress = ?, 
                           immediate_feedback = ?, 
                           allow_skipping = ? 
                           WHERE user_id = ?");
                           
    $stmt->bind_param("ssiiiiii", 
                     $default_category, 
                     $default_difficulty, 
                     $questions_per_quiz, 
                     $show_timer, 
                     $show_progress, 
                     $immediate_feedback, 
                     $allow_skipping, 
                     $user_id);
                     
    $stmt->execute();
}

// Function to handle notifications form submission
function handleNotificationsForm($conn, $user_id) {
    if (!$user_id) return;
    
    $email_quiz_results = isset($_POST['email_quiz_results']) ? 1 : 0;
    $email_leaderboard = isset($_POST['email_leaderboard']) ? 1 : 0;
    $email_new_quizzes = isset($_POST['email_new_quizzes']) ? 1 : 0;
    $email_newsletter = isset($_POST['email_newsletter']) ? 1 : 0;
    $browser_quiz_results = isset($_POST['browser_quiz_results']) ? 1 : 0;
    $browser_leaderboard = isset($_POST['browser_leaderboard']) ? 1 : 0;
    $browser_new_quizzes = isset($_POST['browser_new_quizzes']) ? 1 : 0;
    
    // Update settings in database
    $stmt = $conn->prepare("UPDATE user_settings SET 
                           email_quiz_results = ?, 
                           email_leaderboard = ?, 
                           email_new_quizzes = ?, 
                           email_newsletter = ?, 
                           browser_quiz_results = ?, 
                           browser_leaderboard = ?, 
                           browser_new_quizzes = ? 
                           WHERE user_id = ?");
                           
    $stmt->bind_param("iiiiiiii", 
                     $email_quiz_results, 
                     $email_leaderboard, 
                     $email_new_quizzes, 
                     $email_newsletter, 
                     $browser_quiz_results, 
                     $browser_leaderboard, 
                     $browser_new_quizzes, 
                     $user_id);
                     
    $stmt->execute();
}

// Function to handle account form submission
function handleAccountForm($conn, $user_id) {
    if (!$user_id) return;
    
    // Check if password update is requested
    if (!empty($_POST['current_password']) && !empty($_POST['new_password']) && !empty($_POST['confirm_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Verify passwords match
        if ($new_password !== $confirm_password) {
            throw new Exception("New passwords do not match");
        }
        
        // Verify current password
        $stmt = $conn->prepare("SELECT password FROM users WHERE user_id = ?");
        $stmt->bind_param("s", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user_data = $result->fetch_assoc();
            
            if (!password_verify($current_password, $user_data['password'])) {
                throw new Exception("Current password is incorrect");
            }
            
            // Update password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
            $stmt->bind_param("ss", $hashed_password, $user_id);
            $stmt->execute();
        }
    }
    
    // Update privacy settings
    $save_quiz_history = isset($_POST['save_quiz_history']) ? 1 : 0;
    $share_activity = isset($_POST['share_activity']) ? 1 : 0;
    $personalized_content = isset($_POST['personalized_content']) ? 1 : 0;
    $analytics_cookies = isset($_POST['analytics_cookies']) ? 1 : 0;
    
    // Update settings in database
    $stmt = $conn->prepare("UPDATE user_settings SET 
                           save_quiz_history = ?, 
                           share_activity = ?, 
                           personalized_content = ?, 
                           analytics_cookies = ? 
                           WHERE user_id = ?");
                           
    $stmt->bind_param("iiiis", 
                     $save_quiz_history, 
                     $share_activity, 
                     $personalized_content, 
                     $analytics_cookies, 
                     $user_id);
                     
    $stmt->execute();
    
    // Handle danger zone actions
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'delete_history':
                $stmt = $conn->prepare("DELETE FROM quiz_attempts WHERE user_id = ?");
                $stmt->bind_param("s", $user_id);
                $stmt->execute();
                break;
                
            case 'delete_account':
                // Delete user data from all tables
                $conn->begin_transaction();
                
                try {
                    // Delete quiz attempts
                    $stmt = $conn->prepare("DELETE FROM quiz_attempts WHERE user_id = ?");
                    $stmt->bind_param("s", $user_id);
                    $stmt->execute();
                    
                    // Delete user settings
                    $stmt = $conn->prepare("DELETE FROM user_settings WHERE user_id = ?");
                    $stmt->bind_param("s", $user_id);
                    $stmt->execute();
                    
                    // Delete user
                    $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
                    $stmt->bind_param("s", $user_id);
                    $stmt->execute();
                    
                    $conn->commit();
                    
                    // Destroy session
                    session_destroy();
                    
                    // Redirect to home page
                    header("Location: ../index.php");
                    exit;
                } catch (Exception $e) {
                    $conn->rollback();
                    throw $e;
                }
                break;

