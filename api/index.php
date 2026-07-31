<?php

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// Raw DB Diagnostic Endpoint
if (isset($_SERVER['REQUEST_URI']) && (strpos($_SERVER['REQUEST_URI'], '/test-db') !== false)) {
    header('Content-Type: text/plain; charset=utf-8');
    
    $host = getenv('DB_HOST') ?: ($_ENV['DB_HOST'] ?? 'not set');
    $port = getenv('DB_PORT') ?: ($_ENV['DB_PORT'] ?? 'not set');
    $db = getenv('DB_DATABASE') ?: ($_ENV['DB_DATABASE'] ?? 'not set');
    $user = getenv('DB_USERNAME') ?: ($_ENV['DB_USERNAME'] ?? 'not set');
    $pass = getenv('DB_PASSWORD') ?: ($_ENV['DB_PASSWORD'] ?? 'not set');
    
    echo "=== AUROTRACE Database Connection Test ===\n";
    echo "Host: $host\n";
    echo "Port: $port\n";
    echo "Database: $db\n";
    echo "User: $user\n";
    echo "Password status: " . ($pass !== 'not set' ? 'Configured (Hidden)' : 'Not configured') . "\n\n";
    
    $extensions = get_loaded_extensions();
    echo "Is pdo_pgsql loaded? " . (in_array('pdo_pgsql', $extensions) ? 'YES' : 'NO') . "\n";
    echo "Is pdo_sqlite loaded? " . (in_array('pdo_sqlite', $extensions) ? 'YES' : 'NO') . "\n\n";
    
    try {
        echo "Attempting to connect to Supabase (PostgreSQL)...\n";
        $dsn = "pgsql:host=$host;port=$port;dbname=$db";
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5
        ]);
        echo "SUCCESS: Connected to database successfully!\n\n";
        
        $stmt = $pdo->query("SELECT tablename FROM pg_catalog.pg_tables WHERE schemaname = 'public'");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "Found " . count($tables) . " tables in public schema:\n";
        foreach ($tables as $t) {
            echo " - $t\n";
        }
    } catch (\Throwable $e) {
        echo "CONNECTION FAILED: " . $e->getMessage() . "\n";
    }
    exit;
}

try {
    // Check vendor autoloader
    if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
        throw new \Exception("The vendor/autoload.php file was not found. Ensure Composer dependencies are installed.");
    }

    // Remove any stale bootstrap cache files generated on local environment
    @unlink(__DIR__ . '/../bootstrap/cache/packages.php');
    @unlink(__DIR__ . '/../bootstrap/cache/services.php');
    @unlink(__DIR__ . '/../bootstrap/cache/config.php');
    @unlink(__DIR__ . '/../bootstrap/cache/routes-v7.php');

    // Prepare storage directory structure in /tmp for Vercel Serverless execution
    $storagePath = '/tmp/storage';
    $dirs = [
        $storagePath . '/framework/views',
        $storagePath . '/framework/sessions',
        $storagePath . '/framework/cache/data',
        $storagePath . '/bootstrap/cache',
        $storagePath . '/logs',
    ];

    foreach ($dirs as $dir) {
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
    }

    putenv('APP_STORAGE=' . $storagePath);
    $_ENV['APP_STORAGE'] = $storagePath;
    $_SERVER['APP_STORAGE'] = $storagePath;

    // Check database connection configuration
    $dbConnection = getenv('DB_CONNECTION') ?: ($_ENV['DB_CONNECTION'] ?? null);

    if (empty($dbConnection) || $dbConnection === 'sqlite') {
        // Fallback to SQLite in /tmp if no database parameters are provided in environment
        $dbTarget = '/tmp/database.sqlite';
        if (!file_exists($dbTarget)) {
            @touch($dbTarget);
        }

        putenv('DB_CONNECTION=sqlite');
        $_ENV['DB_CONNECTION'] = 'sqlite';
        $_SERVER['DB_CONNECTION'] = 'sqlite';

        putenv('DB_DATABASE=' . $dbTarget);
        $_ENV['DB_DATABASE'] = $dbTarget;
        $_SERVER['DB_DATABASE'] = $dbTarget;
    }

    // Ensure default APP_KEY exists if not set in Vercel environment settings
    if (empty(getenv('APP_KEY')) && empty($_ENV['APP_KEY'])) {
        $defaultKey = 'base64:3qZ8HwJ6XbN+4K9vM1Lp7R2tY5uW0xS8vE6yQ1zA4cB=';
        putenv('APP_KEY=' . $defaultKey);
        $_ENV['APP_KEY'] = $defaultKey;
        $_SERVER['APP_KEY'] = $defaultKey;
    }

    require __DIR__ . '/../public/index.php';
} catch (\Throwable $e) {
    http_response_code(500);
    header('Content-Type: text/html; charset=utf-8');
    echo "<h2>AUROTRACE Vercel Diagnostic Error</h2>";
    echo "<p><strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . " (Line " . $e->getLine() . ")</p>";
    echo "<pre style='background:#f4f4f4;padding:15px;border-radius:5px;overflow:auto;'>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
