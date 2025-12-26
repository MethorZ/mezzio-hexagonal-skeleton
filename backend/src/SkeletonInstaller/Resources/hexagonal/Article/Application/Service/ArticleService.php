<?php

declare(strict_types=1);

namespace Article\Application\Service;

use Article\Application\Request\CreateArticleRequest;
use Article\Application\Request\GetArticleRequest;
use Article\Application\Request\UpdateArticleRequest;
use Article\Application\Response\ArticleListResponse;
use Article\Application\Response\ArticleResponse;
use Article\Domain\Entity\Article;
use Article\Domain\Enum\ArticleStatus;
use Article\Domain\Exception\ArticleNotFoundException;
use Article\Domain\Port\ArticleRepositoryInterface;
use Article\Domain\ValueObject\ArticleId;
use Article\Domain\ValueObject\Author;
use Article\Domain\ValueObject\Content;
use Article\Domain\ValueObject\Title;

/**
 * Article Service
 *
 * Application service implementing all article use cases.
 * This is the heart of the application layer, coordinating domain objects
 * and orchestrating the execution of use cases.
 */
final readonly class ArticleService
{
    public function __construct(
        private ArticleRepositoryInterface $articleRepository,
    ) {}

    /**
     * Create a new article
     */
    public function create(CreateArticleRequest $request): ArticleResponse
    {
        $article = Article::create(
            id: ArticleId::generate(),
            title: Title::fromString($request->title),
            content: Content::fromString($request->content),
            author: Author::fromString($request->author),
        );

        $this->articleRepository->save($article);

        return ArticleResponse::fromEntity($article);
    }

    /**
     * Get an article by ID
     */
    public function get(GetArticleRequest $request): ArticleResponse
    {
        $articleId = ArticleId::fromString($request->id);
        $article = $this->articleRepository->findById($articleId);

        if ($article === null) {
            throw ArticleNotFoundException::withId($articleId);
        }

        return ArticleResponse::fromEntity($article);
    }

    /**
     * Update an article
     */
    public function update(UpdateArticleRequest $request): ArticleResponse
    {
        $articleId = ArticleId::fromString($request->id);
        $article = $this->articleRepository->findById($articleId);

        if ($article === null) {
            throw ArticleNotFoundException::withId($articleId);
        }

        $article->update(
            title: Title::fromString($request->title),
            content: Content::fromString($request->content),
        );

        $this->articleRepository->save($article);

        return ArticleResponse::fromEntity($article);
    }

    /**
     * Delete an article
     */
    public function delete(string $id): void
    {
        $articleId = ArticleId::fromString($id);

        if (!$this->articleRepository->exists($articleId)) {
            throw ArticleNotFoundException::withId($articleId);
        }

        $this->articleRepository->delete($articleId);
    }

    /**
     * List all articles
     */
    public function list(?string $status = null): ArticleListResponse
    {
        if ($status !== null) {
            $statusEnum = ArticleStatus::from($status);
            $articles = $this->articleRepository->findByStatus($statusEnum);
        } else {
            $articles = $this->articleRepository->findAll();
        }

        $articleResponses = array_map(
            fn (Article $article) => ArticleResponse::fromEntity($article),
            $articles,
        );

        return new ArticleListResponse(
            articles: $articleResponses,
            total: count($articleResponses),
        );
    }

    /**
     * Publish an article
     */
    public function publish(string $id): ArticleResponse
    {
        $articleId = ArticleId::fromString($id);
        $article = $this->articleRepository->findById($articleId);

        if ($article === null) {
            throw ArticleNotFoundException::withId($articleId);
        }

        $article->publish();
        $this->articleRepository->save($article);

        return ArticleResponse::fromEntity($article);
    }

    /**
     * Archive an article
     */
    public function archive(string $id): ArticleResponse
    {
        $articleId = ArticleId::fromString($id);
        $article = $this->articleRepository->findById($articleId);

        if ($article === null) {
            throw ArticleNotFoundException::withId($articleId);
        }

        $article->archive();
        $this->articleRepository->save($article);

        return ArticleResponse::fromEntity($article);
    }
}

