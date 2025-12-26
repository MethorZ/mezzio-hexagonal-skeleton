<?php

declare(strict_types=1);

// Delegate static file requests back to the PHP built-in webserver
if (PHP_SAPI === 'cli-server' && $_SERVER['SCRIPT_FILENAME'] !== __FILE__) {
    return false;
}

chdir(dirname(__DIR__));
require 'vendor/autoload.php';

use Mezzio\Application;
use Mezzio\MiddlewareFactory;
use Psr\Container\ContainerInterface;

/**
 * Self-called anonymous function that creates its own scope and keeps the global namespace clean.
 */
(function () {
    try {
        /** @var ContainerInterface $container */
        $container = require 'config/container.php';

        /** @var Application $app */
        $app     = $container->get(Application::class);
        $factory = $container->get(MiddlewareFactory::class);

        // Execute programmatic/declarative middleware pipeline and routing
        // configuration statements
        (require 'config/pipeline.php')($app, $factory, $container);
        (require 'config/routes.php')($app, $factory, $container);

        $app->run();
    } catch (Throwable $exception) {
        // LAST RESORT - catches anything that happens before middleware can handle it
        // This includes: container errors, config errors, missing dependencies, etc.

        // Log to file directly since we can't rely on the logger being available
        $logDir = __DIR__ . '/../data/logs';
        if (! is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $logFile   = $logDir . '/emergency.log';
        $timestamp = date('Y-m-d H:i:s');
        $message   = sprintf(
            "[%s] EMERGENCY: %s in %s:%d\nStack trace:\n%s\n\n",
            $timestamp,
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getTraceAsString(),
        );

        file_put_contents($logFile, $message, FILE_APPEND);

        // Return appropriate response based on environment
        http_response_code(500);
        header('Content-Type: application/json');

        if (getenv('APP_ENV') === 'production') {
            // Production: generic error message
            echo json_encode([
                'error'   => 'Internal Server Error',
                'message' => 'An unexpected error occurred.',
            ]);
        } else {
            // Development: show full error details
            echo json_encode([
                'error'   => 'Internal Server Error',
                'message' => $exception->getMessage(),
                'file'    => $exception->getFile(),
                'line'    => $exception->getLine(),
                'trace'   => explode("\n", $exception->getTraceAsString()),
            ]);
        }

        exit(1);
    }
})();
