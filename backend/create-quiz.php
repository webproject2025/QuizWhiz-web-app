<?php
// Disable output buffering to prevent issues with JSON responses
ob_end_clean();

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set headers to allow cross-origin requests and specify content type
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
header('Content-Type: application/json');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Start session
session_start();

// Function to log debug information
function debug_log($message) {
    $log_dir = __DIR__;
    $log_file = $log_dir . '/debug.log';
    file_put_contents($log_file, date('[Y-m-d H:i:s] ') . $message . PHP_EOL, FILE_APPEND);
}

debug_log("Script started with method: " . $_SERVER['REQUEST_METHOD']);
debug_log("Raw POST data: " . file_get_contents('php://input'));

// Database connection settings
$host = "localhost";
$username = "root"; 
$password = ""; 
$database = "quiz_system"; 

// Function to sanitize input data
function sanitize($data) {
    global $conn;
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    if (is_null($data)) {
        return '';
    }
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return isset($conn) ? $conn->real_escape_string($data) : $data;
}

// Function to generate unique ID
function generateUniqueId() {
    return uniqid('quiz_', true);
}

// For testing purposes, set a dummy user ID if not logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 'user_123';
    debug_log("Set dummy user_id: user_123");
}

try {
    // Connect to database
    $conn = new mysqli($host, $username, $password, $database);
    
    if ($conn->connect_error) {
        debug_log("Database connection failed: " . $conn->connect_error);
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }
    
    debug_log("Database connection successful");
    
    // Set character set
    $conn->set_charset("utf8mb4");
    
    // Process the request
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        debug_log("Processing POST request");
        
        // Get basic quiz information
        $quiz_id = isset($_POST['quiz_id']) && !empty($_POST['quiz_id']) ? sanitize($_POST['quiz_id']) : generateUniqueId();
        $user_id = $_SESSION['user_id'];
        $title = isset($_POST['quiz_title']) ? sanitize($_POST['quiz_title']) : '';
        $description = isset($_POST['quiz_description']) ? sanitize($_POST['quiz_description']) : '';
        $category = isset($_POST['quiz_category']) ? sanitize($_POST['quiz_category']) : '';
        $difficulty = isset($_POST['quiz_difficulty']) ? sanitize($_POST['quiz_difficulty']) : 'medium';
        $time_limit = isset($_POST['time_limit']) ? (int)$_POST['time_limit'] : 0;
        $passing_score = isset($_POST['passing_score']) ? (int)$_POST['passing_score'] : 70;
        
        // Get privacy setting
        $privacy = isset($_POST['privacy']) ? sanitize($_POST['privacy']) : 'public';
        $is_public = ($privacy === 'public') ? 1 : 0;
        
        // Get quiz options
        $randomize_questions = isset($_POST['randomize_questions']) ? 1 : 0;
        $randomize_options = isset($_POST['randomize_options']) ? 1 : 0;
        $show_correct_answers = isset($_POST['show_correct_answers']) ? 1 : 0;
        $show_explanations = isset($_POST['show_explanations']) ? 1 : 0;
        $allow_retakes = isset($_POST['allow_retakes']) ? 1 : 0;
        
        debug_log("Quiz basic info processed: ID=$quiz_id, Title=$title");
        
        // Process cover image
        $cover_image = "";
        if (isset($_FILES['quiz_cover']) && $_FILES['quiz_cover']['error'] == 0) {
            debug_log("Processing quiz cover image");
            
            $upload_dir = "uploads/";
            
            // Create upload directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                if (!mkdir($upload_dir, 0777, true)) {
                    debug_log("Failed to create upload directory: $upload_dir");
                    throw new Exception("Failed to create upload directory");
                }
            }
            
            $file_extension = strtolower(pathinfo($_FILES['quiz_cover']["name"], PATHINFO_EXTENSION));
            $allowed_extensions = ["jpg", "jpeg", "png", "gif"];
            
            if (in_array($file_extension, $allowed_extensions)) {
                $new_filename = $quiz_id . "_cover_" . time() . "." . $file_extension;
                $target_file = $upload_dir . $new_filename;
                
                debug_log("Attempting to move uploaded file to: $target_file");
                
                if (move_uploaded_file($_FILES['quiz_cover']["tmp_name"], $target_file)) {
                    $cover_image = $upload_dir . $new_filename;
                    debug_log("File uploaded successfully: $cover_image");
                } else {
                    debug_log("Failed to move uploaded file. Upload error code: " . $_FILES['quiz_cover']['error']);
                    debug_log("Upload tmp_name: " . $_FILES['quiz_cover']['tmp_name']);
                    debug_log("Target file: $target_file");
                    debug_log("PHP upload error: " . error_get_last()['message']);
                }
            } else {
                debug_log("Invalid file extension: $file_extension");
            }
        }
        
        // Check if the quizzes table exists, create it if not
        $result = $conn->query("SHOW TABLES LIKE 'quizzes'");
        if ($result->num_rows == 0) {
            debug_log("Creating quizzes table");
            $sql = "CREATE TABLE quizzes (
                quiz_id VARCHAR(50) PRIMARY KEY,
                user_id VARCHAR(50) NOT NULL,
                title VARCHAR(255) NOT NULL,
                description TEXT,
                category VARCHAR(50),
                difficulty VARCHAR(20),
                time_limit INT DEFAULT 0,
                passing_score INT DEFAULT 70,
                is_public TINYINT(1) DEFAULT 1,
                cover_image VARCHAR(255),
                randomize_questions TINYINT(1) DEFAULT 0,
                randomize_options TINYINT(1) DEFAULT 0,
                show_correct_answers TINYINT(1) DEFAULT 0,
                show_explanations TINYINT(1) DEFAULT 0,
                allow_retakes TINYINT(1) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
            
            if (!$conn->query($sql)) {
                debug_log("Failed to create quizzes table: " . $conn->error);
                throw new Exception("Failed to create quizzes table: " . $conn->error);
            }
        }
        
        // Check if the questions table exists, create it if not
        $result = $conn->query("SHOW TABLES LIKE 'questions'");
        if ($result->num_rows == 0) {
            debug_log("Creating questions table");
            $sql = "CREATE TABLE questions (
                question_id VARCHAR(50) PRIMARY KEY,
                quiz_id VARCHAR(50) NOT NULL,
                question_text TEXT NOT NULL,
                question_type VARCHAR(20) DEFAULT 'multiple_choice',
                points INT DEFAULT 1,
                explanation TEXT,
                question_image VARCHAR(255),
                question_order INT,
                FOREIGN KEY (quiz_id) REFERENCES quizzes(quiz_id) ON DELETE CASCADE
            )";
            
            if (!$conn->query($sql)) {
                debug_log("Failed to create questions table: " . $conn->error);
                throw new Exception("Failed to create questions table: " . $conn->error);
            }
        }
        
        // Check if the options table exists, create it if not
        $result = $conn->query("SHOW TABLES LIKE 'options'");
        if ($result->num_rows == 0) {
            debug_log("Creating options table");
            $sql = "CREATE TABLE options (
                option_id VARCHAR(50) PRIMARY KEY,
                question_id VARCHAR(50) NOT NULL,
                option_text TEXT NOT NULL,
                is_correct TINYINT(1) DEFAULT 0,
                option_order INT,
                FOREIGN KEY (question_id) REFERENCES questions(question_id) ON DELETE CASCADE
            )";
            
            if (!$conn->query($sql)) {
                debug_log("Failed to create options table: " . $conn->error);
                throw new Exception("Failed to create options table: " . $conn->error);
            }
        }
        
        // Insert quiz into database
        debug_log("Inserting quiz into database");
        
        // Check if quiz already exists
        $stmt = $conn->prepare("SELECT quiz_id FROM quizzes WHERE quiz_id = ?");
        $stmt->bind_param("s", $quiz_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Update existing quiz
            $stmt = $conn->prepare("UPDATE quizzes SET 
                title = ?, 
                description = ?, 
                category = ?, 
                difficulty = ?, 
                time_limit = ?, 
                passing_score = ?, 
                is_public = ?, 
                cover_image = ?,
                randomize_questions = ?,
                randomize_options = ?,
                show_correct_answers = ?,
                show_explanations = ?,
                allow_retakes = ?
                WHERE quiz_id = ?");
            
            $stmt->bind_param("ssssiissiiiiss", 
                $title, 
                $description, 
                $category, 
                $difficulty, 
                $time_limit, 
                $passing_score, 
                $is_public, 
                $cover_image,
                $randomize_questions,
                $randomize_options,
                $show_correct_answers,
                $show_explanations,
                $allow_retakes,
                $quiz_id);
        } else {
            // Insert new quiz
            $stmt = $conn->prepare("INSERT INTO quizzes (
                quiz_id, 
                user_id, 
                title, 
                description, 
                category, 
                difficulty, 
                time_limit, 
                passing_score, 
                is_public, 
                cover_image,
                randomize_questions,
                randomize_options,
                show_correct_answers,
                show_explanations,
                allow_retakes
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $stmt->bind_param("ssssssiisiiiii", 
                $quiz_id, 
                $user_id, 
                $title, 
                $description, 
                $category, 
                $difficulty, 
                $time_limit, 
                $passing_score, 
                $is_public, 
                $cover_image,
                $randomize_questions,
                $randomize_options,
                $show_correct_answers,
                $show_explanations,
                $allow_retakes);
        }
        
        if (!$stmt->execute()) {
            debug_log("Execute statement failed: " . $stmt->error);
            throw new Exception("Failed to save quiz: " . $stmt->error);
        }
        
        debug_log("Quiz saved successfully");
        
        // Process questions
        $questions_processed = 0;
        
        // Check if questions are provided as JSON
        if (isset($_POST['questions'])) {
            $questionsJson = $_POST['questions'];
            debug_log("Questions JSON received: " . substr($questionsJson, 0, 100) . "...");
            
            $questions = json_decode($questionsJson, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                debug_log("JSON decode error: " . json_last_error_msg() . " - Raw data: " . substr($questionsJson, 0, 200));
                throw new Exception("Invalid question data format: " . json_last_error_msg());
            }
            
            if (!is_array($questions)) {
                debug_log("Questions data is not an array");
                throw new Exception("Questions data is not in the expected format");
            }
            
            debug_log("Processing " . count($questions) . " questions from JSON");
            
            // Delete existing questions for this quiz
            $stmt = $conn->prepare("DELETE FROM questions WHERE quiz_id = ?");
            $stmt->bind_param("s", $quiz_id);
            $stmt->execute();
            
            foreach ($questions as $index => $question) {
                $question_id = generateUniqueId();
                
                if (!isset($question['text']) || empty($question['text'])) {
                    debug_log("Question text missing for index $index");
                    continue;
                }
                
                $question_text = sanitize($question['text']);
                $question_type = isset($question['type']) ? sanitize($question['type']) : 'multiple_choice';
                $points = isset($question['points']) ? (int)$question['points'] : 1;
                $explanation = isset($question['explanation']) ? sanitize($question['explanation']) : '';
                
                debug_log("Processing question $index: $question_text");
                
                // Process question image
                $question_image = "";
                $image_field_name = "question_image_$index";
                
                if (isset($_FILES[$image_field_name]) && $_FILES[$image_field_name]['error'] == 0) {
                    debug_log("Processing question image for question $index");
                    
                    $upload_dir = "uploads/";
                
                    if (!file_exists($upload_dir)) {
                        if (!mkdir($upload_dir, 0777, true)) {
                            debug_log("Failed to create upload directory: $upload_dir");
                        }
                    }
                    
                    $file_extension = strtolower(pathinfo($_FILES[$image_field_name]["name"], PATHINFO_EXTENSION));
                    $allowed_extensions = ["jpg", "jpeg", "png", "gif"];
                    if (in_array($file_extension, $allowed_extensions)) {
                        $new_filename = $question_id . "_" . time() . "." . $file_extension;
                        $target_file = $upload_dir . $new_filename;

                        if (move_uploaded_file($_FILES[$image_field_name]["tmp_name"], $target_file)) {
                            $question_image = $upload_dir . $new_filename;
                            debug_log("Question image uploaded: $question_image");
                        } else {
                            debug_log("Failed to move question image. Upload error code: " . $_FILES[$image_field_name]['error']);
                        }
                    } else {
                        debug_log("Invalid question image file extension: $file_extension");
                    }
                }
                
                // Insert question
                $question_order = $index + 1;
                
                $stmt = $conn->prepare("INSERT INTO questions (
                    question_id, 
                    quiz_id, 
                    question_text, 
                    question_type, 
                    points, 
                    explanation, 
                    question_image, 
                    question_order
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                
                if (!$stmt) {
                    debug_log("Prepare statement failed for question: " . $conn->error);
                    continue;
                }
                
                $stmt->bind_param("ssssissi", 
                    $question_id, 
                    $quiz_id, 
                    $question_text, 
                    $question_type, 
                    $points, 
                    $explanation, 
                    $question_image, 
                    $question_order);
                
                if (!$stmt->execute()) {
                    debug_log("Execute statement failed for question: " . $stmt->error);
                    continue;
                }
                
                debug_log("Question inserted successfully");
                $questions_processed++;
                
                // Process options
                $options_processed = 0;
                
                if (isset($question['options']) && is_array($question['options'])) {
                    debug_log("Processing " . count($question['options']) . " options for question $index");
                    $correct_option = isset($question['correct_option']) ? (int)$question['correct_option'] : -1;
                    
                    foreach ($question['options'] as $option_index => $option) {
                        $option_id = generateUniqueId();
                        
                        if (!isset($option['text']) || empty($option['text'])) {
                            debug_log("Option text missing for question $index, option $option_index");
                            continue;
                        }
                        
                        $option_text = sanitize($option['text']);
                        $is_correct = ($correct_option == $option_index) ? 1 : 0;
                        
                        debug_log("Processing option $option_index: $option_text (correct: $is_correct)");
                        $option_order = $option_index + 1;
                        
                        $stmt = $conn->prepare("INSERT INTO options (
                            option_id, 
                            question_id, 
                            option_text, 
                            is_correct, 
                            option_order
                        ) VALUES (?, ?, ?, ?, ?)");
                        
                        if (!$stmt) {
                            debug_log("Prepare statement failed for option: " . $conn->error);
                            continue;
                        }
                        
                        $stmt->bind_param("sssii", 
                            $option_id, 
                            $question_id, 
                            $option_text, 
                            $is_correct, 
                            $option_order);
                        
                        if (!$stmt->execute()) {
                            debug_log("Execute statement failed for option: " . $stmt->error);
                            continue;
                        }
                        
                        debug_log("Option inserted successfully");
                        $options_processed++;
                    }
                }
                
                debug_log("Processed $options_processed options for question $index");
            }
        } else {
            debug_log("No questions data found in the request");
        }
        
        debug_log("Processed $questions_processed questions total");
        
        // Return success response
        $response = [
            'status' => 'success', 
            'message' => 'Quiz created successfully', 
            'quiz_id' => $quiz_id,
            'questions_processed' => $questions_processed
        ];
        
        debug_log("Sending success response: " . json_encode($response));
        echo json_encode($response);
        
    } else if ($_SERVER["REQUEST_METHOD"] == "GET") {
        // Handle GET request (for testing)
        echo json_encode(['status' => 'success', 'message' => 'API is working']);
    } else {
        // Not a supported request method
        http_response_code(405); // Method Not Allowed
        echo json_encode(['status' => 'error', 'message' => 'Method not allowed. Only GET and POST requests are accepted.']);
    }
    
} catch (Exception $e) {
    debug_log("Exception: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
