<?php

declare(strict_types=1);

namespace Article\Application\Config;

use Article\Domain\Port\ArticleRepositoryInterface;
use Article\Infrastructure\Handler\CreateArticleHandler;
use Article\Infrastructure\Handler\DeleteArticleHandler;
use Article\Infrastructure\Handler\GetArticleHandler;
use Article\Infrastructure\Handler\ListArticlesHandler;
use Article\Infrastructure\Handler\UpdateArticleHandler;
use Article\Infrastructure\Repository\FileArticleRepository;
use Article\Infrastructure\Repository\FileArticleRepositoryFactory;
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
            'article'      => [
                // JSON file used by FileArticleRepository, relative to the backend directory
                'storage_file' => 'data/articles.json',
            ],
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
                // FileArticleRepository keeps articles in data/articles.json across requests.
                // Switch to DbArticleRepository (requires methorz/swift-db) for database persistence;
                // InMemoryArticleRepository suits tests that must not touch the filesystem.
                ArticleRepositoryInterface::class => FileArticleRepository::class,
            ],
            'factories' => [
                FileArticleRepository::class => FileArticleRepositoryFactory::class,
            ],
        ];
    }

    /**
     * Get route configuration
     *
     * Handlers implementing DtoHandlerInterface (methorz/http-dto) are wrapped automatically by
     * its DtoHandlerAbstractFactory; plain PSR-15 handlers are used as they are.
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
