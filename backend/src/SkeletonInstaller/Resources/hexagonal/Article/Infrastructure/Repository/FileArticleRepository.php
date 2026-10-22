<?php

declare(strict_types=1);

namespace Article\Infrastructure\Repository;

use Article\Domain\Entity\Article;
use Article\Domain\Enum\ArticleStatus;
use Article\Domain\Port\ArticleRepositoryInterface;
use Article\Domain\ValueObject\ArticleId;
use Article\Domain\ValueObject\Author;
use Article\Domain\ValueObject\Content;
use Article\Domain\ValueObject\Title;
use DateTimeImmutable;
use RuntimeException;
use UnexpectedValueException;

/**
 * File Article Repository (Outbound Adapter)
 *
 * Stores articles as JSON in a single file, so they survive across requests without a database.
 * Concurrent requests are serialised with file locks.
 *
 * Intended for development and demos. For production, use DbArticleRepository (methorz/swift-db).
 */
final readonly class FileArticleRepository implements ArticleRepositoryInterface
{
    private const string DATE_FORMAT = 'Y-m-d\TH:i:s.uP';

    public function __construct(
        private string $filePath,
    ) {
    }

    public function save(Article $article): void
    {
        $this->modify(function (array $records) use ($article): array {
            $records[$article->getId()->getValue()] = $this->toRecord($article);

            return $records;
        });
    }

    public function findById(ArticleId $id): ?Article
    {
        $record = $this->read()[$id->getValue()] ?? null;

        return $record !== null ? $this->fromRecord($id->getValue(), $record) : null;
    }

    /**
     * @return array<Article>
     */
    public function findAll(): array
    {
        $articles = [];

        foreach ($this->read() as $id => $record) {
            $articles[] = $this->fromRecord($id, $record);
        }

        usort(
            $articles,
            static fn (Article $a, Article $b): int => $b->getCreatedAt() <=> $a->getCreatedAt(),
        );

        return $articles;
    }

    /**
     * @return array<Article>
     */
    public function findByStatus(ArticleStatus $status): array
    {
        return array_values(
            array_filter(
                $this->findAll(),
                static fn (Article $article): bool => $article->getStatus() === $status,
            ),
        );
    }

    public function delete(ArticleId $id): void
    {
        $this->modify(static function (array $records) use ($id): array {
            unset($records[$id->getValue()]);

            return $records;
        });
    }

    public function exists(ArticleId $id): bool
    {
        return array_key_exists($id->getValue(), $this->read());
    }

    /**
     * @return array<string, array<mixed>>
     */
    private function read(): array
    {
        if (! is_file($this->filePath)) {
            return [];
        }

        $handle = $this->open('r');

        try {
            if (! flock($handle, LOCK_SH)) {
                throw new RuntimeException(sprintf('Could not lock "%s" for reading', $this->filePath));
            }

            return $this->decode(stream_get_contents($handle));
        } finally {
            fclose($handle);
        }
    }

    /**
     * @param callable(array<string, array<mixed>>): array<string, array<mixed>> $change
     */
    private function modify(callable $change): void
    {
        $directory = dirname($this->filePath);

        if (! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
            throw new RuntimeException(sprintf('Could not create directory "%s"', $directory));
        }

        $handle = $this->open('c+');

        try {
            if (! flock($handle, LOCK_EX)) {
                throw new RuntimeException(sprintf('Could not lock "%s" for writing', $this->filePath));
            }

            $json = json_encode(
                $change($this->decode(stream_get_contents($handle))),
                JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
            );

            if (
                ! ftruncate($handle, 0)
                || ! rewind($handle)
                || fwrite($handle, $json) !== strlen($json)
                || ! fflush($handle)
            ) {
                throw new RuntimeException(sprintf('Could not write articles to "%s"', $this->filePath));
            }
        } finally {
            fclose($handle);
        }
    }

    /**
     * @return resource
     */
    private function open(string $mode)
    {
        $handle = fopen($this->filePath, $mode);

        if ($handle === false) {
            throw new RuntimeException(sprintf('Could not open "%s"', $this->filePath));
        }

        return $handle;
    }

    /**
     * @return array<string, array<mixed>>
     */
    private function decode(string|false $contents): array
    {
        if ($contents === false) {
            throw new RuntimeException(sprintf('Could not read "%s"', $this->filePath));
        }

        if (trim($contents) === '') {
            return [];
        }

        $data = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

        if (! is_array($data)) {
            throw new UnexpectedValueException(sprintf('"%s" must contain a JSON object', $this->filePath));
        }

        $records = [];

        foreach ($data as $id => $record) {
            if (! is_string($id) || ! is_array($record)) {
                throw new UnexpectedValueException(sprintf('"%s" contains an invalid article record', $this->filePath));
            }

            $records[$id] = $record;
        }

        return $records;
    }

    /**
     * @return array<string, string|null>
     */
    private function toRecord(Article $article): array
    {
        return [
            'title'        => $article->getTitle()->getValue(),
            'content'      => $article->getContent()->getValue(),
            'author'       => $article->getAuthor()->getValue(),
            'status'       => $article->getStatus()->value,
            'created_at'   => $article->getCreatedAt()->format(self::DATE_FORMAT),
            'updated_at'   => $article->getUpdatedAt()->format(self::DATE_FORMAT),
            'published_at' => $article->getPublishedAt()?->format(self::DATE_FORMAT),
        ];
    }

    /**
     * @param array<mixed> $record
     */
    private function fromRecord(string $id, array $record): Article
    {
        $status = ArticleStatus::tryFrom($this->stringField($id, $record, 'status'))
            ?? throw new UnexpectedValueException(sprintf('Article "%s" has an unknown status', $id));

        $publishedAt = $record['published_at'] ?? null;

        return Article::reconstitute(
            id: ArticleId::fromString($id),
            title: Title::fromString($this->stringField($id, $record, 'title')),
            content: Content::fromString($this->stringField($id, $record, 'content')),
            author: Author::fromString($this->stringField($id, $record, 'author')),
            status: $status,
            createdAt: new DateTimeImmutable($this->stringField($id, $record, 'created_at')),
            updatedAt: new DateTimeImmutable($this->stringField($id, $record, 'updated_at')),
            publishedAt: $publishedAt !== null
                ? new DateTimeImmutable($this->stringField($id, $record, 'published_at'))
                : null,
        );
    }

    /**
     * @param array<mixed> $record
     */
    private function stringField(string $id, array $record, string $field): string
    {
        $value = $record[$field] ?? null;

        if (! is_string($value)) {
            throw new UnexpectedValueException(sprintf(
                'Field "%s" of article "%s" must be a string, got %s',
                $field,
                $id,
                get_debug_type($value),
            ));
        }

        return $value;
    }
}
