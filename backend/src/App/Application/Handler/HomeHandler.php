<?php

declare(strict_types=1);

namespace App\Application\Handler;

use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Home Handler - Default handler for the skeleton application
 *
 * Provides a simple JSON response to verify the application is running.
 */
class HomeHandler implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return new JsonResponse([
            'name'    => 'Health Check API',
            'status'  => 'healthy',
            'message' => 'The application is running and ready to build!',
        ]);
    }
}
