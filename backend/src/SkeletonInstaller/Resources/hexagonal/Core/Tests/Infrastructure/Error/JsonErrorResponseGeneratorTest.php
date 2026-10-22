<?php

declare(strict_types=1);

namespace Core\Tests\Infrastructure\Error;

use Core\Infrastructure\Error\JsonErrorResponseGenerator;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Throwable;

final class JsonErrorResponseGeneratorTest extends TestCase
{
    private string $errorLogFile;
    private string|false $previousErrorLog;

    protected function setUp(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'error-log-');
        $this->assertIsString($file);

        $this->errorLogFile     = $file;
        $this->previousErrorLog = ini_set('error_log', $file);
    }

    protected function tearDown(): void
    {
        ini_set('error_log', $this->previousErrorLog === false ? '' : $this->previousErrorLog);
        unlink($this->errorLogFile);
    }

    public function testUsesStatusCodeAndMessageOfClientErrors(): void
    {
        $exception = new class ('Article not found') extends RuntimeException {
            public function getStatusCode(): int
            {
                return 404;
            }
        };

        $body = $this->generate(new JsonErrorResponseGenerator(false), $exception, 404);

        $this->assertSame(['status' => 404, 'error' => 'Not Found', 'message' => 'Article not found'], $body);
        $this->assertSame('', (string) file_get_contents($this->errorLogFile));
    }

    public function testHidesServerErrorDetailsWithoutDebug(): void
    {
        $body = $this->generate(new JsonErrorResponseGenerator(false), new RuntimeException('SQLSTATE secret'), 500);

        $this->assertSame(
            ['status' => 500, 'error' => 'Internal Server Error', 'message' => 'An unexpected error occurred.'],
            $body,
        );
        $this->assertStringContainsString('SQLSTATE secret', (string) file_get_contents($this->errorLogFile));
    }

    public function testExposesServerErrorDetailsInDebugMode(): void
    {
        $body = $this->generate(new JsonErrorResponseGenerator(true), new RuntimeException('Boom'), 500);

        $this->assertSame('Boom', $body['message']);
        $this->assertSame(RuntimeException::class, $body['exception']);
        $this->assertSame(__FILE__, $body['file']);
        $this->assertIsInt($body['line']);
        $this->assertIsArray($body['trace']);
    }

    public function testFallsBackToServerErrorForStatusCodesOutsideTheErrorRange(): void
    {
        $exception = new class ('Not an error status') extends RuntimeException {
            public function getStatusCode(): int
            {
                return 200;
            }
        };

        $body = $this->generate(new JsonErrorResponseGenerator(false), $exception, 500);

        $this->assertSame(500, $body['status']);
    }

    /**
     * @return array<string, mixed>
     */
    private function generate(JsonErrorResponseGenerator $generator, Throwable $exception, int $expectedStatus): array
    {
        $response = $generator($exception, new ServerRequest(), new Response());

        $this->assertSame($expectedStatus, $response->getStatusCode());
        $this->assertSame('application/json', $response->getHeaderLine('Content-Type'));

        $body = json_decode((string) $response->getBody(), true, flags: JSON_THROW_ON_ERROR);
        $this->assertIsArray($body);

        /** @var array<string, mixed> $body */
        return $body;
    }
}
