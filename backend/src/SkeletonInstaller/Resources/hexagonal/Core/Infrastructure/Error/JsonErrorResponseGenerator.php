<?php

declare(strict_types=1);

namespace Core\Infrastructure\Error;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

use function error_log;
use function explode;
use function is_int;
use function json_encode;
use function method_exists;
use function sprintf;

use const JSON_INVALID_UTF8_SUBSTITUTE;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;

/**
 * JSON Error Response Generator
 *
 * Renders uncaught exceptions for Mezzio's ErrorHandler as JSON.
 *
 * - Exceptions exposing getStatusCode() with a 4xx/5xx value keep that status
 *   (Core\Domain\Exception\DomainException defaults to 400); everything else becomes 500.
 * - Client errors (4xx) show the exception message.
 * - Server errors (5xx) are written to the PHP error log and only show details when `debug` is enabled.
 *
 * methorz/http-problem-details replaces this output with RFC 7807 responses when it is installed,
 * because its middleware runs inside the ErrorHandler.
 */
final readonly class JsonErrorResponseGenerator
{
    public function __construct(
        private bool $debug,
    ) {
    }

    public function __invoke(
        Throwable $exception,
        ServerRequestInterface $request,
        ResponseInterface $response,
    ): ResponseInterface {
        $status   = $this->resolveStatusCode($exception);
        $response = $response
            ->withStatus($status)
            ->withHeader('Content-Type', 'application/json');

        if ($status >= 500) {
            error_log(sprintf(
                "Uncaught %s: %s in %s:%d\nStack trace:\n%s",
                $exception::class,
                $exception->getMessage(),
                $exception->getFile(),
                $exception->getLine(),
                $exception->getTraceAsString(),
            ));
        }

        $payload = [
            'status'  => $status,
            'error'   => $response->getReasonPhrase(),
            'message' => $status < 500 || $this->debug
                ? $exception->getMessage()
                : 'An unexpected error occurred.',
        ];

        if ($this->debug) {
            $payload['exception'] = $exception::class;
            $payload['file']      = $exception->getFile();
            $payload['line']      = $exception->getLine();
            $payload['trace']     = explode("\n", $exception->getTraceAsString());
        }

        $response->getBody()->write(
            json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE),
        );

        return $response;
    }

    private function resolveStatusCode(Throwable $exception): int
    {
        if (! method_exists($exception, 'getStatusCode')) {
            return 500;
        }

        $status = $exception->getStatusCode();

        return is_int($status) && $status >= 400 && $status <= 599 ? $status : 500;
    }
}
