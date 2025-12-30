# Mezzio Skeleton with Architecture Choice

> Mezzio skeleton with architecture choice: **Minimal** or **Hexagonal (DDD)**. Modern PHP 8.4+ development with optional packages.

[![PHP Version](https://img.shields.io/badge/php-%5E8.4-blue)](https://www.php.net/)
[![Mezzio](https://img.shields.io/badge/mezzio-%5E3.25-purple)](https://docs.mezzio.dev/)
[![Docker](https://img.shields.io/badge/docker-ready-brightgreen)](https://www.docker.com/)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE.md)

## Overview

A modern Mezzio skeleton that lets you **choose your architecture** during setup:

- **PHP 8.4** with strict types
- **Mezzio Framework** for PSR-15 middleware
- **Laminas ServiceManager** for dependency injection
- **FastRoute** for high-performance routing
- **Docker** ready with PHP 8.4 + Nginx
- **Quality tools**: PHPStan (level 9), PHP_CodeSniffer, PHPUnit
- **Architecture choice**: Minimal or Hexagonal (DDD)

## Architecture Options

During the interactive setup, you'll choose between two architectural approaches:

### 🏗️ Minimal Architecture
**Best for**: Small to medium projects, APIs, microservices, rapid prototyping

- Simple, flat structure
- Request handlers in `Application/Handler/`
- Straightforward and easy to understand
- Quick to get started

### 🎯 Hexagonal Architecture (Ports & Adapters / DDD)
**Best for**: Complex domains, large teams, long-term maintainability

- Domain-Driven Design with clear layer separation
- Business logic independent of framework
- Modular structure (Core, HealthCheck, Article example modules)
- Ports & Adapters pattern
- Comprehensive ARCHITECTURE.md guide included

**After setup**, your project will have an architecture-specific README with detailed guidance for your chosen approach.

## Prerequisites

- Docker and Docker Compose

## Getting Started

### With Composer (One Command)

```bash
composer create-project methorz/mezzio-hexagonal-skeleton my-project
```

That's it! The setup will automatically:
- Run the interactive package installer
- Install all dependencies via Docker
- Build and start containers
- Launch your application at **http://localhost:8081**

**No PHP required on your host machine** - everything runs in Docker!

### Without Composer (Two Commands)

```bash
git clone https://github.com/MethorZ/mezzio-hexagonal-skeleton.git my-project
cd my-project
./setup.sh
```

Same automated process runs from here. Visit **http://localhost:8081** when complete.

## What Happens During Setup

The setup process is fully automated:

1. **Architecture selection** - Choose between Minimal or Hexagonal architecture
2. **Interactive package selection** - Choose which optional packages to install (Monolog, Validator, Database, etc.)
3. **Package configuration** - ConfigProviders and middleware automatically registered
4. **Dependency installation** - Selected packages installed via Docker
5. **Docker image building** - Custom PHP 8.4 + Nginx images built
6. **Container startup** - Application containers started
7. **Development mode** - Development environment configured
8. **Verification & cleanup** - Setup verified, then setup files automatically removed

After successful setup, the project is production-ready with no setup artifacts left behind.

### Daily Development

```bash
make start         # Start containers
make stop          # Stop containers
make shell         # Access backend shell
make logs-f        # View logs
make cs-fix        # Run code style fixes
make quality       # Run all quality checks
```

**Application URL**: http://localhost:8081

### Troubleshooting

If setup fails:
- Setup files are **kept** for debugging
- Check logs: `make logs` or `docker-compose logs`
- After fixing issues, retry: `./setup.sh`
- If setup succeeds on retry, files are cleaned up automatically

## Optional Packages

The skeleton includes an **interactive installer** that prompts for optional packages when you create a new project. The installer will:

- Ask which packages you want to install
- Update `composer.json` with your selections
- Copy relevant configuration files
- Remove itself from your new project

### Core Infrastructure
- **Monolog** - Application logging
- **Symfony Validator** - Request validation with attributes
- **Symfony Cache** - Redis, APCu, filesystem caching
- **Symfony Console** - CLI command support

### MethorZ Packages
- **methorz/http-dto** - Auto-map JSON requests to typed PHP objects
- **methorz/http-problem-details** - RFC 7807 JSON error responses
- **methorz/http-request-logger** - HTTP request/response logging middleware
- **methorz/http-cache-middleware** - HTTP caching with ETag/304
- **methorz/openapi-generator** - Auto-generate OpenAPI/Swagger docs

### Database Layer
- **methorz/swift-db** - High-performance MySQL layer with Laravel-style query builder
  - Bulk operations, master/slave support, query builder
  - See: https://github.com/MethorZ/swift-db

### Adding Packages Later

To add packages after initial setup:

```bash
# From project root
make shell
# Inside container:
composer require methorz/swift-db
```

## Project Structure

The project structure depends on your architecture choice:

- **Minimal**: Simple `backend/src/App/Application/` structure
- **Hexagonal**: Modular structure with `Core/`, `HealthCheck/`, `Article/` modules

**Example (Minimal Architecture)**:
```
project/
├── backend/
│   ├── src/
│   │   └── App/                    # Application module
│   │       └── Application/
│   │           ├── Config/         # ConfigProvider
│   │           └── Handler/        # HTTP handlers
│   ├── config/                     # Configuration files
│   ├── public/                     # Web root
│   ├── composer.json
│   ├── phpstan.neon
│   ├── phpcs.xml.dist
│   └── phpunit.xml.dist
├── docker/                         # Docker configuration
├── docker-compose.yml
├── Makefile
└── README.md
```

See your architecture-specific README after project creation for detailed structure documentation.

## Command Reference

All commands run inside Docker containers - no PHP required on your host machine!

```bash
make help          # Show all available commands
make start         # Start development environment
make stop          # Stop containers
make restart       # Restart containers
make shell         # Access backend shell (inside container)
make logs          # View container logs
make logs-f        # Follow container logs
make cs-check      # Check code style (PSR-12)
make cs-fix        # Auto-fix code style issues
make analyze       # Run PHPStan (level 9)
make phpunit       # Run PHPUnit tests
make quality       # Run all quality checks (style + analysis + tests)
```

## Adding Your Code

1. Add handlers to `backend/src/App/Application/Handler/`
2. Register routes in `backend/src/App/Application/Config/ConfigProvider.php`
3. Add services and factories as needed

Example handler:

```php
<?php

declare(strict_types=1);

namespace App\Application\Handler;

use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class MyHandler implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return new JsonResponse(['message' => 'Hello World']);
    }
}
```

## Configuration

Environment configuration files are in `backend/config/autoload/`:

- `app.development.php` - Development settings
- `app.production.php` - Production settings
- `app.testing.php` - Test settings

## Quality Tools

All quality tools are pre-configured and ready to use:

- **PHPStan** - Level 9 static analysis
- **PHP_CodeSniffer** - PSR-12 code style checking
- **PHP Code Beautifier** - Automatic code style fixing
- **PHPUnit** - Unit testing framework

**Run quality checks:**

```bash
make quality
```

This runs: code style check → static analysis → unit tests (all inside Docker)

## Docker Services

- **Backend**: PHP 8.4-FPM + Nginx (Port 8081)
- **Database**: MySQL 8.0 (Port 33060) - optional

### Container Naming

Docker Compose automatically names containers based on your project folder name:
- If your project is in `my-project/`, containers will be named `my-project-backend-1` and `my-project-database-1`
- This allows multiple projects to run simultaneously without conflicts

### Port Configuration

Each project needs unique ports if running multiple projects simultaneously. Edit your `.env` file:

```bash
BACKEND_PORT=8081  # Change to 8082, 8083, etc. for other projects
DB_PORT=33060      # Change to 33061, 33062, etc. for other projects
```

## License

MIT License - see [LICENSE.md](LICENSE.md)

---

**Built with Mezzio and modern PHP practices**
