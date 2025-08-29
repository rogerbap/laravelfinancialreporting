<?php
try {
    $pdo = new PDO(
        'mysql:host=127.0.0.1;port=3306;dbname=financial_reporting',
        'laravel',
        'secret',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "✅ Connection successful!\n";
    
    // Test query
    $stmt = $pdo->query('SELECT DATABASE() as db_name');
    $result = $stmt->fetch();
    echo "Connected to database: " . $result['db_name'] . "\n";
    
} catch (PDOException $e) {
    echo "❌ Connection failed: " . $e->getMessage() . "\n";
}