<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/database.php';

try {
    echo "<h1>Database Setup</h1>";
    
    // Get database connection
    $conn = getDBConnection();
    echo "<p>✓ Database connection successful</p>";
    
    // Read and execute SQL files
    $sqlFiles = [
        'quiz_tables.sql',
        'sample_data.sql'
    ];
    
    foreach ($sqlFiles as $file) {
        echo "<h2>Processing $file</h2>";
        $sql = file_get_contents(__DIR__ . '/' . $file);
        
        if ($sql === false) {
            throw new Exception("Failed to read $file");
        }
        
        // Split SQL into individual statements
        $statements = array_filter(
            array_map('trim', explode(';', $sql)),
            function($stmt) { return !empty($stmt); }
        );
        
        foreach ($statements as $statement) {
            try {
                $result = $conn->query($statement);
                echo "<p>✓ Executed: " . substr($statement, 0, 100) . "...</p>";
            } catch (Exception $e) {
                echo "<p style='color: red'>✗ Error executing statement: " . $e->getMessage() . "</p>";
                echo "<pre>" . $statement . "</pre>";
            }
        }
    }
    
    // Verify data
    echo "<h2>Verifying Data</h2>";
    
    $tables = ['users', 'quizzes', 'questions', 'answers'];
    foreach ($tables as $table) {
        $result = $conn->query("SELECT COUNT(*) as count FROM $table");
        $count = $result->fetch_assoc()['count'];
        echo "<p>✓ Table '$table' has $count records</p>";
    }
    
    echo "<h2>Setup Complete!</h2>";
    echo "<p>You can now <a href='../pages/explore.html'>go to the explore page</a> to start using the application.</p>";
    
} catch (Exception $e) {
    echo "<h1 style='color: red'>Error</h1>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
} 