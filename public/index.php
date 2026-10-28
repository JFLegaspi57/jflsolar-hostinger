<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode... (Hostinger Git deployment keeps
// storage in ../../jflsolar-data next to public_html; see bootstrap/app.php)
// Same precedence as bootstrap/app.php: the first folder with a .env holds the storage.
$storage = __DIR__.'/../storage';
foreach ([__DIR__.'/../../jflsolar-data', __DIR__.'/../../jflsolar-app'] as $data) {
    if (is_file($data.'/.env')) {
        $storage = $data.'/storage';
        break;
    }
}
if (file_exists($maintenance = $storage.'/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
