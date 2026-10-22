<?php

declare(strict_types=1);

namespace Core\Tests\Infrastructure;

use Laminas\Diactoros\ServerRequest;
use Mezzio\Application;
use Mezzio\MiddlewareFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

/**
 * Boots the real container, pipeline and routes to catch wiring errors.
 */
final class ApplicationPipelineTest extends TestCase
{
    private string $previousDirectory;
    private Application $app;

    protected function setUp(): void
    {
        $directory = getcwd();
        $this->assertIsString($directory);
        $this->previousDirectory = $directory;

        // Config paths are relative to the backend directory, as in public/index.php
        chdir(dirname(__DIR__, 4));

        /** @var ContainerInterface $container */
        $container = require 'config/container.php';
        $app       = $container->get(Application::class);
        $factory   = $container->get(MiddlewareFactory::class);
        $this->assertInstanceOf(Application::class, $app);
        $this->assertInstanceOf(MiddlewareFactory::class, $factory);

        (require 'config/pipeline.php')($app, $factory, $container);
        (require 'config/routes.php')($app, $factory, $container);

        $this->app = $app;
    }

    protected function tearDown(): void
    {
        chdir($this->previousDirectory);
    }

    public function testServesTheHealthEndpoint(): void
    {
        $response = $this->app->handle(new ServerRequest(uri: '/health', method: 'GET'));

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testRendersUncaughtExceptionsWithTheirStatusCode(): void
    {
        $this->app->get('/pipeline-test/not-found', new class implements MiddlewareInterface {
            public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
            {
                throw new class ('Thing not found') extends RuntimeException {
                    public function getStatusCode(): int
                    {
                        return 404;
                    }
                };
            }
        });

        $response = $this->app->handle(new ServerRequest(uri: '/pipeline-test/not-found', method: 'GET'));

        $this->assertSame(404, $response->getStatusCode());
        $this->assertStringContainsString('json', $response->getHeaderLine('Content-Type'));
        $this->assertStringContainsString('Thing not found', (string) $response->getBody());
    }
}
