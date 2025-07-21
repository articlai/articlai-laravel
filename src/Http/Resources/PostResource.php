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
        return [
            'id' => (string) $this->id,
            'url' => $this->url,
            'title' => $this->title,
            'content' => $this->content,
            'excerpt' => $this->excerpt,
            'slug' => $this->slug,
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'focus_keyword' => $this->focus_keyword,
            'canonical_url' => $this->canonical_url,
            'status' => $this->status,
            'published_at' => $this->published_at?->toISOString(),
            'custom_fields' => $this->custom_fields ?: new \stdClass(),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }

    /**
     * Create a success response for post creation
     */
    public static function created($post): array
    {
        return [
            'id' => (string) $post->id,
            'url' => $post->url,
            'title' => $post->title,
            'status' => $post->status,
            'created_at' => $post->created_at->toISOString(),
        ];
    }

    /**
     * Create a success response for post update
     */
    public static function updated($post): array
    {
        return [
            'id' => (string) $post->id,
            'url' => $post->url,
            'title' => $post->title,
            'status' => $post->status,
            'updated_at' => $post->updated_at->toISOString(),
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
