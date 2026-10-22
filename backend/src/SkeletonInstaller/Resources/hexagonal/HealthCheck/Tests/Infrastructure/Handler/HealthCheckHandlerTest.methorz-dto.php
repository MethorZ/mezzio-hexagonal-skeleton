<?php

declare(strict_types=1);

namespace HealthCheck\Tests\Infrastructure\Handler;

use HealthCheck\Application\Request\HealthCheckRequest;
use HealthCheck\Application\Service\HealthCheckService;
use HealthCheck\Infrastructure\Handler\HealthCheckHandler;
use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\TestCase;

final class HealthCheckHandlerTest extends TestCase
{
    public function testReturnsOkStatusWithoutDetailsByDefault(): void
    {
        $handler = new HealthCheckHandler(new HealthCheckService());

        $response = $handler(new ServerRequest(), new HealthCheckRequest());
        $body = $response->jsonSerialize();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertIsArray($body);
        $this->assertSame('ok', $body['status']);
        $this->assertSame('1.0.0', $body['version']);
        $this->assertArrayHasKey('timestamp', $body);
        $this->assertArrayNotHasKey('details', $body);
    }

    public function testIncludesRuntimeDetailsWhenRequested(): void
    {
        $handler = new HealthCheckHandler(new HealthCheckService());

        $response = $handler(new ServerRequest(), new HealthCheckRequest(detailed: true));
        $body = $response->jsonSerialize();

        $this->assertIsArray($body);
        $this->assertIsArray($body['details']);
        $this->assertSame(PHP_VERSION, $body['details']['php_version']);
    }
}
