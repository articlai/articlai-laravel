<?php

namespace Articlai\Articlai;

use Articlai\Articlai\Models\ArticlaiPost;

class Articlai
{
    /**
     * Sanitize HTML content based on configuration
     */
    public function sanitizeContent(string $content): string
    {
        if (!config('articlai-laravel.content.sanitize_html', true)) {
            return $content;
        }

        $allowedTags = config('articlai-laravel.content.allowed_html_tags', [
            'p', 'br', 'strong', 'em', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
            'ul', 'ol', 'li', 'a', 'img', 'blockquote', 'code', 'pre'
        ]);

        // Basic HTML sanitization - in production, consider using a library like HTMLPurifier
        $allowedTagsString = '<' . implode('><', $allowedTags) . '>';

        return strip_tags($content, $allowedTagsString);
    }

    /**
     * Create a new post
     */
    public function createPost(array $data): ArticlaiPost
    {
        if (isset($data['content'])) {
            $data['content'] = $this->sanitizeContent($data['content']);
        }

        return ArticlaiPost::create($data);
    }

    /**
     * Update an existing post
     */
    public function updatePost(int $id, array $data): ArticlaiPost
    {
        $post = ArticlaiPost::findOrFail($id);

        if (isset($data['content'])) {
            $data['content'] = $this->sanitizeContent($data['content']);
        }

        $post->update($data);

        return $post->fresh();
    }

    /**
     * Delete a post
     */
    public function deletePost(int $id): bool
    {
        $post = ArticlaiPost::findOrFail($id);
        return $post->delete();
    }

    /**
     * Get a post by ID
     */
    public function getPost(int $id): ArticlaiPost
    {
        return ArticlaiPost::findOrFail($id);
    }

    /**
     * Get all posts with optional filtering
     */
    public function getPosts(array $filters = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = ArticlaiPost::query();

        if (isset($filters['status'])) {
            $query->byStatus($filters['status']);
        }

        if (isset($filters['published']) && $filters['published']) {
            $query->published();
        }

        return $query->orderBy('created_at', 'desc')
                    ->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Validate connection and return platform info
     */
    public function validateConnection(): array
    {
        return [
            'success' => true,
            'message' => 'Connection validated successfully',
            'platform_info' => [
                'name' => config('articlai-laravel.platform.name', 'Laravel Application'),
                'version' => config('articlai-laravel.platform.version', '1.0.0'),
                'capabilities' => config('articlai-laravel.platform.capabilities', ['create', 'update', 'delete']),
            ],
        ];
    }
}
