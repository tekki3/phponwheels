<?php

declare(strict_types=1);

/**
 * Production entrypoint for FrankenPHP, nginx+PHP-FPM and Apache.
 *
 * The adapter is chosen from the runtime; the application above it is the same
 * object in every case. Point the document root at this directory.
 */

use PhpOnWheels\Kernel\Application;
use PhpOnWheels\Sapi\FpmSapi;
use PhpOnWheels\Sapi\FrankenPhpSapi;

require dirname(__DIR__) . '/vendor/autoload.php';

/** @var Application $app */
$app = require dirname(__DIR__) . '/app/bootstrap.php';

$sapi = FrankenPhpSapi::isAvailable() ? new FrankenPhpSapi() : new FpmSapi();

$sapi->run($app);
