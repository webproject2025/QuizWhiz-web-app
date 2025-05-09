<?php
require_once 'config/database.php';

try {
    // Create database if it doesn't exist
    $pdo = new PDO("mysql:host=localhost:3308", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "Database created successfully\n";
    
    // Select the database
    $pdo->exec("USE " . DB_NAME);
    
    // Create tables
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            user_id INT PRIMARY KEY AUTO_INCREMENT,
            email VARCHAR(255) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "Users table created successfully\n";
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS quizzes (
            quiz_id INT PRIMARY KEY AUTO_INCREMENT,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            category VARCHAR(50),
            difficulty ENUM('easy', 'medium', 'hard'),
            created_by INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            total_questions INT DEFAULT 0,
            time_limit INT DEFAULT 0,
            is_published BOOLEAN DEFAULT true,
            FOREIGN KEY (created_by) REFERENCES users(user_id)
        )
    ");
    echo "Quizzes table created successfully\n";
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS questions (
            question_id INT PRIMARY KEY AUTO_INCREMENT,
            quiz_id INT,
            question_text TEXT NOT NULL,
            question_type ENUM('multiple_choice', 'true_false', 'short_answer'),
            points INT DEFAULT 1,
            question_order INT,
            FOREIGN KEY (quiz_id) REFERENCES quizzes(quiz_id) ON DELETE CASCADE
        )
    ");
    echo "Questions table created successfully\n";
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS answers (
            answer_id INT PRIMARY KEY AUTO_INCREMENT,
            question_id INT,
            answer_text TEXT NOT NULL,
            is_correct BOOLEAN DEFAULT false,
            FOREIGN KEY (question_id) REFERENCES questions(question_id) ON DELETE CASCADE
        )
    ");
    echo "Answers table created successfully\n";
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS quiz_attempts (
            attempt_id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT,
            quiz_id INT,
            start_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            end_time TIMESTAMP NULL,
            score INT DEFAULT 0,
            status ENUM('in_progress', 'completed', 'abandoned') DEFAULT 'in_progress',
            FOREIGN KEY (user_id) REFERENCES users(user_id),
            FOREIGN KEY (quiz_id) REFERENCES quizzes(quiz_id)
        )
    ");
    echo "Quiz attempts table created successfully\n";
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS user_answers (
            answer_id INT PRIMARY KEY AUTO_INCREMENT,
            attempt_id INT,
            question_id INT,
            selected_answer_id INT,
            is_correct BOOLEAN,
            answered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (attempt_id) REFERENCES quiz_attempts(attempt_id),
            FOREIGN KEY (question_id) REFERENCES questions(question_id),
            FOREIGN KEY (selected_answer_id) REFERENCES answers(answer_id)
        )
    ");
    echo "User answers table created successfully\n";
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS user_favorites (
            user_id INT,
            quiz_id INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (user_id, quiz_id),
            FOREIGN KEY (user_id) REFERENCES users(user_id),
            FOREIGN KEY (quiz_id) REFERENCES quizzes(quiz_id)
        )
    ");
    echo "User favorites table created successfully\n";
    
    // Insert some test data
    $stmt = $pdo->prepare("INSERT IGNORE INTO users (email, password_hash) VALUES (?, ?)");
    $stmt->execute(['test@example.com', password_hash('password123', PASSWORD_DEFAULT)]);
    echo "Test user created successfully\n";
    
    echo "Database setup completed successfully!\n";
    
} catch (PDOException $e) {
    die("Database setup failed: " . $e->getMessage() . "\n");
} 