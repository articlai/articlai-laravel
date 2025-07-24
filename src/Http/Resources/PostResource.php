<?php

namespace Articlai\Articlai\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        // Use the ArticlaiConnectable interface to get properly mapped data
        $articlaiData = $this->resource->getArticlaiData();

        return [
            'id' => (string) $this->resource->getKey(),
            'url' => $articlaiData['url'] ?? $this->resource->getUrl(),
            'title' => $articlaiData['title'] ?? null,
            'content' => $articlaiData['content'] ?? null,
            'excerpt' => $articlaiData['excerpt'] ?? null,
            'slug' => $articlaiData['slug'] ?? null,
            'meta_title' => $articlaiData['meta_title'] ?? null,
            'meta_description' => $articlaiData['meta_description'] ?? null,
            'focus_keyword' => $articlaiData['focus_keyword'] ?? null,
            'canonical_url' => $articlaiData['canonical_url'] ?? null,
            'status' => $articlaiData['status'] ?? null,
            'published_at' => isset($articlaiData['published_at']) && $articlaiData['published_at']
                ? $articlaiData['published_at']->toISOString()
                : null,
            'custom_fields' => $articlaiData['custom_fields'] ?: new \stdClass,
            'banner_image' => $articlaiData['banner_image'] ?? null,
            'banner_thumbnail' => $articlaiData['banner_thumbnail'] ?? null,
            'banner_medium' => $articlaiData['banner_medium'] ?? null,
            'banner_large' => $articlaiData['banner_large'] ?? null,
            'banner_original' => $articlaiData['banner_original'] ?? null,
            'created_at' => $this->resource->created_at?->toISOString(),
            'updated_at' => $this->resource->updated_at?->toISOString(),
        ];
    }

    /**
     * Create a success response for post creation
     */
    public static function created($post): array
    {
        $articlaiData = $post->getArticlaiData();

        return [
            'id' => (string) $post->getKey(),
            'url' => $articlaiData['url'] ?? $post->getUrl(),
            'title' => $articlaiData['title'] ?? null,
            'status' => $articlaiData['status'] ?? null,
            'created_at' => $post->created_at?->toISOString(),
        ];
    }

    /**
     * Create a success response for post update
     */
    public static function updated($post): array
    {
        $articlaiData = $post->getArticlaiData();

        return [
            'id' => (string) $post->getKey(),
            'url' => $articlaiData['url'] ?? $post->getUrl(),
            'title' => $articlaiData['title'] ?? null,
            'status' => $articlaiData['status'] ?? null,
            'updated_at' => $post->updated_at?->toISOString(),
        ];
    }

    /**
     * Create a success response for post deletion
     */
    public static function deleted(): array
    {
        return [
            'success' => true,
            'message' => 'Post deleted successfully',
        ];
    }

    /**
     * Create a validation response
     */
    public static function validation(): array
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
