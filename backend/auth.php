<?php
session_start();
$error = "";
$success = "";
$activeTab = "login"; // default tab

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Database connection parameters
    $dbHost     = "localhost";
    $dbName     = "quizwhiz";
    $dbUser     = "root";
    $dbPassword = ""; // update if you have a DB password

    // Set MySQLi to throw exceptions for error handling
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    try {
        // Create connection with proper charset
        $conn = new mysqli($dbHost, $dbUser, $dbPassword, $dbName);
        $conn->set_charset("utf8mb4");

        // Check if this is a Signup (when the 'name' field is present)
        if (isset($_POST['name'])) {
            $activeTab = "signup";
            $name = trim($_POST['name']);
            $email = trim($_POST['email']);
            $passwd = $_POST['password'];
            $confirmPassword = $_POST['confirm_password'];

            // Validate that password and confirmation match
            if ($passwd !== $confirmPassword) {
                throw new Exception("Passwords do not match.");
            }

            // Check if the email already exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $error = "Email already exists. Please try logging in.";
            } else {
                $stmt->close();
                // Hash the password securely
                $hashedPassword = password_hash($passwd, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $name, $email, $hashedPassword);

                if ($stmt->execute()) {
                    $success = "Signup successful! You can now log in.";
                    // Optionally set the login tab active after successful signup
                    $activeTab = "dashboard";
                } else {
                    throw new Exception("Signup failed. Please try again later.");
                }
            }
            $stmt->close();

        // Otherwise, process a Login request
        } elseif (isset($_POST['email']) && isset($_POST['password'])) {
            $activeTab = "login";
            $email = trim($_POST['email']);
            $passwd = $_POST['password'];

            $stmt = $conn->prepare("SELECT id, name, password FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows === 0) {
                $error = "User not found. Please sign up.";
            } else {
                $stmt->bind_result($id, $name, $hashedPassword);
                $stmt->fetch();
                if (password_verify($passwd, $hashedPassword)) {
                    $_SESSION['user_id']   = $id;
                    $_SESSION['user_name'] = $name;
                    $success = "Login successful!";
                    // Here you might perform a redirect
                    // header("Location: dashboard.php");
                    // exit;
                } else {
                    $error = "Incorrect password.";
                }
            }
            $stmt->close();
        } else {
            throw new Exception("Invalid form submission.");
        }
    } catch (Exception $e) {
        // In production, you might log $e->getMessage() rather than display it directly
        $error = "Error: " . $e->getMessage();
    }
    if (isset($conn)) {
        $conn->close();
    }
}
?>
