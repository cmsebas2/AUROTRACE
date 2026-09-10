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
            'maquila_items',
            'maquila_deliveries',
            'batch_record_archive_locations',
            'electronic_signatures',
            'maquiladores',
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
        echo "\n=== Columns of maquila_production_orders ===\n";
        try {
            $cols = $pdo->query("SELECT column_name, data_type FROM information_schema.columns WHERE table_name = 'maquila_production_orders' ORDER BY ordinal_position")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($cols as $c) {
                echo " - {$c['column_name']} ({$c['data_type']})\n";
            }
        } catch (\Throwable $e) {
            echo "Error checking columns: " . $e->getMessage() . "\n";
        }

        echo "\n=== Columns of maquila_items ===\n";
        try {
            $cols = $pdo->query("SELECT column_name, data_type FROM information_schema.columns WHERE table_name = 'maquila_items' ORDER BY ordinal_position")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($cols as $c) {
                echo " - {$c['column_name']} ({$c['data_type']})\n";
            }
        } catch (\Throwable $e) {
            echo "Error checking columns: " . $e->getMessage() . "\n";
        }

        echo "\n=== Applying Schema Patches & Dropping Check Constraints ===\n";
        try {
            $pdo->exec("ALTER TABLE \"maquila_production_orders\" DROP CONSTRAINT IF EXISTS \"maquila_production_orders_estado_check\"");
            $pdo->exec("ALTER TABLE \"maquila_production_orders\" DROP CONSTRAINT IF EXISTS \"maquila_production_orders_tipo_producto_check\"");
            $pdo->exec("ALTER TABLE \"maquila_production_orders\" ADD COLUMN IF NOT EXISTS \"unidad_medida\" VARCHAR(20) DEFAULT 'KG'");
            $pdo->exec("ALTER TABLE \"maquila_production_orders\" ADD COLUMN IF NOT EXISTS \"vigencia_meses\" INTEGER DEFAULT 24");
            $pdo->exec("ALTER TABLE \"maquila_production_orders\" ADD COLUMN IF NOT EXISTS \"fecha_destruccion_br\" VARCHAR(20)");
            $pdo->exec("ALTER TABLE \"maquila_production_orders\" ADD COLUMN IF NOT EXISTS \"lead_time_dias\" INTEGER DEFAULT 0");
            $pdo->exec("ALTER TABLE \"maquila_production_orders\" ALTER COLUMN \"estado\" TYPE VARCHAR(60)");
            echo " - maquila_production_orders: estado_check dropped & columns verified/added.\n";
        } catch (\Throwable $e) {
            echo " - Error patching maquila_production_orders: " . $e->getMessage() . "\n";
        }

        try {
            $pdo->exec("ALTER TABLE \"maquila_items\" DROP CONSTRAINT IF EXISTS \"maquila_items_unidad_medida_check\"");
            $pdo->exec("ALTER TABLE \"maquila_items\" ALTER COLUMN \"unidad_medida\" TYPE VARCHAR(30) USING \"unidad_medida\"::text");
            $pdo->exec("ALTER TABLE \"maquila_items\" ADD COLUMN IF NOT EXISTS \"forma_farmaceutica\" VARCHAR(100)");
            $pdo->exec("ALTER TABLE \"maquila_items\" ADD COLUMN IF NOT EXISTS \"esm\" VARCHAR(100)");
            echo " - maquila_items: unidad_medida_check dropped & updated to VARCHAR(30).\n";
        } catch (\Throwable $e) {
            echo " - Error patching maquila_items: " . $e->getMessage() . "\n";
        }

        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("
                INSERT INTO \"maquila_production_orders\" 
                (numero_odm, pre_orden, op, lote, producto_nombre, forma_farmaceutica, tamano_lote, unidad_medida, fecha_creacion, fecha_fabricacion, fecha_vencimiento, fecha_destruccion_br, vigencia_meses, maquilador_id, estado, usuario_creador_id, created_at, updated_at)
                VALUES ('ODM-TEST-DRYRUN', 'PL-TEST-G', 'OP-DRYRUN', 'LOT-DRYRUN', 'TEST PROD', 'SOLUCION', 100, 'L', '2023-01-01', '2023-01', '2025-01', '2026-01', 24, 4, 'OP CREADA', 1, NOW(), NOW())
                RETURNING id
            ");
            $stmt->execute();
            $testId = $stmt->fetchColumn();
            $pdo->rollBack();
            echo " - SUCCESS: Dry-run insert with estado 'OP CREADA' passed without check violation! (Test ID generated: $testId)\n";
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            echo " - ERROR in dry-run insert with OP CREADA: " . $e->getMessage() . "\n";
        }

        try {
            $pdo->exec("ALTER TABLE \"maquila_deliveries\" DROP CONSTRAINT IF EXISTS \"maquila_deliveries_tipo_entrega_check\"");
            echo " - maquila_deliveries: tipo_entrega_check dropped.\n";
        } catch (\Throwable $e) {}

        echo "\n=== Users in DB ===\n";
        try {
            $users = $pdo->query("SELECT id, name, email, role FROM \"users\" LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($users as $u) {
                echo " - User [{$u['id']}]: {$u['name']} ({$u['email']}, {$u['role']})\n";
            }
        } catch (\Throwable $e) {
            echo " - Error checking users: " . $e->getMessage() . "\n";
        }

        echo "\n=== Sanitizing Maquiladores ===\n";
        try {
            $deleted = $pdo->exec("DELETE FROM \"maquiladores\" WHERE \"nombre\" ~ '^[0-9]' OR \"nombre\" IN ('4 MILLONES', '24 G', '5 ML', '5 KG', '200 L')");
            echo "Deleted $deleted invalid date/unit records from maquiladores.\n";
            
            $rows = $pdo->query("SELECT id, nombre, nit, activo, certificado_bpm_ica_vigente_hasta FROM \"maquiladores\" ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
            echo "Clean Maquiladores Count: " . count($rows) . "\n";
            foreach ($rows as $r) {
                echo " - [ID: {$r['id']}] {$r['nombre']} (NIT: {$r['nit']})\n";
            }
        } catch (\Throwable $e) {
            echo "Error cleaning maquiladores: " . $e->getMessage() . "\n";
        }
        echo "\n";

        if (isset($_GET['sync_items'])) {
            echo "=== Syncing 1,954 Master Items to Database ===\n";
            $jsonFile = __DIR__ . '/../app/Data/user_items.json';
            if (file_exists($jsonFile)) {
                $itemsData = json_decode(file_get_contents($jsonFile), true);
                if (is_array($itemsData)) {
                    $stmt = $pdo->prepare("
                        INSERT INTO \"items\" (item_code, description, inventory_uom, is_purchased, is_sold, is_manufactured, created_at, updated_at)
                        VALUES (?, ?, 'UND', true, true, true, NOW(), NOW())
                        ON CONFLICT (item_code) DO UPDATE SET description = EXCLUDED.description, updated_at = NOW()
                    ");
                    $synced = 0;
                    $pdo->beginTransaction();
                    foreach ($itemsData as $code => $desc) {
                        $stmt->execute([(string)$code, (string)$desc]);
                        $synced++;
                    }
                    $pdo->commit();
                    echo "SUCCESS: Synced $synced items into database.\n";
                }
            } else {
                echo "File app/Data/user_items.json not found.\n";
            }
            echo "\n";
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
