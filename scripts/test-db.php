<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

try {
    $pdo = new PDO(
        'mysql:host=127.0.0.1;port=3306;dbname=financial_reporting',
        'laravel',
        'secret'
    );
    echo "✅ Direct PDO connection successful!\n";
    
    $stmt = $pdo->query('SELECT VERSION()');
    $version = $stmt->fetchColumn();
    echo "MySQL version: " . $version . "\n";
    
} catch (PDOException $e) {
    echo "❌ Connection failed: " . $e->getMessage() . "\n";
}