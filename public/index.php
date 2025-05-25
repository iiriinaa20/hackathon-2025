<?php

declare(strict_types=1);

// initializing class autoloader
require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use App\Kernel;

// loading .env file variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// var_dump($_ENV['DB_PATH']);
// exit;

// creating and running web application
$app = Kernel::createApp();
$app->run();
