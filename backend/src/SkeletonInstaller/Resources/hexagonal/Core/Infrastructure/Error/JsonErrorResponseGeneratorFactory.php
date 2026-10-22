<?php

declare(strict_types=1);

namespace Core\Infrastructure\Error;

use Psr\Container\ContainerInterface;

use function is_array;

final class JsonErrorResponseGeneratorFactory
{
    public function __invoke(ContainerInterface $container): JsonErrorResponseGenerator
    {
        $config = $container->has('config') ? $container->get('config') : [];

        return new JsonErrorResponseGenerator(is_array($config) && ($config['debug'] ?? false) === true);
    }
}
