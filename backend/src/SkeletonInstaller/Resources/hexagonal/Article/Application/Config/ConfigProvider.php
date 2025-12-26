<?php

declare(strict_types=1);

namespace Article\Application\Config;

use Article\Domain\Port\ArticleRepositoryInterface;
use Article\Infrastructure\Handler\CreateArticleHandler;
use Article\Infrastructure\Handler\DeleteArticleHandler;
use Article\Infrastructure\Handler\GetArticleHandler;
use Article\Infrastructure\Handler\ListArticlesHandler;
use Article\Infrastructure\Handler\UpdateArticleHandler;
use Article\Infrastructure\Repository\InMemoryArticleRepository;
use Fig\Http\Message\RequestMethodInterface;

/**
 * Article Module ConfigProvider
 *
 * Provides dependency injection configuration and routes for the Article module.
 * ReflectionBasedAbstractFactory is configured globally in config/autoload/global.php.
 */
class ConfigProvider
{
    /**
     * @return array<string, mixed>
     */
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
            'routes'       => $this->getRoutes(),
        ];
    }

    /**
     * Get dependency injection configuration
     *
     * @return array<string, array<string, string|callable|array<string>>>
     */
    public function getDependencies(): array
    {
        return [
            'aliases' => [
                // Use InMemoryArticleRepository by default
                // Switch to DbArticleRepository if you want database persistence
                ArticleRepositoryInterface::class => InMemoryArticleRepository::class,
            ],
            'factories' => [
                // Explicit factories only if needed
            ],
        ];
    }

    /**
     * Get route configuration
     *
     * All handlers are automatically wrapped by DtoHandlerAbstractFactory from methorz/http-dto.
     * Just use the class name directly - no need for DtoHandlerWrapperFactory::class . '::wrap:'.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getRoutes(): array
    {
        return [
            [
                'name'            => 'article.list',
                'path'            => '/articles',
                'middleware'      => ListArticlesHandler::class,
                'allowed_methods' => [RequestMethodInterface::METHOD_GET],
            ],
            [
                'name'            => 'article.create',
                'path'            => '/articles',
                'middleware'      => CreateArticleHandler::class,
                'allowed_methods' => [RequestMethodInterface::METHOD_POST],
            ],
            [
                'name'            => 'article.get',
                'path'            => '/articles/{id}',
                'middleware'      => GetArticleHandler::class,
                'allowed_methods' => [RequestMethodInterface::METHOD_GET],
            ],
            [
                'name'            => 'article.update',
                'path'            => '/articles/{id}',
                'middleware'      => UpdateArticleHandler::class,
                'allowed_methods' => [RequestMethodInterface::METHOD_PUT],
            ],
            [
                'name'            => 'article.delete',
                'path'            => '/articles/{id}',
                'middleware'      => DeleteArticleHandler::class,
                'allowed_methods' => [RequestMethodInterface::METHOD_DELETE],
            ],
        ];
    }
}

