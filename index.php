<?php

define('LARAVEL_START', microtime(true));

// Maintenance mode
if (file_exists(__DIR__.'/storage/framework/maintenance.php')) {
    require __DIR__.'/storage/framework/maintenance.php';
}

// Composer autoloader
require __DIR__.'/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__.'/bootstrap/app.php';

$app->handleRequest(Illuminate\Http\Request::capture());
