<?php

require_once __DIR__ . '/../app/autoload.php';

use App\Core\Router;
use App\Core\Request;
use App\Core\Env;

// Load Environment Variables
Env::load(__DIR__ . '/../.env');

// Auto-Install Database if missing
try { App\Core\Installer::checkAndInstall(); } catch (\Throwable $e) {}

// Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Initialize Router
$router = new Router(new Request());

// Load Routes
require_once __DIR__ . '/../routes/web.php';
require_once __DIR__ . '/../routes/api.php';

// Dispatch
$router->resolve();
