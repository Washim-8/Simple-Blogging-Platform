<?php
/**
 * Database Initialization Script
 * Run this once after deployment to create tables
 */

// Get DATABASE_URL from environment
$databaseUrl = getenv('DATABASE_URL');

if (!$databaseUrl) {
    die("ERROR: DATABASE_URL environment variable not set!\n");
}

echo "Connecting to database...\n";

try {
    // Parse database URL
    $dbParts = parse_url($databaseUrl);
    
    $host = $dbParts['host'];
    $port = isset($dbParts['port']) ? $dbParts['port'] : 5432;
    $dbname = ltrim($dbParts['path'], '/');
    $user = $dbParts['user'];
    $password = $dbParts['pass'];
    
    // Create DSN
    $dsn = "pgsql:host={$host};port={$port};dbname={$dbname}";
    
    // Connect
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    echo "Connected successfully!\n\n";
    
    // Read schema file
    $schemaFile = __DIR__ . '/db/schema.sql';
    if (!file_exists($schemaFile)) {
        die("ERROR: schema.sql file not found!\n");
    }
    
    $schema = file_get_contents($schemaFile);
    
    echo "Executing schema...\n";
    
    // Execute schema
    $pdo->exec($schema);
    
    echo "✓ Table 'posts' created successfully!\n";
    echo "✓ Indexes created successfully!\n\n";
    
    // Check if table exists
    $stmt = $pdo->query("SELECT COUNT(*) FROM posts");
    $count = $stmt->fetchColumn();
    
    echo "Current posts count: {$count}\n\n";
    
    // Ask if user wants to load sample data
    echo "Do you want to load sample data? (yes/no): ";
    $handle = fopen("php://stdin", "r");
    $answer = trim(fgets($handle));
    
    if (strtolower($answer) === 'yes' || strtolower($answer) === 'y') {
        $seedFile = __DIR__ . '/db/seed.sql';
        if (file_exists($seedFile)) {
            echo "\nLoading sample data...\n";
            $seed = file_get_contents($seedFile);
            $pdo->exec($seed);
            echo "✓ Sample data loaded successfully!\n";
            
            $stmt = $pdo->query("SELECT COUNT(*) FROM posts");
            $count = $stmt->fetchColumn();
            echo "Total posts: {$count}\n";
        } else {
            echo "ERROR: seed.sql file not found!\n";
        }
    }
    
    echo "\n✅ Database initialization complete!\n";
    echo "You can now deploy your application.\n";
    
} catch (PDOException $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
?>
