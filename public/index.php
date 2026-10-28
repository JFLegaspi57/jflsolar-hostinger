<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode... (Hostinger Git deployment keeps
// storage in ../../jflsolar-data next to public_html; see bootstrap/app.php)
if (file_exists($maintenance = __DIR__.'/../../jflsolar-data/storage/framework/maintenance.php')
    || file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
