-- Users table
CREATE TABLE IF NOT EXISTS users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Quizzes table
CREATE TABLE IF NOT EXISTS quizzes (
    quiz_id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    category VARCHAR(50),
    difficulty ENUM('easy', 'medium', 'hard'),
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    total_questions INT DEFAULT 0,
    time_limit INT DEFAULT 0, -- in minutes
    is_published BOOLEAN DEFAULT true,
    FOREIGN KEY (created_by) REFERENCES users(user_id)
);

-- Questions table
CREATE TABLE IF NOT EXISTS questions (
    question_id INT PRIMARY KEY AUTO_INCREMENT,
    quiz_id INT,
    question_text TEXT NOT NULL,
    question_type ENUM('multiple_choice', 'true_false', 'short_answer'),
    points INT DEFAULT 1,
    question_order INT,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(quiz_id) ON DELETE CASCADE
);

-- Answers table
CREATE TABLE IF NOT EXISTS answers (
    answer_id INT PRIMARY KEY AUTO_INCREMENT,
    question_id INT,
    answer_text TEXT NOT NULL,
    is_correct BOOLEAN DEFAULT false,
    FOREIGN KEY (question_id) REFERENCES questions(question_id) ON DELETE CASCADE
);

-- User Quiz Attempts table
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
);

-- User Answers table
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
); 