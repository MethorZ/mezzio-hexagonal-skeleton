<?php

declare(strict_types=1);

namespace Article\Infrastructure\Repository;

use Psr\Container\ContainerInterface;
use RuntimeException;

final class FileArticleRepositoryFactory
{
    public function __invoke(ContainerInterface $container): FileArticleRepository
    {
        $config  = $container->get('config');
        $article = is_array($config) ? ($config['article'] ?? null) : null;
        $file    = is_array($article) ? ($article['storage_file'] ?? null) : null;

        if (! is_string($file) || $file === '') {
            throw new RuntimeException('Config key "article.storage_file" must be a non-empty string');
        }

        return new FileArticleRepository($file);
    }
}
