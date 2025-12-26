# Database Migrations

This directory contains your database migrations managed by Doctrine Migrations.

## Quick Start

### Check Status

```bash
vendor/bin/doctrine-migrations status
```

### Run Migrations

```bash
vendor/bin/doctrine-migrations migrate
```

### Generate New Migration

```bash
vendor/bin/doctrine-migrations generate
```

## Common Commands

| Command | Description |
|---------|-------------|
| `list` | Show all available migrations |
| `status` | Show migration status |
| `migrate` | Execute migrations (up to latest) |
| `migrate prev` | Migrate to previous version |
| `migrate first` | Migrate to first version |
| `generate` | Generate new blank migration |
| `version --add xxx` | Mark migration as executed |
| `version --delete xxx` | Mark migration as not executed |

## Migration Structure

Each migration has two methods:

- **`up()`** - Executed when migrating forward
- **`down()`** - Executed when rolling back

Example:

```php
public function up(Schema $schema): void
{
    $this->addSql('CREATE TABLE users (...)');
}

public function down(Schema $schema): void
{
    $this->addSql('DROP TABLE users');
}
```

## Configuration

Migrations use two configuration files:

### Root-level Files (Used by CLI)
- `migrations.php` - Migration settings (namespace, paths, table storage)
- `migrations-db.php` - Database connection (reads from config/config.php)

### Application Config
- `config/autoload/migrations.php` - App-level migration settings (for programmatic access if needed)

**Settings:**
- **Namespace**: `Database\Migrations`
- **Directory**: `migrations/`
- **Table**: `doctrine_migration_versions`

The CLI (`vendor/bin/doctrine-migrations`) automatically discovers `migrations.php` and `migrations-db.php` in the project root.

## Environment Variables

Override defaults via environment variables:

- `DB_HOST` - Database host (default: database)
- `DB_PORT` - Database port (default: 3306)
- `DB_NAME` - Database name (default: app_db)
- `DB_USER` - Database user (default: app_user)
- `DB_PASSWORD` - Database password

## Tips

1. **Always review generated SQL** before running migrations
2. **Test migrations** in development before production
3. **Write reversible migrations** (implement `down()`)
4. **Never modify executed migrations** - create new ones instead

## Documentation

Full documentation: https://www.doctrine-project.org/projects/migrations.html

