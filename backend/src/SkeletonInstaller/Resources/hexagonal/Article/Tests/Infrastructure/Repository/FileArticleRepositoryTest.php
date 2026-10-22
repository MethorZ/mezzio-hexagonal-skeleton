<?php

declare(strict_types=1);

namespace Article\Tests\Infrastructure\Repository;

use Article\Domain\Entity\Article;
use Article\Domain\Enum\ArticleStatus;
use Article\Domain\ValueObject\ArticleId;
use Article\Domain\ValueObject\Author;
use Article\Domain\ValueObject\Content;
use Article\Domain\ValueObject\Title;
use Article\Infrastructure\Repository\FileArticleRepository;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use UnexpectedValueException;

final class FileArticleRepositoryTest extends TestCase
{
    private string $directory;
    private string $file;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/article-repository-' . bin2hex(random_bytes(6));
        $this->file      = $this->directory . '/nested/articles.json';
    }

    protected function tearDown(): void
    {
        if (is_file($this->file)) {
            unlink($this->file);
        }

        foreach ([$this->directory . '/nested', $this->directory] as $directory) {
            if (is_dir($directory)) {
                rmdir($directory);
            }
        }
    }

    public function testPersistsArticlesAcrossRepositoryInstances(): void
    {
        $article = $this->article('First article');
        $article->publish();

        (new FileArticleRepository($this->file))->save($article);

        $found = (new FileArticleRepository($this->file))->findById($article->getId());

        $this->assertNotNull($found);
        $this->assertTrue($found->getId()->equals($article->getId()));
        $this->assertSame('First article', $found->getTitle()->getValue());
        $this->assertSame($article->getContent()->getValue(), $found->getContent()->getValue());
        $this->assertSame($article->getAuthor()->getValue(), $found->getAuthor()->getValue());
        $this->assertSame(ArticleStatus::PUBLISHED, $found->getStatus());
        $this->assertEquals($article->getCreatedAt(), $found->getCreatedAt());
        $this->assertEquals($article->getUpdatedAt(), $found->getUpdatedAt());
        $this->assertEquals($article->getPublishedAt(), $found->getPublishedAt());
    }

    public function testSaveReplacesAnExistingArticle(): void
    {
        $repository = new FileArticleRepository($this->file);
        $article    = $this->article('Original title');
        $repository->save($article);

        $article->update(Title::fromString('Updated title'), $article->getContent());
        $repository->save($article);

        $this->assertCount(1, $repository->findAll());
        $this->assertSame('Updated title', $repository->findById($article->getId())?->getTitle()->getValue());
    }

    public function testFindAllReturnsNewestArticlesFirst(): void
    {
        $repository = new FileArticleRepository($this->file);
        $older      = $this->article('Older article', new DateTimeImmutable('2026-01-01 10:00:00'));
        $newer      = $this->article('Newer article', new DateTimeImmutable('2026-02-01 10:00:00'));

        $repository->save($older);
        $repository->save($newer);

        $titles = array_map(static fn (Article $article): string => $article->getTitle()->getValue(), $repository->findAll());

        $this->assertSame(['Newer article', 'Older article'], $titles);
    }

    public function testFindByStatusOnlyReturnsMatchingArticles(): void
    {
        $repository = new FileArticleRepository($this->file);
        $draft      = $this->article('Draft article');
        $published  = $this->article('Published article');
        $published->publish();

        $repository->save($draft);
        $repository->save($published);

        $result = $repository->findByStatus(ArticleStatus::PUBLISHED);

        $this->assertCount(1, $result);
        $this->assertTrue($result[0]->getId()->equals($published->getId()));
    }

    public function testDeleteRemovesTheArticle(): void
    {
        $repository = new FileArticleRepository($this->file);
        $article    = $this->article('Short-lived article');
        $repository->save($article);

        $repository->delete($article->getId());

        $this->assertFalse($repository->exists($article->getId()));
        $this->assertNull($repository->findById($article->getId()));
    }

    public function testReturnsNothingWhenTheFileDoesNotExistYet(): void
    {
        $repository = new FileArticleRepository($this->file);

        $this->assertSame([], $repository->findAll());
        $this->assertFalse($repository->exists(ArticleId::generate()));
        $this->assertFileDoesNotExist($this->file);
    }

    public function testRejectsInvalidRecords(): void
    {
        mkdir(dirname($this->file), 0775, true);
        file_put_contents($this->file, json_encode([ArticleId::generate()->getValue() => ['title' => 42]]));

        $this->expectException(UnexpectedValueException::class);

        (new FileArticleRepository($this->file))->findAll();
    }

    private function article(string $title, ?DateTimeImmutable $createdAt = null): Article
    {
        if ($createdAt === null) {
            return Article::create(
                ArticleId::generate(),
                Title::fromString($title),
                Content::fromString('Content that is long enough for the domain rules.'),
                Author::fromString('Jane Doe'),
            );
        }

        return Article::reconstitute(
            ArticleId::generate(),
            Title::fromString($title),
            Content::fromString('Content that is long enough for the domain rules.'),
            Author::fromString('Jane Doe'),
            ArticleStatus::DRAFT,
            $createdAt,
            $createdAt,
        );
    }
}
