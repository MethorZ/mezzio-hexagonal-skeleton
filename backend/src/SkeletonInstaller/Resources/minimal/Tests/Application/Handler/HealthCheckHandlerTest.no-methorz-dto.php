<?php

declare(strict_types=1);

namespace App\Tests\Application\Handler;

use App\Application\Handler\HealthCheckHandler;
use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\TestCase;

final class HealthCheckHandlerTest extends TestCase
{
    public function testReturnsOkStatusWithoutDetailsByDefault(): void
    {
        $response = (new HealthCheckHandler())->handle(new ServerRequest());
        $body = json_decode((string) $response->getBody(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('application/json', $response->getHeaderLine('Content-Type'));
        $this->assertIsArray($body);
        $this->assertSame('ok', $body['status']);
        $this->assertSame('1.0.0', $body['version']);
        $this->assertArrayHasKey('timestamp', $body);
        $this->assertArrayNotHasKey('details', $body);
    }

    public function testIncludesRuntimeDetailsWhenRequested(): void
    {
        $request = (new ServerRequest())->withQueryParams(['detailed' => 'true']);

        $response = (new HealthCheckHandler())->handle($request);
        $body = json_decode((string) $response->getBody(), true);

        $this->assertIsArray($body);
        $this->assertIsArray($body['details']);
        $this->assertSame(PHP_VERSION, $body['details']['php_version']);
    }
}
