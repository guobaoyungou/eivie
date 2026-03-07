<?php
define('ROOT_PATH', '/www/wwwroot/eivie/');
$config = include(ROOT_PATH.'config.php');
$pdo = new PDO('mysql:host='.$config['hostname'].';dbname='.$config['database'].';port='.$config['hostport'], $config['username'], $config['password']);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$sql = file_get_contents('/www/wwwroot/eivie/database/migrations/generation_module_tables.sql');

// Split by semicolon and filter CREATE statements
$statements = preg_split('/;(?=\s*(CREATE|--|\Z))/i', $sql);
foreach($statements as $stmt) {
    $stmt = trim($stmt);
    if (stripos($stmt, 'CREATE TABLE') !== false) {
        try {
            $pdo->exec($stmt);
            if (preg_match('/CREATE TABLE.*`(\w+)`/', $stmt, $m)) {
                echo "Created: " . $m[1] . PHP_EOL;
            }
        } catch(Exception $e) {
            echo "Error: " . $e->getMessage() . PHP_EOL;
        }
    }
}

// Verify tables exist
$r = $pdo->query("SHOW TABLES LIKE '%generation%'");
$tables = $r->fetchAll(PDO::FETCH_COLUMN);
echo "\nVerification - Tables found:\n";
foreach($tables as $t) {
    echo "  - $t\n";
}
echo "\nDone.\n";
