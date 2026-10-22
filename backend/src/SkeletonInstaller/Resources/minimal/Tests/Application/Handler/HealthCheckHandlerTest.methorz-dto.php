<?php

declare(strict_types=1);

namespace App\Tests\Application\Handler;

use App\Application\Handler\HealthCheckHandler;
use App\Application\Request\HealthCheckRequest;
use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\TestCase;

final class HealthCheckHandlerTest extends TestCase
{
    public function testReturnsOkStatusWithoutDetailsByDefault(): void
    {
        $response = (new HealthCheckHandler())(new ServerRequest(), new HealthCheckRequest());
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
        $response = (new HealthCheckHandler())(new ServerRequest(), new HealthCheckRequest(detailed: true));
        $body = $response->jsonSerialize();

        $this->assertIsArray($body);
        $this->assertIsArray($body['details']);
        $this->assertSame(PHP_VERSION, $body['details']['php_version']);
    }
}
