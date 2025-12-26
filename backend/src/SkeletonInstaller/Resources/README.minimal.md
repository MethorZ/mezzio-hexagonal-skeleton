# Project

> Mezzio application with PHP 8.4+ using minimal architecture

## Quick Start

```bash
# Start development environment
./scripts/dev.sh    # or: make start

# Your application is available at:
# http://localhost:8081
```

## Project Structure

```
project/
├── backend/
│   ├── src/
│   │   └── App/
│   │       └── Application/
│   │           ├── Config/             # Dependency injection and routing
│   │           │   └── ConfigProvider.php
│   │           ├── Handler/            # HTTP request handlers (PSR-15)
│   │           │   └── HealthCheckHandler.php
│   │           ├── Request/            # Request DTOs (with http-dto)
│   │           │   └── HealthCheckRequest.php
│   │           └── Response/           # Response DTOs (with http-dto)
│   │               └── HealthCheckResponse.php
│   ├── config/
│   │   ├── autoload/                   # Configuration files
│   │   │   ├── database.php
│   │   │   ├── logging.php
│   │   │   ├── cors.php
│   │   │   └── ...
│   │   ├── config.php                  # Main config aggregator
│   │   └── pipeline.php                # Middleware pipeline
│   ├── public/
│   │   └── index.php                   # Application entry point
│   ├── migrations/                     # Database migrations (if installed)
│   ├── bin/
│   │   └── console                     # CLI entry point (if symfony/console)
│   └── data/
│       ├── cache/                      # Application cache
│       └── logs/                       # Application logs
├── docker/                             # Docker configuration
├── scripts/                            # Helper scripts
├── docker-compose.yml
├── Makefile
└── .env                                # Environment variables
```

---

## Folder Purpose Guide

### `src/App/Application/Handler/`

**Purpose**: HTTP request handlers that process incoming requests.

**What goes here**:
- Classes implementing `RequestHandlerInterface` (PSR-15) for standard handlers
- Classes implementing `DtoHandlerInterface` for handlers using `methorz/http-dto`
- One handler per endpoint

**Example with http-dto**:
```php
<?php

declare(strict_types=1);

namespace App\Application\Handler;

use App\Application\Request\CreateUserRequest;
use App\Application\Response\UserResponse;
use MethorZ\Dto\Handler\DtoHandlerInterface;
use MethorZ\Dto\Response\JsonSerializableDto;
use Psr\Http\Message\ServerRequestInterface;

final readonly class CreateUserHandler implements DtoHandlerInterface
{
    public function __construct(
        private UserService $userService,
    ) {}

    /**
     * The CreateUserRequest DTO is automatically injected from the request body
     */
    public function __invoke(
        ServerRequestInterface $request,
        CreateUserRequest $dto,
    ): JsonSerializableDto {
        $user = $this->userService->createUser($dto);
        return UserResponse::fromEntity($user);
    }
}
```

**Naming convention**: `{Resource}{Action}Handler.php` (e.g., `CreateUserHandler.php`, `ListOrdersHandler.php`)

---

### `src/App/Application/Request/`

**Purpose**: Request DTOs for automatic request mapping with `methorz/http-dto`.

**What goes here**:
- Simple data transfer objects (DTOs) representing HTTP request data
- Validation rules using Symfony Validator attributes
- No business logic, only data structure and validation

**Example**:
```php
<?php

declare(strict_types=1);

namespace App\Application\Request;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateUserRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Email]
        public string $email,

        #[Assert\NotBlank]
        #[Assert\Length(min: 2, max: 100)]
        public string $name,

        #[Assert\Choice(choices: ['user', 'admin'])]
        public string $role = 'user',
    ) {}
}
```

---

### `src/App/Application/Response/`

**Purpose**: Response DTOs for automatic JSON serialization with `methorz/http-dto`.

**What goes here**:
- Classes extending `JsonSerializableDto`
- Response objects that are automatically serialized to JSON
- Factory methods to convert from domain entities to response DTOs

**Example**:
```php
<?php

declare(strict_types=1);

namespace App\Application\Response;

use MethorZ\Dto\Response\JsonSerializableDto;

final readonly class UserResponse implements JsonSerializableDto
{
    private function __construct(
        public string $id,
        public string $email,
        public string $name,
        public string $role,
    ) {}

    public static function fromEntity(User $user): self
    {
        return new self(
            id: $user->getId(),
            email: $user->getEmail(),
            name: $user->getName(),
            role: $user->getRole(),
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'name' => $this->name,
            'role' => $this->role,
        ];
    }

    public function getStatusCode(): int
    {
        return 200;
    }
}
```

---

### `src/App/Application/Config/`

**Purpose**: Dependency injection configuration and route registration.

**What goes here**:
- `ConfigProvider.php` - Single class that defines:
  - Dependency injection configuration (factories, aliases)
  - Route definitions
  - Uses `ReflectionBasedAbstractFactory` for automatic dependency injection

**Example**:
```php
<?php

declare(strict_types=1);

namespace App\Application\Config;

use App\Application\Handler\CreateUserHandler;
use Laminas\ServiceManager\AbstractFactory\ReflectionBasedAbstractFactory;
use MethorZ\Dto\Factory\DtoHandlerWrapperFactory;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
            'routes'       => $this->getRoutes(),
        ];
    }

    public function getDependencies(): array
    {
        return [
            'factories' => [
                // Add explicit factories only when needed
                // Most classes are auto-wired by ReflectionBasedAbstractFactory
            ],
            'abstract_factories' => [
                ReflectionBasedAbstractFactory::class,
            ],
        ];
    }

    public function getRoutes(): array
    {
        return [
            [
                'name'            => 'user.create',
                'path'            => '/users',
                'middleware'      => DtoHandlerWrapperFactory::class . '::wrap:' . CreateUserHandler::class,
                'allowed_methods' => ['POST'],
            ],
        ];
    }
}
```

**Key Points**:
- Use `ReflectionBasedAbstractFactory` for automatic dependency injection
- Only add explicit factories when auto-wiring doesn't work (complex dependencies)
- For `methorz/http-dto` handlers, use `DtoHandlerWrapperFactory::class . '::wrap:' . YourHandler::class`

---

## Adding New Features

### 1. Adding a New Endpoint

Create three files:

**Request DTO** (`src/App/Application/Request/CreateArticleRequest.php`):
```php
<?php

declare(strict_types=1);

namespace App\Application\Request;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateArticleRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 3, max: 200)]
        public string $title,

        #[Assert\NotBlank]
        public string $content,
    ) {}
}
```

**Response DTO** (`src/App/Application/Response/ArticleResponse.php`):
```php
<?php

declare(strict_types=1);

namespace App\Application\Response;

use MethorZ\Dto\Response\JsonSerializableDto;

final readonly class ArticleResponse implements JsonSerializableDto
{
    public function __construct(
        public string $id,
        public string $title,
        public string $content,
    ) {}

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'content' => $this->content,
        ];
    }

    public function getStatusCode(): int
    {
        return 201; // Created
    }
}
```

**Handler** (`src/App/Application/Handler/CreateArticleHandler.php`):
```php
<?php

declare(strict_types=1);

namespace App\Application\Handler;

use App\Application\Request\CreateArticleRequest;
use App\Application\Response\ArticleResponse;
use MethorZ\Dto\Handler\DtoHandlerInterface;
use MethorZ\Dto\Response\JsonSerializableDto;
use Psr\Http\Message\ServerRequestInterface;

final readonly class CreateArticleHandler implements DtoHandlerInterface
{
    // Dependencies are automatically injected by ReflectionBasedAbstractFactory
    public function __construct(
        private ArticleService $articleService,
    ) {}

    public function __invoke(
        ServerRequestInterface $request,
        CreateArticleRequest $dto,
    ): JsonSerializableDto {
        $article = $this->articleService->create($dto);
        return new ArticleResponse(
            id: $article->getId(),
            title: $article->getTitle(),
            content: $article->getContent(),
        );
    }
}
```

**Register Route** (in `src/App/Application/Config/ConfigProvider.php`):
```php
public function getRoutes(): array
{
    return [
        // ... existing routes
        [
            'name'            => 'article.create',
            'path'            => '/articles',
            'middleware'      => DtoHandlerWrapperFactory::class . '::wrap:' . CreateArticleHandler::class,
            'allowed_methods' => ['POST'],
        ],
    ];
}
```

That's it! No need to register factories - `ReflectionBasedAbstractFactory` handles dependency injection automatically.

---

## Configuration

### Environment Variables

All environment variables are defined in `.env` file:

```env
# Application
APP_ENV=development

# Database (if swift-db installed)
DB_HOST=database
DB_PORT=3306
DB_NAME=app
DB_USER=app
DB_PASSWORD=secret

# Logging
LOG_LEVEL=INFO
LOG_PATH=data/logs/application.log

# JWT Auth (if jwt-auth-middleware installed)
JWT_SECRET=your-secret-key-here
```

### Database Configuration

If you installed `methorz/swift-db`, configure it in `config/autoload/database.php`:

```php
return [
    'db' => [
        'host'     => $_ENV['DB_HOST'] ?? 'database',
        'port'     => (int) ($_ENV['DB_PORT'] ?? 3306),
        'database' => $_ENV['DB_NAME'] ?? 'app',
        'username' => $_ENV['DB_USER'] ?? 'app',
        'password' => $_ENV['DB_PASSWORD'] ?? 'secret',
        'charset'  => 'utf8mb4',
        'options'  => [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        ],
    ],
];
```

---

## Development Workflow

### Quality Checks

```bash
# Run all quality checks
composer check

# Individual checks
composer cs-check      # Code style (PSR-12)
composer analyze       # Static analysis (PHPStan level 9)
composer test          # Unit tests (PHPUnit)
composer test-coverage # Generate coverage report
```

### OpenAPI Documentation (Optional)

If you installed `methorz/openapi-generator`:

```bash
# Generate manually
composer docs

# Auto-generated when running quality checks
composer check
```

**Output:**
- `docs/openapi.yaml` - YAML spec
- `docs/openapi.json` - JSON spec

**View:**
- Upload to [Swagger Editor](https://editor.swagger.io)
- Use [ReDoc](https://redocly.github.io/redoc/)

**How it works:**
Scans routes → analyzes DTOs → extracts validation → generates OpenAPI 3.0 spec

**Configuration:** `config/autoload/openapi.yaml`

### Migrations (if Doctrine Migrations installed)

```bash
# Generate a new migration
vendor/bin/doctrine-migrations generate

# Run migrations
vendor/bin/doctrine-migrations migrate

# Check status
vendor/bin/doctrine-migrations status
```

### Console Commands (if Symfony Console installed)

```bash
# List all commands
php bin/console

# Run a command
php bin/console app:your-command
```

---

## Package-Specific Guides

### Using `methorz/http-dto`

The `methorz/http-dto` package provides automatic request/response handling:

1. **Request DTOs** are automatically populated from request body/query parameters
2. **Validation** is performed using Symfony Validator attributes
3. **Response DTOs** are automatically serialized to JSON

**Key Benefits**:
- Type-safe request handling
- Automatic validation
- Reduced boilerplate
- Clear request/response contracts

**Example Flow**:
1. HTTP request arrives: `POST /articles` with JSON body
2. `DtoHandlerWrapper` deserializes JSON to `CreateArticleRequest`
3. Symfony Validator validates the DTO
4. Your handler is called with validated `CreateArticleRequest`
5. Handler returns `ArticleResponse` which is serialized to JSON

### Using `methorz/swift-db`

Simple query builder for MySQL:

```php
use MethorZ\SwiftDb\Connection;

final readonly class ArticleRepository
{
    public function __construct(
        private Connection $connection,
    ) {}

    public function findById(string $id): ?Article
    {
        $row = $this->connection
            ->table('articles')
            ->where('id', '=', $id)
            ->first();

        return $row ? $this->hydrate($row) : null;
    }

    public function save(Article $article): void
    {
        $this->connection
            ->table('articles')
            ->insert([
                'id' => $article->getId(),
                'title' => $article->getTitle(),
                'content' => $article->getContent(),
            ]);
    }
}
```

---

## Need Help?

- **Mezzio Documentation**: https://docs.mezzio.dev/
- **PSR-15 Middleware**: https://www.php-fig.org/psr/psr-15/
- **Laminas ServiceManager**: https://docs.laminas.dev/laminas-servicemanager/
- **Symfony Validator**: https://symfony.com/doc/current/validation.html

---

## Tips

1. **Use ReflectionBasedAbstractFactory**: Let it auto-wire your dependencies. Only add explicit factories when needed.
2. **Keep handlers thin**: Business logic should be in services, not handlers.
3. **Use DTOs**: They provide type safety and clear contracts.
4. **Validate at the edge**: Use Symfony Validator attributes on Request DTOs.
5. **Test your handlers**: Write unit tests for all handlers.
