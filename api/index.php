<?php

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
