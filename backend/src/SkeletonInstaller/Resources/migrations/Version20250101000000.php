<?php

declare(strict_types=1);

namespace Database\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Example Migration - Create articles table
 *
 * This is an example migration showing the standard database schema structure.
 *
 * Standards:
 * - id BIGINT AUTO_INCREMENT PRIMARY KEY as FIRST column
 * - Business columns (uuid, data fields, enums)
 * - updated_at and created_at as LAST TWO columns with TIMESTAMP(3)
 *
 * Generate your own migrations with:
 * vendor/bin/doctrine-migrations generate
 */
final class Version20250101000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create articles table - example migration with standard schema structure';
    }

    public function up(Schema $schema): void
    {
        // Create articles table
        $this->addSql(
            <<<'SQL'
            CREATE TABLE articles (
                -- ALWAYS FIRST: Auto-increment PK
                id BIGINT AUTO_INCREMENT PRIMARY KEY,

                -- Business columns
                uuid VARCHAR(36) NOT NULL UNIQUE COMMENT 'Domain identifier',
                title VARCHAR(200) NOT NULL,
                content TEXT NOT NULL,
                author VARCHAR(100) NOT NULL,
                status ENUM('draft', 'published', 'archived') NOT NULL DEFAULT 'draft',
                published_at TIMESTAMP(3) NULL,

                -- ALWAYS LAST TWO: Timestamps with millisecond precision
                updated_at TIMESTAMP(3) NULL ON UPDATE CURRENT_TIMESTAMP(3),
                created_at TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),

                -- Indexes
                INDEX idx_uuid (uuid),
                INDEX idx_status (status),
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL
        );
    }

    public function down(Schema $schema): void
    {
        // Rollback: drop articles table
        $this->addSql('DROP TABLE articles');
    }
}

