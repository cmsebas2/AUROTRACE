<?php

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// Trim spaces/tabs from database environment variables to prevent copy-paste errors
$dbKeys = ['DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD', 'POSTGRES_HOST', 'POSTGRES_USER', 'POSTGRES_PASSWORD', 'POSTGRES_DATABASE', 'POSTGRES_URL', 'DATABASE_URL'];
foreach ($dbKeys as $key) {
    $val = getenv($key) ?: ($_ENV[$key] ?? $_SERVER[$key] ?? null);
    if (!empty($val)) {
        $trimmed = trim($val);
        putenv("$key=$trimmed");
        $_ENV[$key] = $trimmed;
        $_SERVER[$key] = $trimmed;
    }
}

// Parse PostgreSQL URL if provided (Vercel Supabase integration uses postgres:// or postgresql://)
$rawPgUrl = getenv('POSTGRES_URL') ?: (getenv('DATABASE_URL') ?: (getenv('DB_URL') ?: null));
if (!empty($rawPgUrl)) {
    $cleanUrl = preg_replace('/^postgres(ql)?:\/\//i', 'pgsql://', $rawPgUrl);
    putenv("DB_URL=$cleanUrl");
    $_ENV['DB_URL'] = $cleanUrl;
    $_SERVER['DB_URL'] = $cleanUrl;

    $parsed = parse_url(preg_replace('/^postgres(ql)?:\/\//i', 'http://', $rawPgUrl));
    if ($parsed) {
        if (isset($parsed['host']) && empty(getenv('DB_HOST'))) {
            putenv('DB_HOST=' . $parsed['host']);
            $_ENV['DB_HOST'] = $parsed['host'];
            $_SERVER['DB_HOST'] = $parsed['host'];
        }
        if (isset($parsed['port']) && empty(getenv('DB_PORT'))) {
            putenv('DB_PORT=' . $parsed['port']);
            $_ENV['DB_PORT'] = (string)$parsed['port'];
            $_SERVER['DB_PORT'] = (string)$parsed['port'];
        }
        if (isset($parsed['user']) && empty(getenv('DB_USERNAME'))) {
            putenv('DB_USERNAME=' . urldecode($parsed['user']));
            $_ENV['DB_USERNAME'] = urldecode($parsed['user']);
            $_SERVER['DB_USERNAME'] = urldecode($parsed['user']);
        }
        if (isset($parsed['pass']) && empty(getenv('DB_PASSWORD'))) {
            putenv('DB_PASSWORD=' . urldecode($parsed['pass']));
            $_ENV['DB_PASSWORD'] = urldecode($parsed['pass']);
            $_SERVER['DB_PASSWORD'] = urldecode($parsed['pass']);
        }
        if (isset($parsed['path']) && empty(getenv('DB_DATABASE'))) {
            $dbName = ltrim($parsed['path'], '/');
            putenv('DB_DATABASE=' . $dbName);
            $_ENV['DB_DATABASE'] = $dbName;
            $_SERVER['DB_DATABASE'] = $dbName;
        }
    }

    putenv('DB_CONNECTION=pgsql');
    $_ENV['DB_CONNECTION'] = 'pgsql';
    $_SERVER['DB_CONNECTION'] = 'pgsql';
}

$hasPostgresConfig = !empty($rawPgUrl) || getenv('DB_HOST') || getenv('POSTGRES_HOST');
if ($hasPostgresConfig) {
    putenv('DB_CONNECTION=pgsql');
    $_ENV['DB_CONNECTION'] = 'pgsql';
    $_SERVER['DB_CONNECTION'] = 'pgsql';
}

// Bypass database session/cache handlers on migration run to prevent bootstrap exceptions
if (isset($_SERVER['REQUEST_URI']) && (strpos($_SERVER['REQUEST_URI'], '/run-migrations') !== false)) {
    putenv('SESSION_DRIVER=cookie');
    $_ENV['SESSION_DRIVER'] = 'cookie';
    $_SERVER['SESSION_DRIVER'] = 'cookie';

    putenv('CACHE_STORE=array');
    $_ENV['CACHE_STORE'] = 'array';
    $_SERVER['CACHE_STORE'] = 'array';

    putenv('CACHE_DRIVER=array');
    $_ENV['CACHE_DRIVER'] = 'array';
    $_SERVER['CACHE_DRIVER'] = 'array';
}

// Raw DB Diagnostic Endpoint
if (isset($_SERVER['REQUEST_URI']) && (strpos($_SERVER['REQUEST_URI'], '/test-db') !== false)) {
    if (!isset($_GET['secret']) || $_GET['secret'] !== 'auromigrate2026') {
        http_response_code(403);
        exit('Forbidden');
    }
    header('Content-Type: text/plain; charset=utf-8');
    
    $host = getenv('DB_HOST') ?: (getenv('POSTGRES_HOST') ?: 'not set');
    $port = getenv('DB_PORT') ?: (getenv('POSTGRES_PORT') ?: '5432');
    $db = getenv('DB_DATABASE') ?: (getenv('POSTGRES_DATABASE') ?: 'postgres');
    $user = getenv('DB_USERNAME') ?: (getenv('POSTGRES_USER') ?: 'postgres');
    $pass = getenv('DB_PASSWORD') ?: (getenv('POSTGRES_PASSWORD') ?: 'not set');
    $url = getenv('DB_URL') ?: 'not set';
    
    echo "=== AUROTRACE Database Connection Test ===\n";
    echo "Host: $host\n";
    echo "Port: $port\n";
    echo "Database: $db\n";
    echo "User: $user\n";
    echo "URL Configured: " . ($url !== 'not set' ? 'YES' : 'NO') . "\n";
    echo "Password status: " . ($pass !== 'not set' ? 'Configured' : 'Not configured') . "\n\n";
    
    $extensions = get_loaded_extensions();
    echo "Is pdo_pgsql loaded? " . (in_array('pdo_pgsql', $extensions) ? 'YES' : 'NO') . "\n";
    echo "Is pdo_sqlite loaded? " . (in_array('pdo_sqlite', $extensions) ? 'YES' : 'NO') . "\n\n";
    
    try {
        echo "Attempting to connect to Supabase (PostgreSQL)...\n";
        $dsn = "pgsql:host=$host;port=$port;dbname=$db;sslmode=require";
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5
        ]);
        echo "SUCCESS: Connected to database successfully!\n\n";

        $tablesToCheck = [
            'products',
            'product_presentations',
            'formula_ingredients',
            'product_steps',
            'maquila_catalog_items',
            'production_orders',
            'maquila_production_orders',
            'maquila_order_items',
            'items'
        ];

        echo "=== Current Table Record Counts ===\n";
        foreach ($tablesToCheck as $tbl) {
            try {
                $count = $pdo->query("SELECT COUNT(*) FROM \"$tbl\"")->fetchColumn();
                echo " - $tbl: $count\n";
            } catch (\Throwable $e) {
                echo " - $tbl: error (" . $e->getMessage() . ")\n";
            }
        }
        echo "\n";

        $q = isset($_GET['q']) ? trim($_GET['q']) : '';
        if ($q) {
            $stmt = $pdo->prepare("SELECT item_code, description, reference, ext_1_detail, inventory_type FROM items WHERE item_code ILIKE :q OR description ILIKE :q LIMIT 20");
            $stmt->execute([':q' => "%$q%"]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "Search results for '$q':\n";
            print_r($results);
        } else {
            $stmt = $pdo->query("SELECT item_code, description, reference, ext_1_detail, inventory_type FROM items LIMIT 10");
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "First 10 items in table 'items':\n";
            print_r($results);
        }
        echo "\n";
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

    if (empty($dbConnection) && !$hasPostgresConfig) {
        // Fallback to SQLite in /tmp ONLY if no PostgreSQL parameters are provided
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
