# Project

> Mezzio application with PHP 8.4+ using Hexagonal Architecture (Ports & Adapters)

## Quick Start

```bash
# Start development environment
./scripts/dev.sh    # or: make start

# Your application is available at:
# http://localhost:8081
```

## Hexagonal Architecture Overview

This project uses **Hexagonal Architecture** (also known as Ports & Adapters) to create a maintainable, testable, and framework-independent application.

### Key Principles

1. **Domain at the Center**: Business logic is isolated in the Domain layer
2. **Dependency Inversion**: Domain defines interfaces (ports), infrastructure implements them (adapters)
3. **Framework Independence**: Domain code doesn't depend on Mezzio, HTTP, or databases
4. **Testability**: Easy to test business logic without infrastructure concerns

### Layer Structure

```
{Module}/
├── Application/        # Use cases and application services
│   ├── Service/        # Application services (orchestrate domain logic)
│   ├── Request/        # Request DTOs (inbound data)
│   ├── Response/       # Response DTOs (outbound data)
│   └── Config/         # DI configuration
├── Domain/             # Business logic (framework-independent)
│   ├── Entity/         # Domain entities and aggregates
│   ├── ValueObject/    # Value objects
│   ├── Enum/           # Domain enumerations
│   ├── Port/           # Outbound port interfaces
│   └── Exception/      # Domain exceptions
├── Infrastructure/     # Adapters for external concerns
│   ├── Handler/        # HTTP handlers (inbound adapters)
│   └── Repository/     # Database repositories (outbound adapters)
└── Tests/              # Unit and integration tests
```

---

## Project Structure

```
project/
├── backend/
│   ├── src/
│   │   ├── Core/                       # Shared domain primitives
│   │   │   └── Domain/
│   │   │       ├── ValueObject/
│   │   │       │   └── BaseValueObject.php
│   │   │       └── Exception/
│   │   │           └── DomainException.php
│   │   ├── HealthCheck/                # Minimalistic health check module
│   │   │   ├── Application/
│   │   │   │   ├── Service/
│   │   │   │   │   └── HealthCheckService.php
│   │   │   │   ├── Request/
│   │   │   │   │   └── HealthCheckRequest.php
│   │   │   │   ├── Response/
│   │   │   │   │   └── HealthCheckResponse.php
│   │   │   │   └── Config/
│   │   │   │       └── ConfigProvider.php
│   │   │   └── Infrastructure/
│   │   │       └── Handler/
│   │   │           └── HealthCheckHandler.php
│   │   └── Article/                    # Full CRUD example module
│   │       ├── Application/
│   │       │   ├── Service/
│   │       │   │   └── ArticleService.php
│   │       │   ├── Request/
│   │       │   │   ├── CreateArticleRequest.php
│   │       │   │   ├── UpdateArticleRequest.php
│   │       │   │   └── GetArticleRequest.php
│   │       │   ├── Response/
│   │       │   │   ├── ArticleResponse.php
│   │       │   │   └── ArticleListResponse.php
│   │       │   └── Config/
│   │       │       └── ConfigProvider.php
│   │       ├── Domain/
│   │       │   ├── Entity/
│   │       │   │   └── Article.php
│   │       │   ├── ValueObject/
│   │       │   │   ├── ArticleId.php
│   │       │   │   ├── Title.php
│   │       │   │   ├── Content.php
│   │       │   │   └── Author.php
│   │       │   ├── Enum/
│   │       │   │   └── ArticleStatus.php
│   │       │   ├── Port/
│   │       │   │   └── ArticleRepositoryInterface.php
│   │       │   └── Exception/
│   │       │       ├── ArticleNotFoundException.php
│   │       │       └── InvalidArticleException.php
│   │       └── Infrastructure/
│   │           ├── Handler/
│   │           │   ├── CreateArticleHandler.php
│   │           │   ├── GetArticleHandler.php
│   │           │   ├── UpdateArticleHandler.php
│   │           │   ├── DeleteArticleHandler.php
│   │           │   └── ListArticlesHandler.php
│   │           └── Repository/
│   │               ├── InMemoryArticleRepository.php
│   │               └── DbArticleRepository.php
│   ├── config/
│   │   ├── autoload/                   # Configuration files
│   │   ├── config.php                  # Main config aggregator
│   │   └── pipeline.php                # Middleware pipeline
│   ├── public/
│   │   └── index.php                   # Application entry point
│   ├── migrations/                     # Database migrations
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

## Layer Responsibilities

### Domain Layer

**Purpose**: Contains all business logic, rules, and invariants.

**Characteristics**:
- Framework-independent (no Mezzio, HTTP, or database dependencies)
- Defines **outbound ports** (interfaces for what it needs from infrastructure)
- Pure PHP with business logic only

**What goes here**:
- **Entities**: Core business objects with identity (e.g., `Article`, `User`)
- **Value Objects**: Immutable objects defined by their values (e.g., `Email`, `Money`)
- **Enums**: Domain enumerations (e.g., `ArticleStatus`, `UserRole`)
- **Port Interfaces**: Contracts for infrastructure (e.g., `ArticleRepositoryInterface`)
- **Domain Exceptions**: Business rule violations (e.g., `InvalidArticleException`)

**Example Entity**:
```php
final class Article
{
    private function __construct(
        private readonly ArticleId $id,
        private Title $title,
        private Content $content,
        private ArticleStatus $status,
    ) {}

    public static function create(
        ArticleId $id,
        Title $title,
        Content $content,
    ): self {
        return new self($id, $title, $content, ArticleStatus::DRAFT);
    }

    public function publish(): void
    {
        if ($this->status === ArticleStatus::ARCHIVED) {
            throw InvalidArticleException::cannotPublishArchivedArticle();
        }
        $this->status = ArticleStatus::PUBLISHED;
    }

    // Getters...
}
```

---

### Application Layer

**Purpose**: Orchestrates domain logic to implement use cases.

**Characteristics**:
- Coordinates between domain and infrastructure
- Contains no business logic (delegates to domain)
- Defines **inbound operations** (what the application does)
- Uses **ReflectionBasedAbstractFactory** for automatic dependency injection

**What goes here**:
- **Services**: Application services that implement use cases
- **Request DTOs**: Input data structures
- **Response DTOs**: Output data structures
- **Config**: Dependency injection and routing configuration

**Example Service**:
```php
final readonly class ArticleService
{
    public function __construct(
        private ArticleRepositoryInterface $articleRepository,
    ) {}

    public function create(CreateArticleRequest $request): ArticleResponse
    {
        $article = Article::create(
            id: ArticleId::generate(),
            title: Title::fromString($request->title),
            content: Content::fromString($request->content),
        );

        $this->articleRepository->save($article);

        return ArticleResponse::fromEntity($article);
    }

    public function publish(string $id): ArticleResponse
    {
        $article = $this->findArticleOrFail($id);
        $article->publish();
        $this->articleRepository->save($article);

        return ArticleResponse::fromEntity($article);
    }
}
```

---

### Infrastructure Layer

**Purpose**: Implements technical details and external integrations.

**Characteristics**:
- Implements **outbound ports** (adapters for domain interfaces)
- Provides **inbound adapters** (HTTP handlers)
- Contains framework-specific code

**What goes here**:
- **Handlers**: HTTP request handlers (inbound adapters)
- **Repositories**: Database implementations (outbound adapters)
- **External Service Clients**: API clients, message queues, etc.

**Example Repository**:
```php
final readonly class InMemoryArticleRepository implements ArticleRepositoryInterface
{
    private static array $articles = [];

    public function save(Article $article): void
    {
        self::$articles[$article->getId()->getValue()] = $article;
    }

    public function findById(ArticleId $id): ?Article
    {
        return self::$articles[$id->getValue()] ?? null;
    }

    // ... other methods
}
```

**Example Handler**:
```php
final readonly class CreateArticleHandler implements DtoHandlerInterface
{
    public function __construct(
        private ArticleService $articleService,
    ) {}

    public function __invoke(
        ServerRequestInterface $request,
        CreateArticleRequest $dto,
    ): JsonSerializableDto {
        return $this->articleService->create($dto);
    }
}
```

---

## Dependency Rules

**Critical Rule**: Dependencies point inward (toward the domain).

```
Infrastructure → Application → Domain
(HTTP/DB)      → (Use Cases) → (Business Logic)
```

✅ **Allowed**:
- Infrastructure can depend on Application
- Infrastructure can depend on Domain
- Application can depend on Domain

❌ **Not Allowed**:
- Domain cannot depend on Application or Infrastructure
- Application cannot depend on Infrastructure

**How it works**:
1. Domain defines interfaces (ports)
2. Infrastructure implements those interfaces (adapters)
3. Application uses the interfaces (knows nothing about implementations)
4. DI container wires everything together

---

## Folder Purpose Guide

### `{Module}/Domain/Entity/`

**Purpose**: Core business entities with identity.

**What goes here**:
- Aggregate roots
- Entities with lifecycle and identity
- Business logic and invariants
- Domain events (optional)

**Example**:
```php
final class Article
{
    private DateTimeImmutable $createdAt;
    private DateTimeImmutable $updatedAt;

    private function __construct(
        private readonly ArticleId $id,
        private Title $title,
        private Content $content,
        private Author $author,
        private ArticleStatus $status,
    ) {
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    public static function create(...): self { /* ... */ }

    public function update(Title $title, Content $content): void
    {
        if (!$this->status->canEdit()) {
            throw InvalidArticleException::cannotEditArchivedArticle();
        }

        $this->title = $title;
        $this->content = $content;
        $this->updatedAt = new DateTimeImmutable();
    }
}
```

---

### `{Module}/Domain/ValueObject/`

**Purpose**: Immutable objects defined by their values.

**What goes here**:
- Value objects (Money, Email, Address, etc.)
- Validation logic within the value object
- Comparison logic (equality)

**Example**:
```php
final readonly class Title extends BaseValueObject
{
    private const MIN_LENGTH = 3;
    private const MAX_LENGTH = 200;

    private string $value;

    private function __construct(string $value)
    {
        $this->validate($value);
        $this->value = $value;
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function equals(mixed $other): bool
    {
        return $other instanceof self && $this->value === $other->value;
    }

    private function validate(string $value): void
    {
        $length = mb_strlen(trim($value));

        if ($length < self::MIN_LENGTH) {
            throw InvalidArticleException::titleTooShort(self::MIN_LENGTH);
        }

        if ($length > self::MAX_LENGTH) {
            throw InvalidArticleException::titleTooLong(self::MAX_LENGTH);
        }
    }
}
```

---

### `{Module}/Domain/Port/`

**Purpose**: Interfaces defining what the domain needs from infrastructure.

**What goes here**:
- Repository interfaces
- External service interfaces
- Any infrastructure contract the domain needs

**Example**:
```php
interface ArticleRepositoryInterface
{
    public function save(Article $article): void;
    public function findById(ArticleId $id): ?Article;
    public function findAll(): array;
    public function delete(ArticleId $id): void;
}
```

---

### `{Module}/Application/Service/`

**Purpose**: Application services implementing use cases.

**What goes here**:
- One service per bounded context/module
- Methods representing use cases (create, update, delete, etc.)
- Orchestration logic (no business logic)
- Transaction boundaries

**Example**:
```php
final readonly class ArticleService
{
    public function __construct(
        private ArticleRepositoryInterface $articleRepository,
    ) {}

    public function create(CreateArticleRequest $request): ArticleResponse
    {
        // Orchestrate: create domain object, save, return response
        $article = Article::create(
            id: ArticleId::generate(),
            title: Title::fromString($request->title),
            content: Content::fromString($request->content),
            author: Author::fromString($request->author),
        );

        $this->articleRepository->save($article);

        return ArticleResponse::fromEntity($article);
    }
}
```

---

### `{Module}/Infrastructure/Handler/`

**Purpose**: HTTP request handlers (inbound adapters).

**What goes here**:
- One handler per HTTP endpoint
- Implements `DtoHandlerInterface` (for http-dto)
- Calls application services
- Maps exceptions to HTTP responses

**Example**:
```php
final readonly class CreateArticleHandler implements DtoHandlerInterface
{
    public function __construct(
        private ArticleService $articleService,
    ) {}

    public function __invoke(
        ServerRequestInterface $request,
        CreateArticleRequest $dto,
    ): JsonSerializableDto {
        return $this->articleService->create($dto);
    }
}
```

---

### `{Module}/Infrastructure/Repository/`

**Purpose**: Repository implementations (outbound adapters).

**What goes here**:
- Implementations of domain repository interfaces
- Database query logic
- Data mapping (DB rows ↔ domain entities)

**Example**:
```php
final readonly class DbArticleRepository implements ArticleRepositoryInterface
{
    public function __construct(
        private Connection $connection,
    ) {}

    public function save(Article $article): void
    {
        $this->connection
            ->table('articles')
            ->insert($this->toRow($article));
    }

    private function toRow(Article $article): array
    {
        return [
            'id' => $article->getId()->getValue(),
            'title' => $article->getTitle()->getValue(),
            'content' => $article->getContent()->getValue(),
            // ...
        ];
    }
}
```

---

## Adding New Modules

To add a new bounded context/module:

### 1. Create Directory Structure

```
src/{Module}/
├── Application/
│   ├── Service/
│   ├── Request/
│   ├── Response/
│   └── Config/
├── Domain/
│   ├── Entity/
│   ├── ValueObject/
│   ├── Enum/
│   ├── Port/
│   └── Exception/
└── Infrastructure/
    ├── Handler/
    └── Repository/
```

### 2. Define Domain Layer

Start with domain entities, value objects, and repository interfaces.

### 3. Create Application Service

Implement use cases by orchestrating domain logic.

### 4. Implement Infrastructure

Create handlers and repository implementations.

### 5. Register Module

Add ConfigProvider to `config/config.php`:

```php
return new ConfigAggregator([
    // ... existing providers
    YourModule\Application\Config\ConfigProvider::class,
    // ...
], $cacheConfig['config_cache_path']);
```

---

## Testing Strategy

### Unit Tests

**Domain Layer**: Test business logic in isolation.

```php
class ArticleTest extends TestCase
{
    public function testCannotPublishArchivedArticle(): void
    {
        $article = Article::create(/* ... */);
        $article->archive();

        $this->expectException(InvalidArticleException::class);
        $article->publish();
    }
}
```

**Application Layer**: Test use case orchestration with mocked repositories.

```php
class ArticleServiceTest extends TestCase
{
    public function testCreateArticle(): void
    {
        $repository = $this->createMock(ArticleRepositoryInterface::class);
        $repository->expects($this->once())->method('save');

        $service = new ArticleService($repository);
        $response = $service->create(new CreateArticleRequest(/* ... */));

        $this->assertInstanceOf(ArticleResponse::class, $response);
    }
}
```

### Integration Tests

**Infrastructure Layer**: Test with real dependencies (database, HTTP).

```php
class DbArticleRepositoryTest extends TestCase
{
    public function testSaveAndRetrieveArticle(): void
    {
        $repository = new DbArticleRepository($this->connection);
        $article = Article::create(/* ... */);

        $repository->save($article);
        $retrieved = $repository->findById($article->getId());

        $this->assertEquals($article->getId(), $retrieved->getId());
    }
}
```

---

## Package Integration

### Using `methorz/http-dto`

All handlers in this architecture use `DtoHandlerInterface` for automatic request/response handling.

### Using `methorz/swift-db`

Use `DbArticleRepository` instead of `InMemoryArticleRepository`:

1. Update `Article/Application/Config/ConfigProvider.php`:
```php
'aliases' => [
    ArticleRepositoryInterface::class => DbArticleRepository::class,
],
```

2. Create migration for articles table (see `DbArticleRepository.php` for schema)

---

## Common Patterns

### Creating a New Entity

```php
// Static factory method
public static function create(
    ArticleId $id,
    Title $title,
    Content $content,
): self {
    // Validate invariants
    // Create entity in valid state
    return new self($id, $title, $content, ArticleStatus::DRAFT);
}
```

### Reconstituting from Database

```php
// For hydration from persistence
public static function reconstitute(
    ArticleId $id,
    Title $title,
    Content $content,
    ArticleStatus $status,
    DateTimeImmutable $createdAt,
    DateTimeImmutable $updatedAt,
): self {
    $article = new self($id, $title, $content, $status);
    $article->createdAt = $createdAt;
    $article->updatedAt = $updatedAt;
    return $article;
}
```

### Value Object Validation

```php
final readonly class Email extends BaseValueObject
{
    private function __construct(
        private string $value,
    ) {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Invalid email address');
        }
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }
}
```

---

## Benefits of This Architecture

1. **Testability**: Easy to test business logic in isolation
2. **Maintainability**: Clear separation of concerns
3. **Flexibility**: Easy to swap implementations (in-memory → database)
4. **Framework Independence**: Domain code doesn't depend on Mezzio
5. **Clear Boundaries**: Each layer has specific responsibilities

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
# Check migration status
vendor/bin/doctrine-migrations status

# Run migrations
vendor/bin/doctrine-migrations migrate

# Generate new migration
vendor/bin/doctrine-migrations generate

# See migrations/README.md for full documentation
```

---

## Need Help?

- **Hexagonal Architecture**: See `ARCHITECTURE.hexagonal.md` for detailed guide
- **Mezzio Documentation**: https://docs.mezzio.dev/
- **DDD Patterns**: Domain-Driven Design by Eric Evans
- **Clean Architecture**: The Clean Architecture by Robert C. Martin

---

## Tips

1. **Start with Domain**: Define entities and value objects first
2. **Think in Use Cases**: Each public method in Application Service is a use case
3. **Keep Handlers Thin**: All logic should be in Application or Domain layers
4. **Use Value Objects**: They encapsulate validation and behavior
5. **Test Domain Logic**: It's the most important part of your application
6. **ReflectionBasedAbstractFactory**: Let it handle dependency injection automatically
