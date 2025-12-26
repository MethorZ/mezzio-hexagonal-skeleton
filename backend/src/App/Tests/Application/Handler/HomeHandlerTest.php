<?php

declare(strict_types=1);

namespace App\Tests\Application\Handler;

use App\Application\Handler\HomeHandler;
use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\TestCase;

final class HomeHandlerTest extends TestCase
{
    public function testHandleReturnsJsonResponse(): void
    {
        $handler = new HomeHandler();
        $request = new ServerRequest();

        $response = $handler->handle($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('application/json', $response->getHeaderLine('Content-Type'));

        $body = json_decode((string) $response->getBody(), true);
        $this->assertIsArray($body);
        $this->assertSame('app', $body['name']);
        $this->assertSame('healthy', $body['status']);
        $this->assertArrayHasKey('message', $body);
    }
}

