<?php

declare(strict_types=1);

namespace SkeletonInstaller;

use Composer\Composer;
use Composer\Factory;
use Composer\IO\IOInterface;
use Composer\Json\JsonFile;
use Composer\Script\Event;

use function copy;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function is_dir;
use function mkdir;
use function rmdir;
use function scandir;
use function sprintf;
use function strlen;
use function strpos;
use function substr_replace;
use function unlink;

/**
 * Skeleton Installer - Interactive package selection during composer create-project
 *
 * This class handles optional package selection and configuration during installation.
 * It removes itself from the project after installation is complete.
 */
class OptionalPackages
{
    public const INSTALL_NONE = 1;

    private const PACKAGE_GROUPS = [
        // === Core Infrastructure ===
        'logging' => [
            'question'       => 'Install Monolog? (application logging - log errors, debug info to files)',
            'packages'       => [
                'monolog/monolog' => '^3.9',
            ],
            'config'         => 'logging.global.php',
            'devOnly'        => false,
            // LoggerInterface factory is now in the main ConfigProvider
            'configProvider' => null,
            'middleware'     => null,
        ],
        'validation' => [
            'question'       => 'Install Symfony Validator? (validate request data with #[Assert\\*] attributes)',
            'packages'       => [
                'symfony/validator' => '^7.2',
            ],
            'config'         => 'validation.global.php',
            'devOnly'        => false,
            'configProvider' => null, // Library - configured via config file
            'middleware'     => null,
        ],
        'caching' => [
            'question'       => 'Install Symfony Cache? (application-level caching - Redis, APCu, filesystem)',
            'packages'       => [
                'symfony/cache' => '^7.0',
            ],
            'config'         => 'caching.global.php',
            'devOnly'        => false,
            'configProvider' => null, // Library - configured via config file
            'middleware'     => null,
        ],
        'console' => [
            'question'       => 'Install Symfony Console? (build CLI commands for your application)',
            'packages'       => [
                'symfony/console' => '^7.0',
            ],
            'config'         => null,
            'devOnly'        => false,
            'configProvider' => null, // Library - no auto-configuration needed
            'middleware'     => null,
        ],
        // === MethorZ Middleware Packages ===
        'methorz-dto' => [
            'question'       => 'Install methorz/http-dto? (auto-map JSON requests to typed PHP objects, zero-config)',
            'packages'       => [
                'methorz/http-dto' => '^2.1',
            ],
            'config'         => null,
            'devOnly'        => false,
            'configProvider' => '\\MethorZ\\Dto\\Integration\\Mezzio\\ConfigProvider::class',
            'middleware'     => null, // No global middleware, uses DtoHandlerWrapper per route
            'middlewarePos'  => null,
        ],
        'methorz-problem-details' => [
            'question'       => 'Install methorz/http-problem-details? (error handler middleware - RFC 7807 JSON responses)',
            'packages'       => [
                'methorz/http-problem-details' => 'dev-main',
            ],
            'config'         => null,
            'devOnly'        => false,
            'configProvider' => '\\MethorZ\\ProblemDetails\\Integration\\Mezzio\\ConfigProvider::class',
            'middleware'     => '\\MethorZ\\ProblemDetails\\Middleware\\ErrorHandlerMiddleware::class',
            'middlewarePos'  => 'first', // should be first to catch all errors
        ],
        'methorz-request-logger' => [
            'question'       => 'Install methorz/http-request-logger? (middleware to log all HTTP requests/responses)',
            'packages'       => [
                'methorz/http-request-logger' => '^1.1',
            ],
            'config'         => null,
            'devOnly'        => false,
            'configProvider' => '\\MethorZ\\RequestLogger\\Integration\\Mezzio\\ConfigProvider::class',
            'middleware'     => '\\MethorZ\\RequestLogger\\Middleware\\LoggingMiddleware::class',
            'middlewarePos'  => 'early', // early to log all requests
        ],
        'methorz-cache' => [
            'question'       => 'Install methorz/http-cache-middleware? (HTTP caching with ETag/304 responses)',
            'packages'       => [
                'methorz/http-cache-middleware' => 'dev-main',
            ],
            'config'         => null,
            'devOnly'        => false,
            'configProvider' => '\\MethorZ\\HttpCache\\Integration\\Mezzio\\ConfigProvider::class',
            'middleware'     => '\\MethorZ\\HttpCache\\Middleware\\CacheMiddleware::class',
            'middlewarePos'  => 'after-routing',
        ],
        'methorz-openapi' => [
            'question'       => 'Install methorz/openapi-generator? (auto-generate OpenAPI/Swagger docs from code)',
            'packages'       => [
                'methorz/openapi-generator' => 'dev-main',
            ],
            'config'         => 'openapi.yaml',
            'devOnly'        => false,
            'configProvider' => null, // CLI tool only, no ConfigProvider
            'middleware'     => null, // No middleware, just generates docs
        ],
        // === API Middleware ===
        'cors' => [
            'question'       => 'Install Mezzio CORS? (enable Cross-Origin Resource Sharing for API endpoints)',
            'packages'       => [
                'mezzio/mezzio-cors' => '^1.11',
            ],
            'config'         => 'cors.global.php',
            'devOnly'        => false,
            'configProvider' => '\\Mezzio\\Cors\\ConfigProvider::class',
            'middleware'     => null, // Already conditionally loaded in pipeline.php
        ],
        'jwt-auth' => [
            'question'       => 'Install methorz/jwt-auth-middleware? (JWT auth with HS256/RS256, zero-config)',
            'packages'       => [
                'methorz/jwt-auth-middleware' => '^1.1',
            ],
            'config'         => 'jwt-auth.global.php',
            'devOnly'        => false,
            'configProvider' => '\\MethorZ\\JwtAuthMiddleware\\ConfigProvider::class',
            // Middleware works without config (passthrough mode) - only validates routes with auth.required = true
            'middleware'     => '\\MethorZ\\JwtAuthMiddleware\\Middleware\\JwtAuthenticationMiddleware::class',
            'middlewarePos'  => 'after-routing',
        ],
        // === Database Layer ===
        'swift-db' => [
            'question'       => 'Install methorz/swift-db? (high-performance MySQL layer with Laravel-style query builder)',
            'packages'       => [
                'methorz/swift-db' => '^1.0',
            ],
            'config'         => 'database.local.php.dist',
            'devOnly'        => false,
            'configProvider' => '\\MethorZ\\SwiftDb\\ConfigProvider::class',
            'middleware'     => null,
        ],
        'migrations' => [
            'question'       => 'Install Doctrine Migrations? (database schema versioning and migration management)',
            'packages'       => [
                'doctrine/migrations' => '^3.8',
            ],
            'config'         => 'migrations.global.php',
            'devOnly'        => false,
            'configProvider' => null, // Configured via config file, no auto-registration
            'middleware'     => null,
        ],
    ];

    private IOInterface $io;
    private string $projectRoot;

    /** @var array<string, mixed> */
    private array $composerDefinition;

    private JsonFile $composerJson;

    /** @var array<string> */
    private array $selectedPackages = [];

    /** @var string Architecture style: 'minimal' or 'hexagonal' */
    private string $architectureStyle = 'minimal';

    public function __construct(IOInterface $io, string $projectRoot)
    {
        $this->io = $io;
        $this->projectRoot = $projectRoot;
        $this->composerJson = new JsonFile($projectRoot . '/composer.json');
        $this->composerDefinition = $this->composerJson->read();
    }

    /**
     * Main entry point - called by Composer's post-create-project-cmd
     */
    public static function install(Event $event): void
    {
        $io = $event->getIO();
        $composer = $event->getComposer();

        // Get the project root (where composer.json is)
        $projectRoot = self::getProjectRoot($composer);

        $installer = new self($io, $projectRoot);
        $installer->run();
    }

    /**
     * Run the interactive installation
     */
    public function run(): void
    {
        $this->io->write('<info>');
        $this->io->write('  _   _                    ____  _        _      _              ');
        $this->io->write(' | | | | _____  ____ _   / ___|| | _____| | ___| |_ ___  _ __  ');
        $this->io->write(' | |_| |/ _ \\ \\/ / _` |  \\___ \\| |/ / _ \\ |/ _ \\ __/ _ \\| \'_ \\ ');
        $this->io->write(' |  _  |  __/>  < (_| |   ___) |   <  __/ |  __/ || (_) | | | |');
        $this->io->write(' |_| |_|\\___/_/\\_\\__,_|  |____/|_|\\_\\___|_|\\___|\\__\\___/|_| |_|');
        $this->io->write('</info>');
        $this->io->write('');
        $this->io->write('<comment>Setting up your new project...</comment>');
        $this->io->write('');

        // Prompt for architecture style first
        $this->promptArchitectureStyle();

        $this->io->write('');
        $this->io->write('<comment>Select which optional packages to install:</comment>');
        $this->io->write('');

        // Core Infrastructure packages
        $this->io->write('<info>--- Core Infrastructure ---</info>');
        $corePackages = ['logging', 'validation', 'caching', 'console'];
        foreach ($corePackages as $groupName) {
            $this->promptForPackageGroup($groupName, self::PACKAGE_GROUPS[$groupName]);
        }

        $this->io->write('');
        $this->io->write('<info>--- API Middleware ---</info>');
        $apiPackages = ['cors', 'jwt-auth'];
        foreach ($apiPackages as $groupName) {
            $this->promptForPackageGroup($groupName, self::PACKAGE_GROUPS[$groupName]);
        }

        $this->io->write('');
        $this->io->write('<info>--- MethorZ Packages ---</info>');
        $methorzPackages = [
            'methorz-dto',
            'methorz-problem-details',
            'methorz-request-logger',
            'methorz-cache',
            'methorz-openapi',
        ];
        foreach ($methorzPackages as $groupName) {
            $this->promptForPackageGroup($groupName, self::PACKAGE_GROUPS[$groupName]);
        }

        $this->io->write('');
        $this->io->write('<info>--- Database Layer ---</info>');
        $databasePackages = ['swift-db', 'migrations'];
        foreach ($databasePackages as $groupName) {
            $this->promptForPackageGroup($groupName, self::PACKAGE_GROUPS[$groupName]);
        }

        // Update project name based on directory (do this FIRST before package selection writes)
        $this->updateProjectName();

        // Apply selected packages to composer.json
        $this->applyPackageSelections();

        // Copy config files for selected packages
        $this->copyConfigFiles();

        // Register ConfigProviders in config.php
        $this->registerConfigProviders();

        // Register middleware in pipeline.php
        $this->registerMiddleware();

        // Create project structure based on architecture choice
        if ($this->architectureStyle === 'hexagonal') {
            $this->createHexagonalStructure();
            // Clear config cache after hexagonal structure is created
            // This ensures the new ConfigProviders can be loaded
            $this->clearConfigCache();
        } else {
            $this->createMinimalStructure();
        }

        // Set up migrations infrastructure if Doctrine Migrations was selected
        $this->setupMigrations();

        // Set up OpenAPI generator if selected
        $this->setupOpenApi();

        // Remove installer from project
        $this->removeInstaller();

        $this->io->write('');
        $this->io->write('<info>✓ Package selection complete!</info>');
        $this->io->write('');
    }

    /**
     * Get the project root directory
     */
    private static function getProjectRoot(Composer $composer): string
    {
        // The project root is where the root composer.json is
        return dirname(Factory::getComposerFile());
    }

    /**
     * Prompt user for architecture style
     */
    private function promptArchitectureStyle(): void
    {
        $this->io->write('<info>--- Project Architecture ---</info>');
        $this->io->write('');
        $this->io->write('Choose your project architecture:');
        $this->io->write('');
        $this->io->write('  <comment>[1] Minimal</comment> - Simple flat structure, great for small/medium projects');
        $this->io->write('      src/App/Application/{Handler,Config,Request,Response}');
        $this->io->write('');
        $this->io->write('  <comment>[2] Hexagonal</comment> - Ports & Adapters pattern with DDD structure');
        $this->io->write('      src/{Module}/{Application,Domain,Infrastructure}');
        $this->io->write('      Full separation: Entities, ValueObjects, Ports, Adapters');
        $this->io->write('');

        $choice = $this->io->ask(
            '<question>Select architecture [1/2]:</question> ',
            '1',
        );

        if ($choice === '2') {
            $this->architectureStyle = 'hexagonal';
            $this->io->write('  <info>✓</info> Hexagonal architecture selected');
            $this->io->write('');
            $this->io->write('  <comment>Your project will include:</comment>');
            $this->io->write('  <comment>  - HealthCheck module as example bounded context</comment>');
            $this->io->write('  <comment>  - Core/ for shared domain elements</comment>');
            $this->io->write('  <comment>  - Base/ for shared infrastructure</comment>');
            $this->io->write('  <comment>  - Full Ports & Adapters pattern</comment>');
        } else {
            $this->architectureStyle = 'minimal';
            $this->io->write('  <info>✓</info> Minimal architecture selected');
        }
    }

    /**
     * Prompt user for a package group
     *
     * @param array<string, mixed> $config
     */
    private function promptForPackageGroup(string $groupName, array $config): void
    {
        /** @var string $question */
        $question = $config['question'];

        $answer = $this->io->askConfirmation(
            sprintf('<question>%s</question> [<comment>y</comment>/n] ', $question),
            true,
        );

        if ($answer) {
            $this->selectedPackages[] = $groupName;
            $this->io->write(sprintf('  <info>✓</info> %s will be installed', $groupName));
        } else {
            $this->io->write(sprintf('  <comment>○</comment> %s skipped', $groupName));
        }
    }

    /**
     * Apply selected packages to composer.json
     */
    private function applyPackageSelections(): void
    {
        if ($this->selectedPackages === []) {
            return;
        }

        $this->io->write('');
        $this->io->write('<info>Updating composer.json with selected packages...</info>');

        foreach ($this->selectedPackages as $groupName) {
            $config = self::PACKAGE_GROUPS[$groupName];

            /** @var array<string, string> $packages */
            $packages = $config['packages'];
            $section = $config['devOnly'] ? 'require-dev' : 'require';

            foreach ($packages as $package => $version) {
                $this->composerDefinition[$section][$package] = $version;
                $this->io->write(sprintf('  Added <info>%s</info>: %s', $package, $version));
            }
        }

        // Always add packages to backend/composer.json (unified approach for both architectures)
        $this->syncPackagesToBackendComposer();
    }

    /**
     * Copy config files for selected packages
     */
    private function copyConfigFiles(): void
    {
        $configDir = $this->projectRoot . '/backend/config/autoload';
        $templateDir = __DIR__ . '/Resources/config';
        $configProviderTemplateDir = __DIR__ . '/Resources/config-provider';

        foreach ($this->selectedPackages as $groupName) {
            $config = self::PACKAGE_GROUPS[$groupName];

            // Copy config file if specified
            if ($config['config'] !== null) {
                $sourceFile = $templateDir . '/' . $config['config'];
                $targetFile = $configDir . '/' . $config['config'];

                if (file_exists($sourceFile)) {
                    copy($sourceFile, $targetFile);
                    $this->io->write(sprintf('  Copied config: <info>%s</info>', $config['config']));
                }
            }

            // Copy ConfigProvider template if specified
            if (isset($config['configProviderTemplate'])) {
                $sourceFile = $configProviderTemplateDir . '/' . $config['configProviderTemplate'];

                // Determine target path based on namespace (e.g., App\Logging\Config -> src/App/Logging/Config)
                $namespace = $config['configProvider'];
                $namespace = str_replace('\\', '/', $namespace);
                $namespace = str_replace('::class', '', $namespace);
                $targetFile = $this->projectRoot . '/backend/src/' . $namespace . '.php';

                // Create directory if it doesn't exist
                $targetDir = dirname($targetFile);
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }

                if (file_exists($sourceFile)) {
                    copy($sourceFile, $targetFile);
                    $this->io->write(sprintf('  Created ConfigProvider: <info>%s</info>', basename(dirname($targetFile)) . '/ConfigProvider.php'));
                }
            }
        }
    }

    /**
     * Register ConfigProviders in config.php for selected packages
     */
    private function registerConfigProviders(): void
    {
        $providersToAdd = [];

        foreach ($this->selectedPackages as $groupName) {
            $config = self::PACKAGE_GROUPS[$groupName];
            if (isset($config['configProvider'])) {
                $providersToAdd[] = $config['configProvider'];
            }
        }

        if (empty($providersToAdd)) {
            return;
        }

        $this->io->write('');
        $this->io->write('<info>Registering ConfigProviders...</info>');

        $configFile = $this->projectRoot . '/backend/config/config.php';
        $content = file_get_contents($configFile);

        // Find the insertion point (before Application ConfigProvider)
        $marker = '    // Application';
        $position = strpos($content, $marker);

        if ($position === false) {
            $this->io->write('  <error>Could not find insertion point in config.php</error>');
            return;
        }

        // Build the providers section
        $providersSection = "    // Optional packages\n";
        foreach ($providersToAdd as $provider) {
            $providersSection .= "    $provider,\n";
        }
        $providersSection .= "\n";

        // Insert the providers
        $content = substr_replace($content, $providersSection, $position, 0);
        file_put_contents($configFile, $content);

        $this->io->write(sprintf('  Registered <info>%d</info> ConfigProvider(s)', count($providersToAdd)));
    }

    /**
     * Register middleware in pipeline.php for selected packages
     */
    private function registerMiddleware(): void
    {
        $middlewareToAdd = [
            'first'         => [],
            'early'         => [],
            'after-routing' => [],
        ];

        foreach ($this->selectedPackages as $groupName) {
            $config = self::PACKAGE_GROUPS[$groupName];
            if (isset($config['middleware']) && $config['middleware'] !== null) {
                $position = $config['middlewarePos'] ?? 'after-routing';
                $middlewareToAdd[$position][] = [
                    'class'   => $config['middleware'],
                    'comment' => $this->getMiddlewareComment($groupName),
                ];
            }
        }

        $hasMiddleware = false;
        foreach ($middlewareToAdd as $items) {
            if (! empty($items)) {
                $hasMiddleware = true;
                break;
            }
        }

        if (! $hasMiddleware) {
            return;
        }

        $this->io->write('');
        $this->io->write('<info>Registering middleware...</info>');

        $pipelineFile = $this->projectRoot . '/backend/config/pipeline.php';
        $content = file_get_contents($pipelineFile);

        // Add "first" middleware (after error handler comment)
        if (! empty($middlewareToAdd['first'])) {
            $marker = "    // \$app->pipe(ErrorHandler::class);\n";
            $position = strpos($content, $marker);
            if ($position !== false) {
                $middlewareCode = "\n";
                foreach ($middlewareToAdd['first'] as $mw) {
                    $middlewareCode .= "    // {$mw['comment']}\n";
                    $middlewareCode .= "    \$app->pipe({$mw['class']});\n\n";
                }
                $content = substr_replace($content, $marker . $middlewareCode, $position, strlen($marker));
            }
        }

        // Add "early" middleware (after ServerUrlMiddleware)
        if (! empty($middlewareToAdd['early'])) {
            $marker = "    \$app->pipe(ServerUrlMiddleware::class);\n";
            $position = strpos($content, $marker);
            if ($position !== false) {
                $middlewareCode = "\n";
                foreach ($middlewareToAdd['early'] as $mw) {
                    $middlewareCode .= "    // {$mw['comment']}\n";
                    $middlewareCode .= "    \$app->pipe({$mw['class']});\n";
                }
                $middlewareCode .= "\n";
                $content = substr_replace($content, $marker . $middlewareCode, $position, strlen($marker));
            }
        }

        // Add "after-routing" middleware (after RouteMiddleware)
        if (! empty($middlewareToAdd['after-routing'])) {
            $marker = "    \$app->pipe(RouteMiddleware::class);\n";
            $position = strpos($content, $marker);
            if ($position !== false) {
                $middlewareCode = "\n";
                foreach ($middlewareToAdd['after-routing'] as $mw) {
                    $middlewareCode .= "    // {$mw['comment']}\n";
                    $middlewareCode .= "    \$app->pipe({$mw['class']});\n";
                }
                $middlewareCode .= "\n";
                $content = substr_replace($content, $marker . $middlewareCode, $position, strlen($marker));
            }
        }

        file_put_contents($pipelineFile, $content);

        $totalMiddleware = count($middlewareToAdd['first']) + count($middlewareToAdd['early']) + count($middlewareToAdd['after-routing']);
        $this->io->write(sprintf('  Registered <info>%d</info> middleware', $totalMiddleware));
    }

    /**
     * Get descriptive comment for middleware
     */
    private function getMiddlewareComment(string $groupName): string
    {
        $comments = [
            'methorz-dto'             => 'HTTP DTO - Auto-map requests to typed objects',
            'methorz-problem-details' => 'Problem Details - RFC 7807 error responses',
            'methorz-request-logger'  => 'Request Logger - Log all HTTP requests/responses',
            'methorz-cache'           => 'HTTP Cache - ETag and 304 responses',
            'jwt-auth'                => 'JWT Authentication - Protect routes with JWT tokens',
        ];

        return $comments[$groupName] ?? 'Middleware';
    }

    /**
     * Update HomeHandler to use http-dto if selected
     */
    private function updateHandlerForDTO(): void
    {
        // Check if methorz-dto was selected
        if (! in_array('methorz-dto', $this->selectedPackages, true)) {
            return;
        }

        $this->io->write('');
        $this->io->write('<info>Setting up http-dto example...</info>');

        $handlerDir = $this->projectRoot . '/backend/src/App/Application/Handler';
        $requestDir = $this->projectRoot . '/backend/src/App/Application/Request';
        $responseDir = $this->projectRoot . '/backend/src/App/Application/Response';
        $configDir = $this->projectRoot . '/backend/src/App/Application/Config';
        $templateDir = __DIR__ . '/Resources';

        // Create Request and Response directories
        if (! is_dir($requestDir)) {
            mkdir($requestDir, 0755, true);
        }
        if (! is_dir($responseDir)) {
            mkdir($responseDir, 0755, true);
        }

        // Copy Request DTO
        $requestSource = $templateDir . '/request/HealthCheckRequest.php';
        $requestTarget = $requestDir . '/HealthCheckRequest.php';
        if (file_exists($requestSource)) {
            copy($requestSource, $requestTarget);
            $this->io->write('  Created <info>Request/HealthCheckRequest.php</info>');
        }

        // Copy Response DTO
        $responseSource = $templateDir . '/response/HealthCheckResponse.php';
        $responseTarget = $responseDir . '/HealthCheckResponse.php';
        if (file_exists($responseSource)) {
            copy($responseSource, $responseTarget);
            $this->io->write('  Created <info>Response/HealthCheckResponse.php</info>');
        }

        // Replace HomeHandler with DTO version
        $handlerSource = $templateDir . '/handlers/HomeHandler.dto.php';
        $handlerTarget = $handlerDir . '/HomeHandler.php';
        if (file_exists($handlerSource)) {
            copy($handlerSource, $handlerTarget);
            $this->io->write('  Updated <info>Handler/HomeHandler.php</info> (DtoHandlerInterface)');
        }

        // Replace ConfigProvider with DTO-enabled version
        $configSource = $templateDir . '/config-provider/ConfigProvider.dto.php';
        $configTarget = $configDir . '/ConfigProvider.php';
        if (file_exists($configSource)) {
            copy($configSource, $configTarget);
            $this->io->write('  Updated <info>Config/ConfigProvider.php</info> (DtoHandlerWrapper)');
        }

        $this->io->write('');
        $this->io->write('  <comment>✨ http-dto example ready!</comment>');
        $this->io->write('  <comment>Try: GET / or GET /?detailed=true</comment>');
    }

    /**
     * Set up console command example if Symfony Console was selected
     */
    private function setupConsoleExample(): void
    {
        if (! in_array('console', $this->selectedPackages, true)) {
            return;
        }

        $this->io->write('');
        $this->io->write('<info>Setting up Console example...</info>');

        $commandDir = $this->projectRoot . '/backend/src/App/Application/Command';
        $templateDir = __DIR__ . '/Resources';

        // Create Command directory
        if (! is_dir($commandDir)) {
            mkdir($commandDir, 0755, true);
        }

        // Copy example command
        $commandSource = $templateDir . '/commands/HealthCheckCommand.php';
        $commandTarget = $commandDir . '/HealthCheckCommand.php';
        if (file_exists($commandSource)) {
            copy($commandSource, $commandTarget);
            $this->io->write('  Created <info>Command/HealthCheckCommand.php</info>');
        }

        $this->io->write('');
        $this->io->write('  <comment>✨ Console example ready!</comment>');
        $this->io->write('  <comment>Run: php bin/console app:health-check</comment>');
    }

    /**
     * Set up migrations infrastructure if Doctrine Migrations was selected
     */
    private function setupMigrations(): void
    {
        if (! in_array('migrations', $this->selectedPackages, true)) {
            return;
        }

        $this->io->write('');
        $this->io->write('<info>Setting up Doctrine Migrations...</info>');

        $migrationsDir = $this->projectRoot . '/backend/migrations';
        $templateDir = __DIR__ . '/Resources/migrations';

        // Create migrations directory
        if (! is_dir($migrationsDir)) {
            mkdir($migrationsDir, 0755, true);
            $this->io->write('  Created <info>migrations/</info> directory');
        }

        // Copy migrations configuration files
        $configSource = $templateDir . '/migrations-cli.php';
        $configTarget = $this->projectRoot . '/backend/migrations.php';
        if (file_exists($configSource)) {
            copy($configSource, $configTarget);
            $this->io->write('  Created <info>migrations.php</info>');
        }

        $dbSource = $templateDir . '/migrations-db.php';
        $dbTarget = $this->projectRoot . '/backend/migrations-db.php';
        if (file_exists($dbSource)) {
            copy($dbSource, $dbTarget);
            $this->io->write('  Created <info>migrations-db.php</info>');
        }

        // Copy example migration
        $exampleSource = $templateDir . '/Version20250101000000.php';
        $exampleTarget = $migrationsDir . '/Version20250101000000.php';
        if (file_exists($exampleSource)) {
            copy($exampleSource, $exampleTarget);
            $this->io->write('  Created <info>migrations/Version20250101000000.php</info> (example)');
        }

        // Copy README for documentation
        $readmeSource = $templateDir . '/README.md';
        $readmeTarget = $migrationsDir . '/README.md';
        if (file_exists($readmeSource)) {
            copy($readmeSource, $readmeTarget);
            $this->io->write('  Created <info>migrations/README.md</info>');
        }

        $this->io->write('');
        $this->io->write('  <comment>✨ Migrations ready!</comment>');
        $this->io->write('  <comment>Common commands:</comment>');
        $this->io->write('  <comment>  vendor/bin/doctrine-migrations status</comment>');
        $this->io->write('  <comment>  vendor/bin/doctrine-migrations migrate</comment>');
        $this->io->write('  <comment>  vendor/bin/doctrine-migrations generate</comment>');
        $this->io->write('');
        $this->io->write('  <comment>See migrations/README.md for full documentation</comment>');
    }

    /**
     * Set up OpenAPI generator if selected
     */
    private function setupOpenApi(): void
    {
        if (! in_array('methorz-openapi', $this->selectedPackages, true)) {
            return;
        }

        $this->io->write('');
        $this->io->write('<info>Setting up OpenAPI Generator...</info>');

        // Copy bin/console if it doesn't exist
        $binDir = $this->projectRoot . '/backend/bin';
        if (! is_dir($binDir)) {
            mkdir($binDir, 0755, true);
        }

        $consoleSource = __DIR__ . '/Resources/bin/console';
        $consoleTarget = $binDir . '/console';
        if (file_exists($consoleSource) && ! file_exists($consoleTarget)) {
            copy($consoleSource, $consoleTarget);
            chmod($consoleTarget, 0755);
            $this->io->write('  Created <info>bin/console</info>');
        }

        // Create docs directory
        $docsDir = $this->projectRoot . '/backend/docs';
        if (! is_dir($docsDir)) {
            mkdir($docsDir, 0755, true);
            $this->io->write('  Created <info>docs/</info> directory');
        }

        // Add composer scripts
        if (! isset($this->composerDefinition['scripts'])) {
            $this->composerDefinition['scripts'] = [];
        }

        // Add docs script (pass config file path)
        $this->composerDefinition['scripts']['docs'] = 'php bin/console openapi:generate config/autoload/openapi.yaml';

        // Add to check script
        if (isset($this->composerDefinition['scripts']['check'])) {
            if (is_array($this->composerDefinition['scripts']['check'])) {
                if (! in_array('@docs', $this->composerDefinition['scripts']['check'], true)) {
                    $this->composerDefinition['scripts']['check'][] = '@docs';
                }
            }
        }

        // Add script description
        if (! isset($this->composerDefinition['scripts-descriptions'])) {
            $this->composerDefinition['scripts-descriptions'] = [];
        }
        $this->composerDefinition['scripts-descriptions']['docs'] = 'Generate OpenAPI documentation (YAML + JSON).';

        $this->io->write('  Added <info>composer docs</info> script');
        $this->io->write('  Added OpenAPI generation to <info>composer check</info>');
        $this->io->write('');
        $this->io->write('  <comment>✨ OpenAPI Generator ready!</comment>');
        $this->io->write('  <comment>Generate docs: composer docs</comment>');
        $this->io->write('  <comment>Auto-generated with: composer check</comment>');
    }

    /**
     * Create minimal project structure (current behavior)
     */
    private function createMinimalStructure(): void
    {
        $this->io->write('');
        $this->io->write('<info>Creating minimal architecture structure...</info>');

        $srcDir = $this->projectRoot . '/backend/src/App/Application';
        $templateDir = __DIR__ . '/Resources/minimal';

        // Copy minimal architecture templates
        $this->copyMinimalTemplates($srcDir, $templateDir);

        // Create logs directory
        $this->createLogsDirectory();

        // Set up console command example if Symfony Console was selected
        $this->setupConsoleExample();

        $this->io->write('');
        $this->io->write('  <comment>✨ Minimal structure created!</comment>');
        $this->io->write('  <comment>See README.md for usage guide</comment>');
    }

    /**
     * Copy minimal architecture templates
     */
    private function copyMinimalTemplates(string $srcDir, string $templateDir): void
    {
        // Create directories
        $directories = [
            'Handler',
            'Request',
            'Response',
            'Config',
        ];

        foreach ($directories as $dir) {
            $fullPath = $srcDir . '/' . $dir;
            if (!is_dir($fullPath)) {
                mkdir($fullPath, 0755, true);
            }
        }

        // Copy template files
        if (is_dir($templateDir)) {
            $this->copyDirectoryRecursive($templateDir, $srcDir);
            $this->io->write('  Created <info>App/Application/</info> structure');
        }
    }

    /**
     * Create hexagonal architecture structure
     */
    private function createHexagonalStructure(): void
    {
        $this->io->write('');
        $this->io->write('<info>Creating hexagonal architecture structure...</info>');

        $srcDir = $this->projectRoot . '/backend/src';
        $templateDir = __DIR__ . '/Resources/hexagonal';

        // Remove the default App structure
        $appDir = $srcDir . '/App';
        if (is_dir($appDir)) {
            $this->recursiveDelete($appDir);
        }

        // Create Core shared module (Domain only - no Application/Port)
        $this->createHexagonalModule('Core', $templateDir, $srcDir, true);

        // Create HealthCheck example module (simplified)
        $this->createHexagonalModule('HealthCheck', $templateDir, $srcDir, false);

        // Create Article CRUD example module
        $this->createHexagonalModule('Article', $templateDir, $srcDir, false);

        // Create logs directory
        $this->createLogsDirectory();

        // Update composer.json autoloading for hexagonal structure
        $this->updateAutoloadingForHexagonal();

        // Update backend/composer.json autoloading as well
        $this->updateBackendComposerJson();

        // Register module ConfigProviders
        $this->registerHexagonalConfigProviders();

        $this->io->write('');
        $this->io->write('  <comment>✨ Hexagonal structure created!</comment>');
        $this->io->write('  <comment>Modules: HealthCheck (minimal), Article (full CRUD example)</comment>');
        $this->io->write('  <comment>See README.md for architecture guide</comment>');
    }

    /**
     * Create a hexagonal module structure
     */
    private function createHexagonalModule(string $moduleName, string $templateDir, string $srcDir, bool $isCore): void
    {
        $moduleDir = $srcDir . '/' . $moduleName;
        $moduleTemplateDir = $templateDir . '/' . $moduleName;

        if (!is_dir($moduleTemplateDir)) {
            return; // Skip if template doesn't exist
        }

        // Copy template files recursively
        $this->copyDirectoryRecursive($moduleTemplateDir, $moduleDir);

        $this->io->write(sprintf('  Created module: <info>%s/</info>', $moduleName));
    }



    /**
     * Update autoloading for hexagonal structure
     *
     * Replaces the default App\ namespace with explicit hexagonal module namespaces:
     * - Core\ → backend/src/Core/
     * - HealthCheck\ → backend/src/HealthCheck/
     * - Article\ → backend/src/Article/
     *
     * Note: When adding new modules, you must add their namespace to composer.json.
     */
    private function updateAutoloadingForHexagonal(): void
    {
        // Ensure autoload exists and has psr-4
        if (!isset($this->composerDefinition['autoload'])) {
            $this->composerDefinition['autoload'] = [];
        }
        if (!isset($this->composerDefinition['autoload']['psr-4'])) {
            $this->composerDefinition['autoload']['psr-4'] = [];
        }

        // Remove default namespaces from skeleton
        unset($this->composerDefinition['autoload']['psr-4']['App\\']);
        unset($this->composerDefinition['autoload']['psr-4']['SkeletonInstaller\\']);

        // Add explicit hexagonal module namespaces
        $this->composerDefinition['autoload']['psr-4']['Core\\'] = 'backend/src/Core/';
        $this->composerDefinition['autoload']['psr-4']['HealthCheck\\'] = 'backend/src/HealthCheck/';
        $this->composerDefinition['autoload']['psr-4']['Article\\'] = 'backend/src/Article/';

        // Remove vendor-dir config for hexagonal projects
        // For hexagonal architecture, composer should run from backend/ directory
        // The vendor-dir config causes autoloader path issues in Docker
        if (isset($this->composerDefinition['config']['vendor-dir'])) {
            unset($this->composerDefinition['config']['vendor-dir']);
        }

        // Ensure autoload-dev exists and has psr-4
        if (!isset($this->composerDefinition['autoload-dev'])) {
            $this->composerDefinition['autoload-dev'] = [];
        }
        if (!isset($this->composerDefinition['autoload-dev']['psr-4'])) {
            $this->composerDefinition['autoload-dev']['psr-4'] = new \stdClass();
        }

        // Remove App\Tests\ namespace if it exists (test structure changes with hexagonal)
        if (isset($this->composerDefinition['autoload-dev']['psr-4']['App\\Tests\\'])) {
            unset($this->composerDefinition['autoload-dev']['psr-4']['App\\Tests\\']);
        }

        // If psr-4 is empty after cleanup, ensure it's still an object for valid JSON
        if (empty((array) $this->composerDefinition['autoload-dev']['psr-4'])) {
            $this->composerDefinition['autoload-dev']['psr-4'] = new \stdClass();
        }
    }

    /**
     * Update backend/composer.json with hexagonal module namespaces
     *
     * The backend composer.json is used by Docker when regenerating the autoloader,
     * so it needs to have the same namespaces but with paths relative to backend/src/
     */
    private function updateBackendComposerJson(): void
    {
        $backendComposerFile = $this->projectRoot . '/backend/composer.json';

        if (!file_exists($backendComposerFile)) {
            return;
        }

        $backendComposer = json_decode(file_get_contents($backendComposerFile), true);

        if (!$backendComposer) {
            $this->io->write('  <warning>Could not read backend/composer.json</warning>');
            return;
        }

        // Update autoload section with hexagonal namespaces (paths without backend/ prefix)
        if (!isset($backendComposer['autoload'])) {
            $backendComposer['autoload'] = [];
        }
        if (!isset($backendComposer['autoload']['psr-4'])) {
            $backendComposer['autoload']['psr-4'] = [];
        }

        // Remove default skeleton namespaces
        unset($backendComposer['autoload']['psr-4']['App\\']);
        unset($backendComposer['autoload']['psr-4']['SkeletonInstaller\\']);

        // Add hexagonal namespaces (relative to backend/src/)
        $backendComposer['autoload']['psr-4']['Core\\'] = 'src/Core/';
        $backendComposer['autoload']['psr-4']['HealthCheck\\'] = 'src/HealthCheck/';
        $backendComposer['autoload']['psr-4']['Article\\'] = 'src/Article/';

        // Remove test namespaces from autoload-dev
        if (isset($backendComposer['autoload-dev']['psr-4']['App\\Tests\\'])) {
            unset($backendComposer['autoload-dev']['psr-4']['App\\Tests\\']);
        }

        // Ensure autoload-dev psr-4 is an object (not array) for valid JSON
        if (empty($backendComposer['autoload-dev']['psr-4'])) {
            $backendComposer['autoload-dev']['psr-4'] = new \stdClass();
        }

        // Write updated backend/composer.json
        $json = json_encode($backendComposer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
        file_put_contents($backendComposerFile, $json);

        $this->io->write('  Updated <info>backend/composer.json</info> with hexagonal namespaces');
    }

    /**
     * Sync optional packages from root composer.json to backend/composer.json
     * This is needed for hexagonal projects where composer runs from backend directory
     */
    private function syncPackagesToBackendComposer(): void
    {
        $backendComposerFile = $this->projectRoot . '/backend/composer.json';

        if (!file_exists($backendComposerFile)) {
            return;
        }

        $backendComposer = json_decode(file_get_contents($backendComposerFile), true);

        if (!$backendComposer) {
            $this->io->write('  <warning>Could not read backend/composer.json for package sync</warning>');
            return;
        }

        // Ensure sections exist
        if (!isset($backendComposer['require'])) {
            $backendComposer['require'] = [];
        }
        if (!isset($backendComposer['require-dev'])) {
            $backendComposer['require-dev'] = [];
        }

        // Sync require packages (skip php constraint)
        if (isset($this->composerDefinition['require'])) {
            foreach ($this->composerDefinition['require'] as $package => $version) {
                if ($package !== 'php') {
                    $backendComposer['require'][$package] = $version;
                }
            }
        }

        // Sync require-dev packages
        if (isset($this->composerDefinition['require-dev'])) {
            foreach ($this->composerDefinition['require-dev'] as $package => $version) {
                $backendComposer['require-dev'][$package] = $version;
            }
        }

        // Write updated backend/composer.json
        $json = json_encode($backendComposer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
        file_put_contents($backendComposerFile, $json);

        $this->io->write('  Synced packages to <info>backend/composer.json</info>');
    }

    /**
     * Register hexagonal module ConfigProviders
     */
    private function registerHexagonalConfigProviders(): void
    {
        $configFile = $this->projectRoot . '/backend/config/config.php';
        $content = file_get_contents($configFile);

        // Replace App ConfigProvider with hexagonal module ConfigProviders
        // Try both with and without leading backslash
        $replacement = "HealthCheck\\Application\\Config\\ConfigProvider::class,\n    Article\\Application\\Config\\ConfigProvider::class";

        $content = str_replace(
            "App\\Application\\Config\\ConfigProvider::class",
            $replacement,
            $content,
        );

        $content = str_replace(
            "\\App\\Application\\Config\\ConfigProvider::class",
            "\\" . $replacement,
            $content,
        );

        file_put_contents($configFile, $content);
    }

    /**
     * Clear config cache to ensure new ConfigProviders can be loaded
     */
    private function clearConfigCache(): void
    {
        $cacheFile = $this->projectRoot . '/backend/data/cache/config-cache.php';

        if (file_exists($cacheFile)) {
            unlink($cacheFile);
            $this->io->write('  <info>✓</info> Cleared config cache');
        }
    }

    /**
     * Create logs directory with .gitkeep
     */
    private function createLogsDirectory(): void
    {
        $logsDir = $this->projectRoot . '/backend/data/logs';

        if (!is_dir($logsDir)) {
            mkdir($logsDir, 0775, true);
            file_put_contents($logsDir . '/.gitkeep', '');
            $this->io->write('  Created <info>data/logs/</info> directory');
        }
    }

    /**
     * Copy directory recursively
     */
    private function copyDirectoryRecursive(string $source, string $destination): void
    {
        if (!is_dir($source)) {
            return;
        }

        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }

        $files = scandir($source);
        if ($files === false) {
            return;
        }

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $sourcePath = $source . '/' . $file;
            $destPath = $destination . '/' . $file;

            if (is_dir($sourcePath)) {
                $this->copyDirectoryRecursive($sourcePath, $destPath);
            } else {
                copy($sourcePath, $destPath);
            }
        }
    }

    /**
     * Update project name in composer.json based on directory name
     * Note: This modifies $this->composerDefinition in memory but doesn't write to disk.
     * The write happens later in applyPackageSelections().
     */
    private function updateProjectName(): void
    {
        // If still using skeleton name, update it to project-specific name
        $currentName = $this->composerDefinition['name'] ?? '';

        // Only update if using the skeleton's original name
        if ($currentName === 'methorz/mezzio-hexagonal-skeleton') {
            $projectDir = basename($this->projectRoot);

            // Sanitize directory name to match Composer's package name requirements
            $projectName = strtolower($projectDir);
            $projectName = str_replace([' ', '_'], '-', $projectName);
            $projectName = preg_replace('/[^a-z0-9\-._]/', '', $projectName);
            $projectName = preg_replace('/[\-._]{2,}/', '-', $projectName);
            $projectName = trim($projectName, '-._');

            // Ensure it starts with a letter or number
            if (!empty($projectName) && !preg_match('/^[a-z0-9]/', $projectName)) {
                $projectName = 'app-' . $projectName;
            }

            // Fallback to 'unnamed' if name is empty, invalid, or suggests skeleton/testing
            if (empty($projectName) ||
                in_array($projectName, ['backend', 'hexa-skeleton', 'hexaskeleton', 'mezzio-hexagonal-skeleton', 'mezziohexagonalskeleton'], true)) {
                $projectName = 'unnamed';
            }

            // Update in-memory composer definition
            $this->composerDefinition['name'] = "app/{$projectName}";
            $this->composerDefinition['description'] = "Project: {$projectName}";

            $this->io->write("  ✓ Project name set to: <info>app/{$projectName}</info>");
        }
    }

    /**
     * Remove installer from the project
     */
    private function removeInstaller(): void
    {
        $this->io->write('');
        $this->io->write('<info>Cleaning up installer...</info>');

        // Remove installer from autoload
        $this->removeFromAutoload();

        // Remove installer directory
        $installerDir = $this->projectRoot . '/backend/src/SkeletonInstaller';
        $this->recursiveDelete($installerDir);

        // Remove installer scripts from composer.json
        $this->removeInstallerScripts();

        // Always remove root composer.json and quality configs (both architectures use backend/)
        $this->removeRootComposerJson();
        $this->removeRootQualityConfigs();

        $this->io->write('  <info>✓</info> Installer removed');

        // Replace README with project-specific version
        $this->replaceReadme();
    }

    /**
     * Remove root composer.json after installation
     * Both architectures use backend/composer.json since all PHP code is in backend/
     */
    private function removeRootComposerJson(): void
    {
        $rootComposerJson = $this->projectRoot . '/composer.json';
        $rootComposerLock = $this->projectRoot . '/composer.lock';

        // Delete root composer.json
        if (file_exists($rootComposerJson)) {
            if (@unlink($rootComposerJson)) {
                $this->io->write('  <info>✓</info> Removed root composer.json (use backend/composer.json)');
            } else {
                $this->io->write('  <warning>⚠</warning> Could not remove root composer.json');
            }
        }

        // Delete root composer.lock
        if (file_exists($rootComposerLock)) {
            @unlink($rootComposerLock);
        }
    }

    /**
     * Remove root quality config files after installation
     * Quality configs should only exist in backend/ since all PHP code is there
     */
    private function removeRootQualityConfigs(): void
    {
        $configFiles = [
            'phpcs.xml.dist',
            'phpstan.neon',
            'phpunit.xml.dist',
        ];

        foreach ($configFiles as $file) {
            $filePath = $this->projectRoot . '/' . $file;
            if (file_exists($filePath)) {
                if (@unlink($filePath)) {
                    $this->io->write("  <info>✓</info> Removed root $file (use backend/$file instead)");
                }
            }
        }
    }

    /**
     * Replace skeleton README with project-specific README
     */
    private function replaceReadme(): void
    {
        $readmeFile = $this->architectureStyle === 'hexagonal'
            ? 'README.hexagonal.md'
            : 'README.minimal.md';

        $readmeSource = __DIR__ . '/Resources/' . $readmeFile;
        $readmeTarget = $this->projectRoot . '/README.md';

        if (file_exists($readmeSource) && file_exists($readmeTarget)) {
            copy($readmeSource, $readmeTarget);
            $this->io->write('  <info>✓</info> README updated for new project');
        }

        // Copy ARCHITECTURE.md for hexagonal projects
        if ($this->architectureStyle === 'hexagonal') {
            $archSource = __DIR__ . '/Resources/ARCHITECTURE.hexagonal.md';
            $archTarget = $this->projectRoot . '/ARCHITECTURE.md';

            if (file_exists($archSource)) {
                copy($archSource, $archTarget);
                $this->io->write('  <info>✓</info> ARCHITECTURE.md added');
            }
        }
    }

    /**
     * Remove installer namespace from autoload
     */
    private function removeFromAutoload(): void
    {
        if (isset($this->composerDefinition['autoload']['psr-4']['SkeletonInstaller\\'])) {
            unset($this->composerDefinition['autoload']['psr-4']['SkeletonInstaller\\']);
        }
    }

    /**
     * Remove installer scripts from composer.json
     */
    private function removeInstallerScripts(): void
    {
        // Remove the post-create-project-cmd that calls the installer
        if (isset($this->composerDefinition['scripts']['post-create-project-cmd'])) {
            $scripts = $this->composerDefinition['scripts']['post-create-project-cmd'];

            if (is_array($scripts)) {
                $scripts = array_filter($scripts, function ($script) {
                    return ! str_contains($script, 'SkeletonInstaller');
                });
                $this->composerDefinition['scripts']['post-create-project-cmd'] = array_values($scripts);
            }

            // If empty, remove the key entirely
            if (empty($this->composerDefinition['scripts']['post-create-project-cmd'])) {
                unset($this->composerDefinition['scripts']['post-create-project-cmd']);
            }
        }
    }

    /**
     * Recursively delete a directory
     */
    private function recursiveDelete(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        $files = scandir($directory);
        if ($files === false) {
            return;
        }

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $path = $directory . '/' . $file;

            if (is_dir($path)) {
                $this->recursiveDelete($path);
            } else {
                unlink($path);
            }
        }

        rmdir($directory);
    }
}
