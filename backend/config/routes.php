<?php

declare(strict_types=1);

use Fig\Http\Message\RequestMethodInterface;
use Mezzio\Application;
use Mezzio\MiddlewareFactory;
use Psr\Container\ContainerInterface;

/**
 * FastRoute route configuration
 *
 * Routes are configured in module ConfigProviders and loaded automatically.
 *
 * Module routes are registered via the 'routes' key in ConfigProvider:
 * - Minimal: App\Application\Config\ConfigProvider
 * - Hexagonal: HealthCheck\Application\Config\ConfigProvider, Article\Application\Config\ConfigProvider
 *
 * Each module defines routes with:
 *   - name: Route identifier
 *   - path: URL path with optional {param} placeholders
 *   - middleware: Handler class name
 *   - allowed_methods: Array of HTTP methods (use RequestMethodInterface constants)
 *
 * Global routes can still be added manually below if needed.
 */

return static function (Application $app, MiddlewareFactory $factory, ContainerInterface $container): void {
    // Load routes from configuration
    $config = $container->get('config');
    $routes = $config['routes'] ?? [];

    foreach ($routes as $route) {
        $path = $route['path'] ?? null;
        $middleware = $route['middleware'] ?? null;
        $methods = $route['allowed_methods'] ?? [RequestMethodInterface::METHOD_GET];
        $name = $route['name'] ?? null;

        if ($path === null || $middleware === null) {
            continue;
        }

        $app->route($path, $middleware, $methods, $name);
    }

    // Global routes can be added here manually if needed
    // Example: $app->get('/custom', CustomHandler::class, 'custom.route');
};
