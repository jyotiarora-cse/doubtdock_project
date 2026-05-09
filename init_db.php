<?php
require_once 'config.php';

// 1. Connect without selecting database to ensure it exists
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 2. Create database if it doesn't exist
$dbName = DB_NAME;
$sql = "CREATE DATABASE IF NOT EXISTS $dbName";
if ($conn->query($sql) === TRUE) {
    echo "Database '$dbName' ready.<br>";
} else {
    die("Error creating database: " . $conn->error);
}

// 3. Select the database
$conn->select_db($dbName);

// 4. Read schema.sql
$schemaFile = 'schema.sql';
if (!file_exists($schemaFile)) {
    die("Schema file '$schemaFile' not found.");
}

$queries = file_get_contents($schemaFile);

// 5. Execute queries
if ($conn->multi_query($queries)) {
    do {
        // Store first result set
        if ($result = $conn->store_result()) {
            $result->free();
        }
        // Move to next result
    } while ($conn->more_results() && $conn->next_result());
    
    echo "Tables initialized successfully!<br>";
    echo "You can now <a href='login.php'>Login</a> or <a href='register.php'>Register</a>.";
} else {
    echo "Error initializing tables: " . $conn->error;
}

$conn->close();
?>
