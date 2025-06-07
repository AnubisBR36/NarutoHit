
<?php
// Database setup script
$host = 'localhost';
$user = 'root';
$password = '';

try {
    // Connect without specifying database first
    $pdo = new PDO("mysql:host=$host", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Read and execute SQL file
    $sql = file_get_contents('setup_database.sql');
    
    // Split into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            $pdo->exec($statement);
        }
    }
    
    echo "Database setup completed successfully!<br>";
    echo "You can now access the site at <a href='index.php'>index.php</a><br>";
    echo "Default admin user: admin / admin123";
    
} catch (PDOException $e) {
    echo "Error setting up database: " . $e->getMessage();
}
?>
