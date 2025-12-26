# Hexagonal Architecture Guide

This document explains the hexagonal architecture (Ports & Adapters) pattern as implemented in this project.

## Core Concepts

### What is Hexagonal Architecture?

Hexagonal Architecture, introduced by Alistair Cockburn, organizes code into three main layers:

1. **Domain Layer** - Pure business logic with no external dependencies
2. **Application Layer** - Use cases that orchestrate domain logic
3. **Infrastructure Layer** - Adapters connecting to external systems

The key insight is that the **business logic is at the center**, and all external systems (HTTP, databases, message queues) are pushed to the edges as "adapters".

### Why Use It?

| Benefit | Description |
|---------|-------------|
| **Testability** | Domain and application can be tested without databases or HTTP |
| **Flexibility** | Easy to swap implementations (change DB, API, etc.) |
| **Maintainability** | Clear boundaries prevent spaghetti code |
| **Framework Independence** | Business logic doesn't depend on Mezzio or any framework |
| **Team Scalability** | Teams can work on different layers independently |

---

## The Layers Explained

### Domain Layer

The **innermost layer** containing business rules and logic.

**Characteristics**:
- NO framework dependencies (no Mezzio, Laminas, etc.)
- NO database code (no PDO, Doctrine, etc.)
- NO HTTP concepts (no Request, Response, etc.)
- Pure PHP only

**Contains**:
- **Entities**: Business objects with identity (Article, User, Order)
- **Value Objects**: Immutable objects defined by value (Email, Money, Title)
- **Enums**: Domain enumerations (ArticleStatus, UserRole)
- **Repository Interfaces (Ports)**: Contracts for data access
- **Domain Exceptions**: Business rule violations
- **Domain Services** (optional): Business logic that doesn't fit in entities

**Example Entity**:
```php
namespace Article\Domain\Entity;

use Article\Domain\Enum\ArticleStatus;
use Article\Domain\ValueObject\ArticleId;
use Article\Domain\ValueObject\Title;
use Article\Domain\ValueObject\Content;

final class Article
{
    private DateTimeImmutable $createdAt;
    private DateTimeImmutable $updatedAt;

    private function __construct(
        private readonly ArticleId $id,
        private Title $title,
        private Content $content,
        private ArticleStatus $status,
    ) {
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

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
        $this->updatedAt = new DateTimeImmutable();
    }

    public function update(Title $title, Content $content): void
    {
        if (!$this->status->canEdit()) {
            throw InvalidArticleException::cannotEditArchivedArticle();
        }

        $this->title = $title;
        $this->content = $content;
        $this->updatedAt = new DateTimeImmutable();
    }

    // Getters...
}
```

**Example Value Object**:
```php
namespace Article\Domain\ValueObject;

use Core\Domain\ValueObject\BaseValueObject;

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

#### Value Object Composition Pattern

The project uses **multiple granular Value Objects** (ArticleId, Title, Content, Author) rather than a single ArticleData VO. This is a DDD best practice providing:

**Benefits**:
- **Strong typing and validation** - Each field has its own type and validation rules
- **Cross-entity reusability** - Author can be used in Article, Comment, Review entities
- **Single Responsibility** - Each VO validates its own business rules
- **Immutability guarantees** - Each concept is independently immutable

**When to Compose**:

If you later need composite Value Objects, compose them from primitive VOs:

```php
namespace Article\Domain\ValueObject;

use Core\Domain\ValueObject\BaseValueObject;

final readonly class ArticleSnapshot extends BaseValueObject
{
    public function __construct(
        public ArticleId $id,
        public Title $title,
        public Content $content,
        public Author $author,
        public ArticleStatus $status,
    ) {
        $this->validate();
    }

    protected function getEqualityValues(): array
    {
        return [
            $this->id->getValue(),
            $this->title->getValue(),
            $this->content->getValue(),
            $this->author->getValue(),
            $this->status->value,
        ];
    }

    protected function validate(): void
    {
        // Composite-level validation (e.g., business rules across fields)
    }

    // Composite VOs can have behavior operating on multiple fields
    public function isByAuthor(Author $author): bool
    {
        return $this->author->equals($author);
    }
}
```

**Abstraction Layers**:
- **Primitive VOs** (Title, Author, Email): Field-level validation, cross-entity reuse
- **Composite VOs** (ArticleSnapshot, UserProfile): Data transfer, memento pattern, serialization
- **Entities** (Article, User): Business logic, state transitions, lifecycle management

This layered approach follows DDD principles: you compose Value Objects just like any other objects, choosing the right level of abstraction for each use case.

**Example Repository Interface (Outbound Port)**:
```php
namespace Article\Domain\Port;

use Article\Domain\Entity\Article;
use Article\Domain\ValueObject\ArticleId;

interface ArticleRepositoryInterface
{
    public function save(Article $article): void;
    public function findById(ArticleId $id): ?Article;
    public function findAll(): array;
    public function delete(ArticleId $id): void;
}
```

---

### Application Layer

The **orchestration layer** that implements use cases.

**Characteristics**:
- Depends only on Domain
- Coordinates domain objects
- Defines DTOs for input/output
- Uses `ReflectionBasedAbstractFactory` for automatic dependency injection

**Contains**:
- **Application Services**: Use case implementations
- **Request DTOs**: Input data structures
- **Response DTOs**: Output data structures
- **Config**: Dependency injection and routing configuration

**Example Service**:
```php
namespace Article\Application\Service;

use Article\Application\Request\CreateArticleRequest;
use Article\Application\Response\ArticleResponse;
use Article\Domain\Port\ArticleRepositoryInterface;

final readonly class ArticleService
{
    public function __construct(
        private ArticleRepositoryInterface $articleRepository,
    ) {}

    public function create(CreateArticleRequest $request): ArticleResponse
    {
        // Orchestrate: create domain object, validate, save, return response
        $article = Article::create(
            id: ArticleId::generate(),
            title: Title::fromString($request->title),
            content: Content::fromString($request->content),
            author: Author::fromString($request->author),
        );

        $this->articleRepository->save($article);

        return ArticleResponse::fromEntity($article);
    }

    public function publish(string $id): ArticleResponse
    {
        $articleId = ArticleId::fromString($id);
        $article = $this->findArticleOrFail($articleId);

        // Business logic is in domain
        $article->publish();

        $this->articleRepository->save($article);

        return ArticleResponse::fromEntity($article);
    }
}
```

**Key Points**:
- Application services **orchestrate**, they don't contain business logic
- Business logic lives in entities and value objects
- Services call domain methods and coordinate between domain objects
- Use `ReflectionBasedAbstractFactory` - it auto-wires dependencies, no manual factories needed!

---

### Infrastructure Layer

The **adapter layer** that connects to external systems.

**Characteristics**:
- Implements Domain repository interfaces
- Handles HTTP requests/responses
- Depends on Domain and Application
- Contains framework-specific code

**Contains**:
- **Handlers**: HTTP request handlers (inbound adapters)
- **Repositories**: Database implementations (outbound adapters)
- **External Service Clients**: API clients, message queues, etc.

**Example Repository (Outbound Adapter)**:
```php
namespace Article\Infrastructure\Repository;

use Article\Domain\Port\ArticleRepositoryInterface;

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

    public function findById(ArticleId $id): ?Article
    {
        $row = $this->connection
            ->table('articles')
            ->where('id', '=', $id->getValue())
            ->first();

        return $row ? $this->fromRow($row) : null;
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

    private function fromRow(array $row): Article
    {
        return Article::reconstitute(
            id: ArticleId::fromString($row['id']),
            title: Title::fromString($row['title']),
            content: Content::fromString($row['content']),
            // ...
        );
    }
}
```

**Example Handler (Inbound Adapter)**:
```php
namespace Article\Infrastructure\Handler;

use Article\Application\Request\CreateArticleRequest;
use Article\Application\Service\ArticleService;
use MethorZ\Dto\Handler\DtoHandlerInterface;

final readonly class CreateArticleHandler implements DtoHandlerInterface
{
    public function __construct(
        private ArticleService $articleService,
    ) {}

    public function __invoke(
        ServerRequestInterface $request,
        CreateArticleRequest $dto,
    ): JsonSerializableDto {
        // Delegate to application service
        return $this->articleService->create($dto);
    }
}
```

---

## Ports & Adapters Pattern

### What are Ports?

**Ports** are interfaces that define contracts between layers.

**Two Types**:
1. **Inbound Ports**: What the application provides (use cases)
   - Example: `ArticleService` methods (create, update, delete)

2. **Outbound Ports**: What the application needs (dependencies)
   - Example: `ArticleRepositoryInterface`

### What are Adapters?

**Adapters** are implementations of ports that connect to external systems.

**Two Types**:
1. **Inbound Adapters**: Drive the application (HTTP handlers, CLI commands, message consumers)
   - Example: `CreateArticleHandler` (HTTP) → calls `ArticleService.create()`

2. **Outbound Adapters**: Called by the application (repository implementations, external APIs)
   - Example: `DbArticleRepository` → implements `ArticleRepositoryInterface`

### The Flow

```
HTTP Request
    ↓
[CreateArticleHandler] ← Inbound Adapter
    ↓
[ArticleService] ← Application Service (Inbound Port)
    ↓
[Article Entity] ← Domain Logic
    ↓
[ArticleRepositoryInterface] ← Outbound Port (interface)
    ↓
[DbArticleRepository] ← Outbound Adapter (implementation)
    ↓
Database
```

**Key Insight**: The domain knows nothing about HTTP or databases. It only knows about interfaces (ports).

---

## Dependency Rules

The **Dependency Rule** is fundamental to hexagonal architecture:

```
Infrastructure → Application → Domain
(Adapters)    → (Use Cases) → (Business Logic)
```

### The Rule

**Dependencies point inward** (toward the domain).

✅ **Allowed**:
- Infrastructure can depend on Application
- Infrastructure can depend on Domain
- Application can depend on Domain

❌ **Not Allowed**:
- Domain cannot depend on Application
- Domain cannot depend on Infrastructure
- Application cannot depend on Infrastructure

### How It Works

1. **Domain defines interfaces** (repository ports)
2. **Infrastructure implements interfaces** (adapters)
3. **Application uses interfaces** (knows nothing about implementations)
4. **DI container wires everything** (at runtime, not compile time)

**Example**:
```php
// Domain defines the contract
namespace Article\Domain\Port;

interface ArticleRepositoryInterface
{
    public function save(Article $article): void;
}

// Infrastructure implements the contract
namespace Article\Infrastructure\Repository;

class DbArticleRepository implements ArticleRepositoryInterface
{
    // Implementation using database
}

// Application uses the interface
namespace Article\Application\Service;

class ArticleService
{
    public function __construct(
        private ArticleRepositoryInterface $repository, // Interface, not implementation!
    ) {}
}

// DI container wires it up
// In ConfigProvider.php:
'aliases' => [
    ArticleRepositoryInterface::class => DbArticleRepository::class,
],
```

---

## Module Structure

Each module follows this structure:

```
{Module}/
├── Application/
│   ├── Service/              # Application services (use cases)
│   ├── Request/              # Request DTOs
│   ├── Response/             # Response DTOs
│   └── Config/               # DI configuration
├── Domain/
│   ├── Entity/               # Entities and aggregates
│   ├── ValueObject/          # Value objects
│   ├── Enum/                 # Domain enums
│   ├── Port/                 # Repository interfaces (outbound ports)
│   └── Exception/            # Domain exceptions
└── Infrastructure/
    ├── Handler/              # HTTP handlers (inbound adapters)
    └── Repository/           # Repository implementations (outbound adapters)
```

---

## Adding a New Bounded Context (Module)

### Step 1: Define the Domain

Start with the business logic:

1. **Identify Entities**: What are the core business objects?
2. **Extract Value Objects**: What values need validation?
3. **Define Repository Interface**: What data access is needed?

```php
// 1. Entity
class Product
{
    public function changeName(ProductName $name): void { /* ... */ }
    public function increaseStock(int $quantity): void { /* ... */ }
}

// 2. Value Object
class ProductName
{
    private function __construct(private string $value) {
        // Validation logic
    }
}

// 3. Repository Interface
interface ProductRepositoryInterface
{
    public function save(Product $product): void;
    public function findBySku(ProductSku $sku): ?Product;
}
```

### Step 2: Create Application Services

Implement use cases:

```php
class ProductService
{
    public function __construct(
        private ProductRepositoryInterface $products,
    ) {}

    public function changeProductName(ChangeProductNameRequest $request): ProductResponse
    {
        $product = $this->findProductOrFail($request->sku);
        $product->changeName(ProductName::fromString($request->name));
        $this->products->save($product);

        return ProductResponse::fromEntity($product);
    }
}
```

### Step 3: Implement Infrastructure

Create adapters:

```php
// Handler (Inbound Adapter)
class ChangeProductNameHandler implements DtoHandlerInterface
{
    public function __construct(private ProductService $service) {}

    public function __invoke(
        ServerRequestInterface $request,
        ChangeProductNameRequest $dto,
    ): JsonSerializableDto {
        return $this->service->changeProductName($dto);
    }
}

// Repository (Outbound Adapter)
class DbProductRepository implements ProductRepositoryInterface
{
    public function save(Product $product): void {
        // Database implementation
    }
}
```

### Step 4: Configure DI

In `Product/Application/Config/ConfigProvider.php`:

```php
public function getDependencies(): array
{
    return [
        'aliases' => [
            ProductRepositoryInterface::class => DbProductRepository::class,
        ],
        'abstract_factories' => [
            ReflectionBasedAbstractFactory::class, // Auto-wires everything!
        ],
    ];
}

public function getRoutes(): array
{
    return [
        [
            'name' => 'product.change-name',
            'path' => '/products/{sku}/name',
            'middleware' => DtoHandlerWrapperFactory::class . '::wrap:' . ChangeProductNameHandler::class,
            'allowed_methods' => ['PUT'],
        ],
    ];
}
```

### Step 5: Register Module

Add to `config/config.php`:

```php
$aggregator = new ConfigAggregator([
    // ... existing
    Product\Application\Config\ConfigProvider::class,
], $cacheConfig['config_cache_path']);
```

---

## Testing in Hexagonal Architecture

### Unit Testing Domain

Test business logic in isolation:

```php
class ArticleTest extends TestCase
{
    public function testPublishingArchivedArticleThrowsException(): void
    {
        $article = Article::create(/* ... */);
        $article->archive();

        $this->expectException(InvalidArticleException::class);
        $article->publish();
    }

    public function testTitleValidatesLength(): void
    {
        $this->expectException(InvalidArticleException::class);
        Title::fromString('ab'); // Too short
    }
}
```

**Benefits**:
- Fast (no I/O)
- Isolated (no dependencies)
- Tests actual business rules

### Unit Testing Application

Test use case orchestration with mocked repositories:

```php
class ArticleServiceTest extends TestCase
{
    public function testCreateArticleSavesToRepository(): void
    {
        $repository = $this->createMock(ArticleRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(Article::class));

        $service = new ArticleService($repository);
        $response = $service->create(new CreateArticleRequest(
            title: 'Test Article',
            content: 'Test content here',
            author: 'John Doe',
        ));

        $this->assertInstanceOf(ArticleResponse::class, $response);
        $this->assertEquals('Test Article', $response->title);
    }
}
```

**Benefits**:
- Tests use case flow
- Fast (mocked dependencies)
- Verifies coordination logic

### Integration Testing Infrastructure

Test with real dependencies:

```php
class DbArticleRepositoryTest extends TestCase
{
    private Connection $connection;
    private DbArticleRepository $repository;

    protected function setUp(): void
    {
        $this->connection = /* create test DB connection */;
        $this->repository = new DbArticleRepository($this->connection);
    }

    public function testSaveAndRetrieveArticle(): void
    {
        $article = Article::create(
            id: ArticleId::generate(),
            title: Title::fromString('Test'),
            content: Content::fromString('Test content'),
            author: Author::fromString('John'),
        );

        $this->repository->save($article);
        $retrieved = $this->repository->findById($article->getId());

        $this->assertNotNull($retrieved);
        $this->assertEquals($article->getId(), $retrieved->getId());
    }
}
```

**Benefits**:
- Tests real database operations
- Catches SQL errors
- Verifies data mapping

---

## Common Patterns

### Factory Methods

Entities use static factory methods for creation:

```php
public static function create(...): self
{
    // Validate invariants
    // Return entity in valid state
}

public static function reconstitute(...): self
{
    // For hydration from database
    // Skip validation (data already validated)
}
```

### Value Object Encapsulation

Value objects encapsulate validation:

```php
final readonly class Email extends BaseValueObject
{
    private function __construct(private string $value)
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Invalid email');
        }
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function getValue(): string
    {
        return $this->value;
    }
}
```

### Repository Pattern

Repositories abstract persistence:

```php
// Domain defines interface
interface UserRepositoryInterface
{
    public function save(User $user): void;
    public function findByEmail(Email $email): ?User;
}

// Infrastructure implements
class DbUserRepository implements UserRepositoryInterface
{
    public function save(User $user): void
    {
        $this->connection->table('users')->insert($this->toRow($user));
    }

    private function toRow(User $user): array { /* ... */ }
    private function fromRow(array $row): User { /* ... */ }
}
```

---

## Benefits Recap

| Benefit | How It's Achieved |
|---------|-------------------|
| **Testability** | Domain has no external dependencies, easy to unit test |
| **Flexibility** | Swap implementations via DI (InMemory → Database) |
| **Maintainability** | Clear boundaries, single responsibility per layer |
| **Framework Independence** | Domain doesn't know about Mezzio, HTTP, databases |
| **Team Scalability** | Teams work on modules independently |

---

## Common Mistakes to Avoid

1. ❌ **Putting business logic in handlers**
   - ✅ Handlers should delegate to application services

2. ❌ **Application services with business logic**
   - ✅ Application orchestrates, domain contains logic

3. ❌ **Domain depending on infrastructure**
   - ✅ Domain defines interfaces, infrastructure implements

4. ❌ **Anemic domain model** (entities as data holders)
   - ✅ Rich domain model (entities with behavior)

5. ❌ **Too many layers**
   - ✅ Keep it simple: Domain → Application → Infrastructure

---

## Further Reading

- **Hexagonal Architecture** by Alistair Cockburn
- **Domain-Driven Design** by Eric Evans
- **Clean Architecture** by Robert C. Martin
- **Implementing Domain-Driven Design** by Vaughn Vernon

---

## Need Help?

See `README.md` for practical examples and quick start guide.
