<?php

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

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

    // Prepare SQLite database file in /tmp
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
